<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReconcilePaymentsTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test_sk_demo_secret';

    private const BASE = 'https://pay.chargily.net/test/api/v2';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.chargily.mode' => 'test',
            'services.chargily.secret_key' => self::SECRET,
        ]);
        Notification::fake();
    }

    public function test_it_activates_a_stale_payment_that_was_paid_at_chargily(): void
    {
        [$user, $payment] = $this->stalePayment('chk_paid', 90);

        Http::fake([
            self::BASE.'/checkouts/chk_paid' => Http::response(['id' => 'chk_paid', 'status' => 'paid', 'amount' => 1500, 'currency' => 'dzd']),
        ]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame('succeeded', $payment->refresh()->status);
        $this->assertSame(1, $user->subscriptions()->count());
    }

    public function test_it_leaves_a_still_pending_recent_checkout_untouched(): void
    {
        [, $payment] = $this->stalePayment('chk_wait', 45);

        Http::fake([
            self::BASE.'/checkouts/chk_wait' => Http::response(['id' => 'chk_wait', 'status' => 'pending']),
        ]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $this->assertSame('pending', $payment->refresh()->status);
        Http::assertSentCount(1);
    }

    public function test_it_expires_a_checkout_abandoned_past_the_deadline(): void
    {
        [, $payment] = $this->stalePayment('chk_old', 60 * 30);

        Http::fake([
            self::BASE.'/checkouts/chk_old' => Http::response(['id' => 'chk_old', 'status' => 'pending']),
            self::BASE.'/checkouts/chk_old/expire' => Http::response(['id' => 'chk_old', 'status' => 'expired']),
        ]);

        $this->artisan('payments:reconcile')->assertSuccessful();

        $payment->refresh();
        $this->assertSame('failed', $payment->status);
        $this->assertSame('checkout_expired', $payment->error_code);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/checkouts/chk_old/expire'));
    }

    public function test_it_ignores_payments_still_within_the_grace_period(): void
    {
        $this->stalePayment('chk_fresh', 5);

        Http::fake();

        $this->artisan('payments:reconcile')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_it_does_nothing_without_a_secret_key(): void
    {
        config(['services.chargily.secret_key' => '']);
        $this->stalePayment('chk_nokey', 90);

        Http::fake();

        $this->artisan('payments:reconcile')->assertSuccessful();

        Http::assertNothingSent();
    }

    /**
     * @return array{0: User, 1: Payment}
     */
    private function stalePayment(string $checkoutId, int $ageMinutes): array
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['monthly_price' => 1500]);
        $payment = Payment::query()->create([
            'user_id' => $user->id,
            'amount' => 1500,
            'currency' => 'DZD',
            'status' => 'pending',
            'provider' => 'chargily',
            'provider_reference' => $checkoutId,
            'metadata' => ['payment_id' => 'pay_'.$checkoutId, 'user_id' => $user->id, 'plan_id' => $plan->id, 'billing_period' => 'monthly'],
        ]);
        $payment->forceFill(['created_at' => now()->subMinutes($ageMinutes)])->save();

        return [$user, $payment];
    }
}
