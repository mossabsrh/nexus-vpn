<?php

namespace Tests\Feature\Api;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PaymentReceiptNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test_sk_demo_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.chargily.mode' => 'test',
            'services.chargily.secret_key' => self::SECRET,
            'app.frontend_url' => 'https://frontend.test',
        ]);
        Notification::fake();
    }

    public function test_checkout_records_the_payment_first_and_returns_to_the_frontend_result_page(): void
    {
        Http::fake(['https://pay.chargily.net/test/api/v2/checkouts' => Http::response([
            'id' => 'chk_1', 'status' => 'pending', 'checkout_url' => 'https://pay.chargily.test/chk_1',
        ])]);
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['monthly_price' => 1500]);

        $response = $this->actingAs($user)->postJson('/api/checkout', ['plan_slug' => $plan->slug])->assertOk();

        $payment = Payment::query()->findOrFail($response->json('payment_id'));
        $this->assertSame('chk_1', $payment->provider_reference);
        $this->assertSame('DZD', $payment->currency);
        Http::assertSent(fn ($request) => $request['currency'] === 'dzd'
            && $request['success_url'] === 'https://frontend.test/payment/result?payment='.$payment->id);
    }

    public function test_checkout_requires_a_verified_email(): void
    {
        Http::fake();
        $plan = Plan::factory()->create();

        $this->actingAs(User::factory()->unverified()->create())
            ->postJson('/api/checkout', ['plan_slug' => $plan->slug])
            ->assertForbidden()
            ->assertJsonPath('code', 'email_unverified');

        Http::assertNothingSent();
    }

    public function test_sales_assisted_and_inactive_plans_cannot_be_bought_online(): void
    {
        Http::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/checkout', ['plan_slug' => Plan::factory()->salesAssisted()->create()->slug])
            ->assertStatus(422);
        $this->actingAs($user)->postJson('/api/checkout', ['plan_slug' => Plan::factory()->inactive()->create()->slug])
            ->assertJsonValidationErrors('plan_slug');

        Http::assertNothingSent();
    }

    public function test_provider_errors_are_not_echoed_and_the_payment_is_marked_failed(): void
    {
        Http::fake(['*' => Http::response(['message' => 'internal provider detail'], 500)]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/checkout', ['plan_slug' => Plan::factory()->create()->slug])
            ->assertStatus(502)
            ->assertJsonMissing(['message' => 'internal provider detail']);

        $payment = Payment::query()->sole();
        $this->assertSame('failed', $payment->status);
        $this->assertSame('http_500', $payment->error_code);
    }

    public function test_replayed_webhook_and_later_verify_apply_the_payment_only_once(): void
    {
        [$user, $plan, $payment] = $this->pendingPayment('chk_once');

        $this->postWebhook('checkout.paid', ['id' => 'chk_once', 'status' => 'paid', 'amount' => 1500, 'currency' => 'dzd'])->assertOk();
        $periodEnd = $user->subscriptions()->sole()->current_period_end;

        $this->postWebhook('checkout.paid', ['id' => 'chk_once', 'status' => 'paid', 'amount' => 1500, 'currency' => 'dzd'])->assertOk();
        Http::fake(['*' => Http::response(['id' => 'chk_once', 'status' => 'paid', 'amount' => 1500, 'currency' => 'dzd'])]);
        $this->actingAs($user)->getJson('/api/payments/'.$payment->id.'/verify')
            ->assertOk()
            ->assertJsonPath('status', 'succeeded');

        $subscription = $user->subscriptions()->sole();
        $this->assertSame('active', $subscription->status);
        $this->assertTrue($subscription->current_period_end->equalTo($periodEnd));
        $this->assertSame($subscription->id, $payment->refresh()->subscription_id);
        Notification::assertSentToTimes($user, PaymentReceiptNotification::class, 1);
        $this->assertSame(1, AuditLog::query()->where('action', 'payment.succeeded')->count());
        $this->assertSame(1, $user->vpnCredentials()->where('status', 'active')->count());
    }

    public function test_a_payment_that_is_no_longer_pending_cannot_be_claimed_again(): void
    {
        [, , $payment] = $this->pendingPayment('chk_claim');

        $this->assertTrue($payment->markSucceeded(['status' => 'paid']));
        $this->assertFalse(Payment::query()->find($payment->id)->markSucceeded(['status' => 'paid']));
        $this->assertSame(1, Subscription::query()->count());
    }

    public function test_paying_during_a_trial_converts_the_trial(): void
    {
        [$user, $plan, $payment] = $this->pendingPayment('chk_trial');
        $trial = $user->subscriptions()->create([
            'plan_id' => $plan->id, 'status' => 'trialing', 'started_at' => now(),
            'trial_ends_at' => now()->addDays(3), 'current_period_end' => now()->addDays(3),
        ]);

        $payment->markSucceeded(['status' => 'paid']);

        $trial->refresh();
        $this->assertSame('active', $trial->status);
        $this->assertNotNull($trial->trial_ends_at, 'trial marker is kept for conversion metrics');
        $this->assertTrue($trial->current_period_end->gt(now()->addDays(27)));
        $this->assertSame(1, $user->subscriptions()->count());
    }

    public function test_renewal_extends_from_the_end_of_the_current_period(): void
    {
        [$user, $plan, $payment] = $this->pendingPayment('chk_renew');
        $periodEnd = now()->addDays(10)->startOfSecond();
        $active = $user->subscriptions()->create([
            'plan_id' => $plan->id, 'status' => 'active', 'started_at' => now()->subDays(20), 'current_period_end' => $periodEnd,
        ]);

        $payment->markSucceeded(['status' => 'paid']);

        $this->assertTrue($active->refresh()->current_period_end->equalTo($periodEnd->copy()->addMonthNoOverflow()));
    }

    public function test_resubscribing_after_a_cancellation_keeps_the_days_already_paid_for(): void
    {
        [$user, $plan, $payment] = $this->pendingPayment('chk_resubscribe');
        $periodEnd = now()->addDays(10)->startOfSecond();
        $canceled = $user->subscriptions()->create([
            'plan_id' => $plan->id, 'status' => 'canceled', 'started_at' => now()->subDays(20),
            'canceled_at' => now()->subDay(), 'current_period_end' => $periodEnd,
        ]);

        $payment->markSucceeded(['status' => 'paid']);

        $renewed = $user->subscriptions()->current()->sole();
        $this->assertNotSame($canceled->id, $renewed->id, 'canceled rows are history and are not revived');
        $this->assertTrue($renewed->current_period_end->equalTo($periodEnd->copy()->addMonthNoOverflow()));
    }

    public function test_resubscribing_after_a_cancelled_period_ended_starts_from_now(): void
    {
        [$user, $plan, $payment] = $this->pendingPayment('chk_lapsed');
        $user->subscriptions()->create([
            'plan_id' => $plan->id, 'status' => 'canceled', 'started_at' => now()->subDays(40),
            'canceled_at' => now()->subDays(20), 'current_period_end' => now()->subDays(10),
        ]);

        $payment->markSucceeded(['status' => 'paid']);

        $renewed = $user->subscriptions()->current()->sole();
        $this->assertTrue($renewed->current_period_end->between(now()->addDays(27), now()->addDays(32)));
    }

    public function test_paying_for_another_plan_ends_the_current_one_and_starts_a_new_one(): void
    {
        [$user, , $payment] = $this->pendingPayment('chk_switch');
        $old = $user->subscriptions()->create([
            'plan_id' => Plan::factory()->create()->id, 'status' => 'active', 'current_period_end' => now()->addDays(10),
        ]);

        $payment->markSucceeded(['status' => 'paid']);

        $this->assertSame('canceled', $old->refresh()->status);
        $this->assertSame(1, $user->subscriptions()->current()->count());
    }

    public function test_the_database_refuses_a_second_current_subscription(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();
        $user->subscriptions()->create(['plan_id' => $plan->id, 'status' => 'trialing', 'current_period_end' => now()->addDay()]);

        $this->expectException(UniqueConstraintViolationException::class);
        $user->subscriptions()->create(['plan_id' => $plan->id, 'status' => 'active', 'current_period_end' => now()->addMonth()]);
    }

    public function test_webhook_for_unknown_checkout_returns_404_so_chargily_retries(): void
    {
        $this->postWebhook('checkout.paid', ['id' => 'chk_missing', 'status' => 'paid'])->assertNotFound();
    }

    public function test_webhook_failed_event_records_the_failure_reason(): void
    {
        [, , $payment] = $this->pendingPayment('chk_failed');

        $this->postWebhook('checkout.failed', ['id' => 'chk_failed', 'status' => 'failed'])->assertOk();

        $payment->refresh();
        $this->assertSame('failed', $payment->status);
        $this->assertSame('checkout_failed', $payment->error_code);
        $this->assertNotNull($payment->failure_reason);
    }

    public function test_webhook_with_a_different_amount_does_not_activate(): void
    {
        [$user, , $payment] = $this->pendingPayment('chk_amount');

        $this->postWebhook('checkout.paid', ['id' => 'chk_amount', 'status' => 'paid', 'amount' => 1, 'currency' => 'dzd'])->assertOk();

        $this->assertSame('pending', $payment->refresh()->status);
        $this->assertSame('amount_mismatch', $payment->error_code);
        $this->assertSame(0, $user->subscriptions()->count());
    }

    public function test_webhook_without_an_amount_does_not_activate(): void
    {
        [$user, , $payment] = $this->pendingPayment('chk_no_amount');

        $this->postWebhook('checkout.paid', ['id' => 'chk_no_amount', 'status' => 'paid', 'currency' => 'dzd'])->assertOk();

        $this->assertSame('pending', $payment->refresh()->status);
        $this->assertSame('amount_mismatch', $payment->error_code);
        $this->assertSame(0, $user->subscriptions()->count());
    }

    public function test_paid_checkout_in_another_currency_does_not_activate(): void
    {
        [$user, , $payment] = $this->pendingPayment('chk_currency');

        $this->postWebhook('checkout.paid', ['id' => 'chk_currency', 'status' => 'paid', 'amount' => 1500, 'currency' => 'eur'])->assertOk();
        Http::fake(['*' => Http::response(['id' => 'chk_currency', 'status' => 'paid', 'amount' => 1500])]);
        $this->actingAs($user)->getJson('/api/payments/'.$payment->id.'/verify')
            ->assertOk()
            ->assertJsonPath('status', 'pending');

        $this->assertSame('pending', $payment->refresh()->status);
        $this->assertSame('currency_mismatch', $payment->error_code);
        $this->assertSame(0, $user->subscriptions()->count());
    }

    public function test_paid_webhook_for_a_failed_payment_is_flagged_for_the_admin(): void
    {
        [$user, , $payment] = $this->pendingPayment('chk_late');
        $payment->markFailed('Checkout expired at Chargily.', 'checkout_expired');

        $paid = ['id' => 'chk_late', 'status' => 'paid', 'amount' => 1500, 'currency' => 'dzd'];
        $this->postWebhook('checkout.paid', $paid)->assertOk();
        $this->postWebhook('checkout.paid', $paid)->assertOk();

        $payment->refresh();
        $this->assertSame('failed', $payment->status);
        $this->assertSame('paid_after_failure', $payment->error_code);
        $this->assertSame(0, $user->subscriptions()->count());
        $this->assertSame(1, AuditLog::query()->where('action', 'payment.paid_after_failure')->count());
    }

    public function test_duplicate_paid_webhook_for_a_succeeded_payment_is_not_flagged(): void
    {
        [, , $payment] = $this->pendingPayment('chk_dup');

        $paid = ['id' => 'chk_dup', 'status' => 'paid', 'amount' => 1500, 'currency' => 'dzd'];
        $this->postWebhook('checkout.paid', $paid)->assertOk();
        $this->postWebhook('checkout.paid', $paid)->assertOk();

        $this->assertSame('succeeded', $payment->refresh()->status);
        $this->assertNull($payment->error_code);
        $this->assertSame(0, AuditLog::query()->where('action', 'payment.paid_after_failure')->count());
    }

    public function test_users_cannot_verify_someone_elses_payment(): void
    {
        [, , $payment] = $this->pendingPayment('chk_owner');

        $this->actingAs(User::factory()->create())
            ->getJson('/api/payments/'.$payment->id.'/verify')
            ->assertNotFound();
    }

    public function test_users_can_list_their_payment_history_and_receipts_only(): void
    {
        [$user, , $payment] = $this->pendingPayment('chk_history');
        $payment->markSucceeded(['status' => 'paid']);
        [, , $otherPayment] = $this->pendingPayment('chk_other');

        $this->actingAs($user)->getJson('/api/account/payments')
            ->assertOk()
            ->assertJsonCount(1, 'payments')
            ->assertJsonPath('payments.0.status', 'succeeded')
            ->assertJsonMissingPath('payments.0.provider_response');
        $this->actingAs($user)->getJson('/api/account/payments/'.$payment->id)->assertOk()->assertJsonPath('payment.amount', 1500);
        $this->actingAs($user)->getJson('/api/account/payments/'.$otherPayment->id)->assertNotFound();
        $this->actingAs($user)->getJson('/api/account/subscriptions')->assertOk()->assertJsonCount(1, 'subscriptions');
    }

    /**
     * @return array{0: User, 1: Plan, 2: Payment}
     */
    private function pendingPayment(string $checkoutId): array
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

        return [$user, $plan, $payment];
    }

    private function postWebhook(string $type, array $data): TestResponse
    {
        $body = json_encode(['id' => 'evt_1', 'entity' => 'event', 'type' => $type, 'data' => $data]);

        return $this->call('POST', '/api/webhooks/chargily', [], [], [], [
            'HTTP_SIGNATURE' => hash_hmac('sha256', $body, self::SECRET),
            'CONTENT_TYPE' => 'application/json',
        ], $body);
    }
}
