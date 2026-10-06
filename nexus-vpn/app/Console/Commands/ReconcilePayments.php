<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\ChargilyGateway;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile
        {--older-than=30 : Only check payments pending for at least this many minutes}
        {--expire-after=24 : Expire checkouts still unpaid after this many hours}';

    protected $description = 'Sync stale pending Chargily payments with Chargily and expire abandoned checkouts';

    public function handle(ChargilyGateway $chargily): int
    {
        if (! $chargily->isConfigured()) {
            $this->warn('CHARGILY_SECRET_KEY is not configured; nothing to reconcile.');

            return self::SUCCESS;
        }

        $expireBefore = now()->subHours((int) $this->option('expire-after'));
        $counts = ['checked' => 0, 'succeeded' => 0, 'failed' => 0, 'pending' => 0, 'errors' => 0];

        Payment::query()
            ->where('status', 'pending')
            ->where('provider', 'chargily')
            ->where('created_at', '<=', now()->subMinutes((int) $this->option('older-than')))
            ->chunkById(100, function ($payments) use ($chargily, $expireBefore, &$counts): void {
                foreach ($payments as $payment) {
                    $counts['checked']++;
                    $outcome = $this->reconcile($chargily, $payment, $payment->created_at <= $expireBefore);
                    $counts[$outcome]++;
                }
            });

        $summary = sprintf(
            'Checked %d pending payment(s): %d succeeded, %d failed or expired, %d still pending, %d error(s).',
            $counts['checked'], $counts['succeeded'], $counts['failed'], $counts['pending'], $counts['errors'],
        );
        $this->info($summary);

        if ($counts['checked'] > 0) {
            Log::info('Chargily payments reconciled.', $counts);
        }

        return self::SUCCESS;
    }

    /**
     * @return 'succeeded'|'failed'|'pending'|'errors'
     */
    private function reconcile(ChargilyGateway $chargily, Payment $payment, bool $canExpire): string
    {
        // The checkout id is stored before the customer gets the payment link, so without it nobody could have paid.
        if (! $payment->provider_reference) {
            $payment->markFailed('Checkout was never created at Chargily.', 'checkout_missing');

            return 'failed';
        }

        try {
            $response = $chargily->retrieveCheckout($payment->provider_reference);
        } catch (ConnectionException) {
            return 'errors';
        }

        if ($response->failed()) {
            Log::warning('Chargily checkout lookup failed during reconciliation.', ['payment_id' => $payment->id, 'status' => $response->status()]);

            return 'errors';
        }

        $checkout = $response->json() ?? [];
        $status = (string) ($checkout['status'] ?? '');
        $chargily->applyCheckoutStatus($payment, $status, $checkout);
        $payment->refresh();

        if ($payment->status !== 'pending') {
            return $payment->status === 'succeeded' ? 'succeeded' : 'failed';
        }

        if ($status === 'pending' && $canExpire) {
            return $this->expire($chargily, $payment);
        }

        return 'pending';
    }

    /**
     * @return 'failed'|'pending'|'errors'
     */
    private function expire(ChargilyGateway $chargily, Payment $payment): string
    {
        try {
            $response = $chargily->expireCheckout($payment->provider_reference);
        } catch (ConnectionException) {
            return 'errors';
        }

        // 400 means Chargily already expired it; either way the customer can no longer pay.
        if ($response->successful() || $response->status() === 400) {
            $payment->markFailed('Checkout expired: not paid within '.$this->option('expire-after').' hours.', 'checkout_expired');

            return 'failed';
        }

        // 403: no longer pending at Chargily (e.g. just paid); the next run picks up the new status.
        return 'pending';
    }
}
