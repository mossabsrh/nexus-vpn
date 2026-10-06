<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Payment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Chargily Pay v2 API client and the rules that turn a checkout status into a payment outcome.
 */
class ChargilyGateway
{
    /**
     * Chargily checkout statuses that end a checkout without payment.
     */
    public const UNPAID_FINAL_STATUSES = ['failed', 'canceled', 'expired'];

    public function isConfigured(): bool
    {
        return $this->secretKey() !== '';
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws ConnectionException
     */
    public function createCheckout(array $payload): Response
    {
        return Http::withToken($this->secretKey())
            ->acceptJson()
            ->connectTimeout(5)->timeout(15)
            ->post($this->baseUrl().'/checkouts', $payload);
    }

    /**
     * @throws ConnectionException
     */
    public function retrieveCheckout(string $checkoutId): Response
    {
        return Http::withToken($this->secretKey())->acceptJson()->connectTimeout(5)->timeout(15)
            ->get($this->baseUrl().'/checkouts/'.$checkoutId);
    }

    public function verifySignature(string $rawBody, string $signature): bool
    {
        $secretKey = $this->secretKey();

        if ($secretKey === '') {
            Log::critical('Chargily webhook received but CHARGILY_SECRET_KEY is not configured.');

            return false;
        }

        return hash_equals(hash_hmac('sha256', $rawBody, $secretKey), $signature);
    }

    /**
     * @param  array<string, mixed>  $checkout
     */
    public function applyCheckoutStatus(Payment $payment, string $status, array $checkout): void
    {
        if ($status === 'paid') {
            // A missing amount or currency counts as a mismatch: never activate on unverifiable data.
            $mismatch = match (true) {
                ! is_numeric($checkout['amount'] ?? null) || (int) $checkout['amount'] !== (int) $payment->amount => 'amount_mismatch',
                strtolower((string) ($checkout['currency'] ?? '')) !== strtolower((string) $payment->currency) => 'currency_mismatch',
                default => null,
            };

            if ($mismatch !== null) {
                Log::critical('Chargily paid checkout does not match the payment record; not activating.', [
                    'payment_id' => $payment->id,
                    'error_code' => $mismatch,
                    'expected' => ['amount' => $payment->amount, 'currency' => $payment->currency],
                    'received' => ['amount' => $checkout['amount'] ?? null, 'currency' => $checkout['currency'] ?? null],
                ]);
                $payment->update(['error_code' => $mismatch]);

                return;
            }

            if (! $payment->markSucceeded($checkout)) {
                $this->flagPaidAfterFailure($payment->refresh());
            }

            return;
        }

        if (in_array($status, self::UNPAID_FINAL_STATUSES, true)) {
            $payment->markFailed('Checkout '.$status.' at Chargily.', 'checkout_'.$status);
        }
    }

    /**
     * Chargily took the money but our record was already closed as failed, so nothing was activated.
     * A duplicate "paid" for a succeeded payment is normal and is not flagged.
     */
    private function flagPaidAfterFailure(Payment $payment): void
    {
        if ($payment->status !== 'failed' || $payment->error_code === 'paid_after_failure') {
            return;
        }

        Log::critical('Chargily reports a paid checkout for a payment already marked failed; nothing was activated.', [
            'payment_id' => $payment->id,
            'checkout_id' => $payment->provider_reference,
            'previous_error_code' => $payment->error_code,
        ]);

        $payment->update([
            'error_code' => 'paid_after_failure',
            'failure_reason' => 'Paid at Chargily after being marked failed. Activate or refund manually.',
        ]);
        AuditLog::record('payment.paid_after_failure', $payment, ['checkout_id' => $payment->provider_reference], $payment->user);
    }

    private function secretKey(): string
    {
        return (string) config('services.chargily.secret_key');
    }

    private function baseUrl(): string
    {
        return config('services.chargily.mode', 'test') === 'live'
            ? 'https://pay.chargily.net/api/v2'
            : 'https://pay.chargily.net/test/api/v2';
    }
}
