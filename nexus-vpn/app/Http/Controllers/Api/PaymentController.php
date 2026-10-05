<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\VpnCredential;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * Chargily checkout statuses that end a checkout without payment.
     */
    private const UNPAID_FINAL_STATUSES = ['failed', 'canceled', 'expired'];

    public function createCheckout(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => ['required_without:plan_slug', 'integer', Rule::exists('plans', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'plan_slug' => ['required_without:plan_id', 'string', Rule::exists('plans', 'slug')->where('is_active', true)->whereNull('deleted_at')],
            'billing_period' => ['nullable', 'in:monthly,yearly'],
        ]);

        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email address before subscribing.',
                'code' => 'email_unverified',
            ], 403);
        }

        $plan = $request->filled('plan_id')
            ? Plan::query()->active()->findOrFail($request->input('plan_id'))
            : Plan::query()->active()->where('slug', $request->input('plan_slug'))->firstOrFail();
        $billingPeriod = $request->input('billing_period', 'monthly');
        $amount = (int) match ($billingPeriod) {
            'yearly' => $plan->yearly_total ?? 0,
            default => $plan->monthly_price ?? 0,
        };

        if (! $plan->isSelfServe() || $amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'This plan cannot be bought online. Please contact us.',
            ], 422);
        }

        $secretKey = (string) config('services.chargily.secret_key');

        if ($secretKey === '') {
            Log::critical('Chargily checkout requested but CHARGILY_SECRET_KEY is not configured.');

            return response()->json([
                'success' => false,
                'message' => 'Online payments are temporarily unavailable.',
            ], 503);
        }

        $reference = 'pay_'.Str::uuid()->toString();
        $metadata = [
            'payment_id' => $reference,
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'billing_period' => $billingPeriod,
        ];

        // Recorded before calling Chargily so a fast webhook always finds it.
        $payment = Payment::query()->create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => strtoupper($plan->currency ?: 'DZD'),
            'status' => 'pending',
            'provider' => 'chargily',
            'metadata' => $metadata,
        ]);

        $resultUrl = config('app.frontend_url').'/payment/result?'.http_build_query(['payment' => $payment->id]);

        try {
            $response = Http::withToken($secretKey)
                ->acceptJson()
                ->connectTimeout(5)->timeout(15)
                ->post($this->baseUrl().'/checkouts', [
                    'amount' => $amount,
                    // Chargily expects a lowercase ISO code; our records store it uppercase.
                    'currency' => strtolower($payment->currency),
                    'success_url' => $resultUrl,
                    'failure_url' => $resultUrl,
                    'chargily_pay_fees_allocation' => 'merchant',
                    'description' => $plan->name.' - '.$billingPeriod.' subscription',
                    'locale' => 'fr',
                    'metadata' => $metadata,
                ]);
        } catch (ConnectionException $exception) {
            $payment->markFailed('Could not reach Chargily.', 'connection_error');
            Log::error('Chargily checkout request failed.', ['payment_id' => $payment->id, 'exception' => $exception->getMessage()]);

            return response()->json(['success' => false, 'message' => 'The payment provider is unreachable. Please try again.'], 502);
        }

        $body = $response->json() ?? [];

        if ($response->failed() || empty($body['id']) || empty($body['checkout_url'])) {
            $payment->update(['provider_response' => $body]);
            $payment->markFailed((string) ($body['message'] ?? 'Checkout could not be created.'), 'http_'.$response->status());
            Log::warning('Chargily rejected a checkout.', ['payment_id' => $payment->id, 'status' => $response->status()]);

            return response()->json([
                'success' => false,
                'message' => 'Checkout could not be created. Please try again.',
            ], 502);
        }

        $payment->update([
            'provider_reference' => $body['id'],
            'provider_response' => $body,
        ]);

        return response()->json([
            'success' => true,
            'status' => $body['status'] ?? 'pending',
            'checkout_id' => $body['id'],
            'checkout_url' => $body['checkout_url'],
            'payment_id' => $payment->id,
            'message' => 'Checkout created successfully.',
        ]);
    }

    /**
     * Post-payment check by our payment id (used by the frontend result page).
     */
    public function verifyPayment(Request $request, Payment $payment): JsonResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 404);

        return $this->syncWithProvider($payment);
    }

    /**
     * Post-payment check by Chargily checkout id.
     */
    public function verify(Request $request, string $checkoutId): JsonResponse
    {
        $payment = Payment::query()
            ->where('provider_reference', $checkoutId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $payment) {
            return response()->json(['success' => false, 'message' => 'Checkout not found.'], 404);
        }

        return $this->syncWithProvider($payment);
    }

    public function cancelSubscription(Request $request): JsonResponse
    {
        $subscription = $request->user()->currentSubscription();

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'You have no active subscription or trial to cancel.',
            ], 409);
        }

        if ($subscription->team && $subscription->user_id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Team subscriptions are managed by our sales team. Please contact us to cancel.',
            ], 409);
        }

        $wasTrial = $subscription->isTrial();
        $subscription->cancel('user');

        return response()->json([
            'success' => true,
            'subscription' => $subscription->fresh('plan')->toApiArray(),
            'user' => AuthController::userPayload($request->user()->fresh()),
            'message' => $wasTrial
                ? 'Your trial has been canceled.'
                : 'Your subscription has been canceled. You keep access until '.$subscription->current_period_end?->toFormattedDayDateString().'.',
        ]);
    }

    public function startTrial(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_slug' => ['required', 'string', Rule::exists('plans', 'slug')->where('is_active', true)->whereNull('deleted_at')],
        ]);

        $plan = Plan::query()->active()->where('slug', $validated['plan_slug'])->firstOrFail();

        if (! $plan->isSelfServe()) {
            return response()->json([
                'success' => false,
                'message' => 'A trial is not available for the '.$plan->name.' plan.',
            ], 422);
        }

        $user = $request->user();

        // One trial per account, ever.
        if ($user->subscriptions()->withTrashed()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This account already has or had a subscription, so it is not eligible for a trial.',
                'user' => AuthController::userPayload($user),
            ], 409);
        }

        try {
            $trialEndsAt = now()->addDays(7);
            $subscription = $user->subscriptions()->create([
                'plan_id' => $plan->id,
                'seats' => 1,
                'billing_period' => 'monthly',
                'status' => 'trialing',
                'started_at' => now(),
                'trial_ends_at' => $trialEndsAt,
                'current_period_end' => $trialEndsAt,
            ]);
        } catch (UniqueConstraintViolationException) {
            // A concurrent request created the trial first (one current subscription per user is DB-enforced).
            return response()->json([
                'success' => false,
                'message' => 'This account already has a subscription.',
                'user' => AuthController::userPayload($user),
            ], 409);
        }

        AuditLog::record('subscription.trial_started', $subscription, ['plan' => $plan->slug], $user);

        if ($user->hasVerifiedEmail()) {
            VpnCredential::issueFor($user, $subscription);
        }

        return response()->json([
            'success' => true,
            'user' => AuthController::userPayload($user),
            'subscription' => $subscription->load('plan')->toApiArray(),
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $signature = (string) $request->header('signature', '');
        $rawBody = $request->getContent();

        if ($signature === '') {
            return response()->json(['message' => 'Missing signature'], 400);
        }

        if (! $this->verifySignature($rawBody, $signature)) {
            Log::warning('Chargily webhook rejected: invalid signature.', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $payload = json_decode($rawBody, true);

        if (! is_array($payload) || ! is_array($payload['data'] ?? null)) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $event = (string) ($payload['type'] ?? '');
        $checkout = $payload['data'];
        $checkoutId = $checkout['id'] ?? null;

        if (! in_array($event, ['checkout.paid', 'checkout.failed', 'checkout.canceled', 'checkout.expired'], true)) {
            Log::info('Chargily webhook ignored: unhandled event type.', ['type' => $event]);

            return response()->json(['status' => 'ignored']);
        }

        $payment = $checkoutId ? Payment::query()->where('provider_reference', $checkoutId)->first() : null;

        if (! $payment) {
            // Non-2xx makes Chargily retry, which covers the brief window before the checkout id is stored.
            Log::warning('Chargily webhook for an unknown checkout.', ['type' => $event, 'checkout_id' => $checkoutId]);

            return response()->json(['message' => 'Unknown checkout'], 404);
        }

        $this->applyCheckoutStatus($payment, $event === 'checkout.paid' ? 'paid' : Str::after($event, 'checkout.'), $checkout);

        return response()->json(['status' => 'success']);
    }

    private function syncWithProvider(Payment $payment): JsonResponse
    {
        if ($payment->status === 'pending' && $payment->provider_reference) {
            $secretKey = (string) config('services.chargily.secret_key');

            if ($secretKey === '') {
                return response()->json(['success' => false, 'message' => 'Online payments are temporarily unavailable.'], 503);
            }

            try {
                $response = Http::withToken($secretKey)->acceptJson()->connectTimeout(5)->timeout(15)
                    ->get($this->baseUrl().'/checkouts/'.$payment->provider_reference);
            } catch (ConnectionException) {
                return response()->json(['success' => false, 'message' => 'The payment provider is unreachable. Please retry.'], 502);
            }

            if ($response->failed()) {
                Log::warning('Chargily checkout lookup failed.', ['payment_id' => $payment->id, 'status' => $response->status()]);

                return response()->json(['success' => false, 'message' => 'Unable to verify the payment right now.'], 502);
            }

            $checkout = $response->json() ?? [];
            $this->applyCheckoutStatus($payment, (string) ($checkout['status'] ?? ''), $checkout);
            $payment->refresh();
        }

        return response()->json([
            'success' => true,
            'status' => $payment->status,
            'payment' => $payment->toApiArray(),
            'user' => AuthController::userPayload(request()->user()->fresh()),
        ]);
    }

    /**
     * @param  array<string, mixed>  $checkout
     */
    private function applyCheckoutStatus(Payment $payment, string $status, array $checkout): void
    {
        if ($status === 'paid') {
            if (isset($checkout['amount']) && (int) $checkout['amount'] !== (int) $payment->amount) {
                Log::critical('Chargily paid amount does not match the payment record; not activating.', [
                    'payment_id' => $payment->id,
                    'expected' => $payment->amount,
                    'received' => $checkout['amount'],
                ]);
                $payment->update(['error_code' => 'amount_mismatch']);

                return;
            }

            $payment->markSucceeded($checkout);

            return;
        }

        if (in_array($status, self::UNPAID_FINAL_STATUSES, true)) {
            $payment->markFailed('Checkout '.$status.' at Chargily.', 'checkout_'.$status);
        }
    }

    private function verifySignature(string $rawBody, string $signature): bool
    {
        $secretKey = (string) config('services.chargily.secret_key');

        if ($secretKey === '') {
            Log::critical('Chargily webhook received but CHARGILY_SECRET_KEY is not configured.');

            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secretKey), $signature);
    }

    private function baseUrl(): string
    {
        return config('services.chargily.mode', 'test') === 'live'
            ? 'https://pay.chargily.net/api/v2'
            : 'https://pay.chargily.net/test/api/v2';
    }
}
