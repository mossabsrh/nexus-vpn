<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminBillingController extends Controller
{
    /**
     * Real billing and growth metrics. Amounts are integers in the plan currency (DZD).
     */
    public function stats(): JsonResponse
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();

        $succeeded = Payment::query()->where('status', 'succeeded');
        $revenueByMonth = Payment::query()
            ->where('status', 'succeeded')
            ->where('paid_at', '>=', $now->copy()->subMonths(5)->startOfMonth())
            ->get(['amount', 'paid_at'])
            ->groupBy(fn (Payment $payment) => $payment->paid_at->format('Y-m'))
            ->map(fn ($payments) => $payments->sum('amount'));

        $signupsByDay = User::query()
            ->where('created_at', '>=', $now->copy()->subDays(29)->startOfDay())
            ->get(['created_at'])
            ->groupBy(fn (User $user) => $user->created_at->format('Y-m-d'))
            ->map->count();

        $trialUserIds = Subscription::withTrashed()->whereNotNull('trial_ends_at')->distinct()->pluck('user_id');
        $convertedTrialUsers = Payment::query()->whereIn('status', ['succeeded', 'refunded'])
            ->whereIn('user_id', $trialUserIds)->distinct()->count('user_id');

        $firstPaymentByUser = Payment::query()->whereIn('status', ['succeeded', 'refunded'])
            ->groupBy('user_id')->selectRaw('user_id, min(paid_at) as first_paid_at')->pluck('first_paid_at', 'user_id');
        $renewalsThisMonth = Payment::query()->where('status', 'succeeded')->where('paid_at', '>=', $startOfMonth)
            ->get(['user_id', 'paid_at'])
            ->filter(fn (Payment $payment) => Carbon::parse($firstPaymentByUser[$payment->user_id])->lt($payment->paid_at))
            ->count();

        $current = Subscription::query()->grantingAccess();

        return response()->json([
            'users' => [
                'total' => User::query()->count(),
                'admins' => User::query()->where('role', 'admin')->count(),
                'verified' => User::query()->whereNotNull('email_verified_at')->count(),
                'newLast30Days' => User::query()->where('created_at', '>=', $now->copy()->subDays(30))->count(),
                'newPrevious30Days' => User::query()->whereBetween('created_at', [$now->copy()->subDays(60), $now->copy()->subDays(30)])->count(),
                'signupsByDay' => collect(range(29, 0))->mapWithKeys(fn (int $daysAgo) => [
                    $key = $now->copy()->subDays($daysAgo)->format('Y-m-d') => $signupsByDay[$key] ?? 0,
                ]),
            ],
            'subscriptions' => [
                'active' => (clone $current)->where('status', 'active')->count(),
                'trialing' => (clone $current)->where('status', 'trialing')->count(),
                'canceledWithAccess' => (clone $current)->where('status', 'canceled')->count(),
                'expired' => Subscription::query()->where('status', 'expired')->count(),
                'byPlan' => Plan::withTrashed()
                    ->withCount([
                        'subscriptions as active_count' => fn ($query) => $query->grantingAccess()->where('status', 'active'),
                        'subscriptions as trialing_count' => fn ($query) => $query->grantingAccess()->where('status', 'trialing'),
                    ])
                    ->get(['id', 'slug', 'name'])
                    ->map(fn (Plan $plan) => [
                        'planId' => $plan->slug,
                        'name' => $plan->name,
                        'active' => $plan->active_count,
                        'trialing' => $plan->trialing_count,
                    ])
                    ->values(),
                'trialUsers' => $trialUserIds->count(),
                'convertedTrialUsers' => $convertedTrialUsers,
                'trialConversionRate' => $trialUserIds->count() > 0 ? round($convertedTrialUsers / $trialUserIds->count() * 100, 1) : null,
            ],
            'revenue' => [
                'currency' => 'DZD',
                'total' => (int) (clone $succeeded)->sum('amount'),
                'thisMonth' => (int) (clone $succeeded)->where('paid_at', '>=', $startOfMonth)->sum('amount'),
                'last30Days' => (int) (clone $succeeded)->where('paid_at', '>=', $now->copy()->subDays(30))->sum('amount'),
                'refunded' => (int) Payment::query()->where('status', 'refunded')->sum('amount'),
                'renewalsThisMonth' => $renewalsThisMonth,
                'byMonth' => collect(range(5, 0))->mapWithKeys(fn (int $monthsAgo) => [
                    $key = $now->copy()->subMonths($monthsAgo)->format('Y-m') => (int) ($revenueByMonth[$key] ?? 0),
                ]),
            ],
            'payments' => Payment::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function payments(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'succeeded', 'failed', 'refunded'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $payments = Payment::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('q'), fn ($query) => $query->whereHas('user', fn ($user) => $user->withTrashed()
                ->whereRaw('lower(email) like ?', ['%'.str_replace(['%', '_'], ['\%', '\_'], strtolower($request->input('q'))).'%'])))
            ->with('user:id,name,email')
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'payments' => $payments->getCollection()->map(fn (Payment $payment) => $payment->toApiArray() + [
                'errorCode' => $payment->error_code,
                'user' => $payment->user ? ['id' => $payment->user->id, 'name' => $payment->user->name, 'email' => $payment->user->email] : null,
            ]),
            'meta' => AdminController::meta($payments),
        ]);
    }

    public function refund(Request $request, Payment $payment): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'end_subscription' => ['sometimes', 'boolean'],
        ]);

        try {
            $payment->recordRefund($validated['reason'], $validated['end_subscription'] ?? true, $request->user());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        }

        return response()->json(['payment' => $payment->fresh()->toApiArray()]);
    }

    public function subscriptions(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['trialing', 'active', 'past_due', 'canceled', 'expired'])],
            'plan' => ['nullable', 'string', 'max:100'],
        ]);

        $subscriptions = Subscription::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('plan'), fn ($query) => $query->whereHas('plan', fn ($plan) => $plan->withTrashed()->where('slug', $request->input('plan'))))
            ->with(['user:id,name,email', 'plan', 'team'])
            ->withCount(['payments as paid_payments_count' => fn ($query) => $query->whereIn('status', ['succeeded', 'refunded'])])
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'subscriptions' => $subscriptions->getCollection()->map(fn (Subscription $subscription) => $subscription->toApiArray() + [
                'user' => $subscription->user ? ['id' => $subscription->user->id, 'name' => $subscription->user->name, 'email' => $subscription->user->email] : null,
                'teamName' => $subscription->team?->name,
                'paidPayments' => $subscription->paid_payments_count,
            ]),
            'meta' => AdminController::meta($subscriptions),
        ]);
    }

    /**
     * Team plans are sold by sales with custom seats/price; an admin provisions them here once agreed.
     */
    public function createTeamSubscription(Request $request): JsonResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $validated = $request->validate([
            'email' => ['required', 'email', Rule::exists('users', 'email')->whereNull('deleted_at')],
            'plan_slug' => ['required', 'string', Rule::exists('plans', 'slug')->whereNull('deleted_at')],
            'team_name' => ['required', 'string', 'max:150'],
            'seats' => ['required', 'integer', 'min:2', 'max:500'],
            'months' => ['required', 'integer', 'min:1', 'max:36'],
        ]);

        $owner = User::query()->where('email', $validated['email'])->firstOrFail();
        $plan = Plan::query()->where('slug', $validated['plan_slug'])->firstOrFail();

        if ($owner->currentSubscription()) {
            return response()->json(['message' => 'This user already has an active subscription or trial. Cancel it first.'], 409);
        }

        try {
            $team = DB::transaction(function () use ($owner, $plan, $validated): Team {
                $subscription = $owner->subscriptions()->create([
                    'plan_id' => $plan->id,
                    'seats' => $validated['seats'],
                    'billing_period' => $validated['months'] >= 12 ? 'yearly' : 'monthly',
                    'status' => 'active',
                    'started_at' => now(),
                    'current_period_end' => now()->addMonthsNoOverflow($validated['months']),
                ]);

                $team = Team::query()->create([
                    'name' => $validated['team_name'],
                    'owner_id' => $owner->id,
                    'subscription_id' => $subscription->id,
                ]);
                $team->members()->attach($owner->id, ['role' => 'owner', 'created_at' => now()]);

                return $team;
            });
        } catch (UniqueConstraintViolationException) {
            return response()->json(['message' => 'This user already has an active subscription.'], 409);
        }

        AuditLog::record('admin.team_subscription_created', $team, [
            'owner_id' => $owner->id,
            'plan' => $plan->slug,
            'seats' => $validated['seats'],
            'months' => $validated['months'],
        ]);

        return response()->json(['team' => $team->fresh()->toApiArray()], 201);
    }

    /**
     * Ends a subscription immediately (e.g. abuse or a manual refund agreement).
     */
    public function cancelSubscription(Subscription $subscription): JsonResponse
    {
        if (! $subscription->grantsAccess()) {
            return response()->json(['message' => 'This subscription is not active.'], 409);
        }

        $subscription->update(['status' => 'canceled', 'canceled_at' => now(), 'current_period_end' => now()]);
        AuditLog::record('admin.subscription_canceled', $subscription, ['user_id' => $subscription->user_id]);
        $subscription->refresh()->revokeAccessIfEnded('admin_canceled');

        return response()->json(['subscription' => $subscription->toApiArray()]);
    }
}
