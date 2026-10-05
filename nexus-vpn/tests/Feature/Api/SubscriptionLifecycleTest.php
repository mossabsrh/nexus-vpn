<?php

namespace Tests\Feature\Api;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\VpnCredential;
use App\Notifications\SubscriptionReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        config([
            'services.vpn.server_public_key' => base64_encode(str_repeat('k', 32)),
            'services.vpn.endpoint' => 'vpn.example.test:51820',
        ]);
    }

    public function test_canceling_a_trial_ends_access_immediately_and_revokes_vpn(): void
    {
        [$user, $subscription] = $this->subscribed('trialing', now()->addDays(5));
        VpnCredential::issueFor($user, $subscription);

        $this->actingAs($user)->postJson('/api/subscriptions/cancel')
            ->assertOk()
            ->assertJsonPath('message', 'Your trial has been canceled.')
            ->assertJsonPath('user.hasVpnAccess', false);

        $this->assertSame(0, $user->vpnCredentials()->where('status', 'active')->count());
    }

    public function test_canceling_a_paid_subscription_keeps_access_until_the_paid_period_ends(): void
    {
        $periodEnd = now()->addDays(20)->startOfSecond();
        [$user, $subscription] = $this->subscribed('active', $periodEnd);

        $this->actingAs($user)->postJson('/api/subscriptions/cancel')
            ->assertOk()
            ->assertJsonPath('subscription.status', 'canceled')
            ->assertJsonPath('user.hasVpnAccess', true);

        $this->assertTrue($subscription->refresh()->current_period_end->equalTo($periodEnd));
        $this->assertNotNull($subscription->canceled_at);
    }

    public function test_canceling_without_a_current_subscription_is_rejected(): void
    {
        [$user] = $this->subscribed('active', now()->addMonth());

        $this->actingAs($user)->postJson('/api/subscriptions/cancel')->assertOk();
        $this->actingAs($user)->postJson('/api/subscriptions/cancel')->assertStatus(409);
    }

    public function test_the_per_subscription_cancel_route_no_longer_exists(): void
    {
        [$user, $subscription] = $this->subscribed('active', now()->addMonth());

        $this->actingAs($user)->postJson("/api/subscriptions/{$subscription->id}/cancel")->assertNotFound();
    }

    public function test_trial_is_offered_once_and_never_for_sales_assisted_plans(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/subscriptions/trial', ['plan_slug' => Plan::factory()->salesAssisted()->create()->slug])
            ->assertStatus(422);
        $this->actingAs($user)->postJson('/api/subscriptions/trial', ['plan_slug' => Plan::factory()->create()->slug])
            ->assertOk();
        $user->subscriptions()->update(['status' => 'expired']);
        $this->actingAs($user)->postJson('/api/subscriptions/trial', ['plan_slug' => Plan::factory()->create()->slug])
            ->assertStatus(409);
    }

    public function test_expiry_command_expires_ended_periods_and_revokes_vpn_access(): void
    {
        [$user, $subscription] = $this->subscribed('active', now()->addDay());
        VpnCredential::issueFor($user, $subscription);
        [$stillValid] = $this->subscribed('trialing', now()->addDays(3));
        $this->travel(2)->days();

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $this->assertSame('expired', $subscription->refresh()->status);
        $this->assertSame('revoked', $user->vpnCredentials()->sole()->status);
        $this->assertSame('trialing', $stillValid->subscriptions()->sole()->status);
        Notification::assertSentTo($user, SubscriptionReminderNotification::class, fn ($notification) => $notification->kind === 'expired');
    }

    public function test_access_ends_at_the_period_end_even_before_the_scheduler_runs(): void
    {
        [$user] = $this->subscribed('active', now()->addHour());
        $this->travel(2)->hours();

        $this->assertFalse($user->hasVpnAccess());
        $this->actingAs($user)->get('/api/vpn/config')->assertForbidden();
    }

    public function test_reminders_are_sent_once_per_period(): void
    {
        [$trialUser] = $this->subscribed('trialing', now()->addDays(2));
        [$paidUser] = $this->subscribed('active', now()->addDays(2));
        [$farUser] = $this->subscribed('active', now()->addDays(20));

        $this->artisan('subscriptions:remind')->assertSuccessful();
        $this->artisan('subscriptions:remind')->assertSuccessful();

        Notification::assertSentToTimes($trialUser, SubscriptionReminderNotification::class, 1);
        Notification::assertSentToTimes($paidUser, SubscriptionReminderNotification::class, 1);
        Notification::assertNotSentTo($farUser, SubscriptionReminderNotification::class);
    }

    public function test_vpn_config_download_issues_a_valid_wireguard_peer(): void
    {
        [$user] = $this->subscribed('active', now()->addMonth());

        $config = $this->actingAs($user)->get('/api/vpn/config')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->getContent();

        $credential = $user->vpnCredentials()->sole();
        preg_match('/PrivateKey = (\S+)/', $config, $match);
        $this->assertSame($credential->public_key, base64_encode(sodium_crypto_scalarmult_base(base64_decode($match[1]))));
        $this->assertStringContainsString('Address = '.$credential->address.'/32', $config);
        $this->assertStringContainsString('Endpoint = vpn.example.test:51820', $config);
        $this->assertNotSame($match[1], $credential->getRawOriginal('private_key'), 'private key is encrypted at rest');

        $this->actingAs($user)->get('/api/vpn/config')->assertOk();
        $this->assertSame(1, $user->vpnCredentials()->count(), 'downloading again reuses the same peer');

        $this->artisan('vpn:peers')->expectsOutputToContain('PublicKey = '.$credential->public_key)->assertSuccessful();
    }

    public function test_vpn_config_requires_a_verified_email_and_a_configured_server(): void
    {
        [$user] = $this->subscribed('active', now()->addMonth(), User::factory()->unverified()->create());
        $this->actingAs($user)->get('/api/vpn/config')->assertForbidden()->assertJsonPath('code', 'email_unverified');

        config(['services.vpn.endpoint' => null]);
        [$verified] = $this->subscribed('active', now()->addMonth());
        $this->actingAs($verified)->get('/api/vpn/config')->assertStatus(503);
    }

    public function test_peers_get_distinct_tunnel_addresses(): void
    {
        [$first, $firstSubscription] = $this->subscribed('active', now()->addMonth());
        [$second, $secondSubscription] = $this->subscribed('active', now()->addMonth());

        $a = VpnCredential::issueFor($first, $firstSubscription);
        $b = VpnCredential::issueFor($second, $secondSubscription);

        $this->assertSame('10.66.0.2', $a->address);
        $this->assertSame('10.66.0.3', $b->address);
    }

    /**
     * @return array{0: User, 1: Subscription}
     */
    private function subscribed(string $status, $periodEnd, ?User $user = null): array
    {
        $user ??= User::factory()->create();
        $subscription = $user->subscriptions()->create([
            'plan_id' => Plan::factory()->create()->id,
            'status' => $status,
            'started_at' => now(),
            'trial_ends_at' => $status === 'trialing' ? $periodEnd : null,
            'current_period_end' => $periodEnd,
        ]);

        return [$user, $subscription];
    }
}
