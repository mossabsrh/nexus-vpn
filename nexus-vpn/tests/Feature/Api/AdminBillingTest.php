<?php

namespace Tests\Feature\Api;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Testimonial;
use App\Models\User;
use App\Models\VpnCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminBillingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_billing_endpoints_are_admin_only(): void
    {
        $user = User::factory()->create();

        foreach (['/api/admin/stats', '/api/admin/payments', '/api/admin/subscriptions', '/api/admin/audit-logs', '/api/admin/testimonials'] as $url) {
            $this->actingAs($user)->getJson($url)->assertForbidden();
        }
    }

    public function test_stats_are_computed_from_real_billing_data(): void
    {
        $plan = Plan::factory()->create(['slug' => 'standard']);
        $paying = $this->paidCustomer($plan, 1500);
        $this->paidCustomer($plan, 9000, 'refunded');
        User::factory()->create()->subscriptions()->create([
            'plan_id' => $plan->id, 'status' => 'trialing', 'trial_ends_at' => now()->addDays(3), 'current_period_end' => now()->addDays(3),
        ]);
        $paying->subscriptions()->first()->update(['trial_ends_at' => now()->subDays(10)]);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/stats')->assertOk();

        $response->assertJsonPath('subscriptions.active', 2)
            ->assertJsonPath('subscriptions.trialing', 1)
            ->assertJsonPath('revenue.total', 1500)
            ->assertJsonPath('revenue.thisMonth', 1500)
            ->assertJsonPath('revenue.refunded', 9000)
            ->assertJsonPath('payments.succeeded', 1)
            ->assertJsonPath('users.total', 4)
            ->assertJsonPath('subscriptions.trialUsers', 2)
            ->assertJsonPath('subscriptions.convertedTrialUsers', 1);
        $byPlan = collect($response->json('subscriptions.byPlan'))->firstWhere('planId', 'standard');
        $this->assertSame(['planId' => 'standard', 'name' => $plan->name, 'active' => 2, 'trialing' => 1], $byPlan);
    }

    public function test_users_list_is_paginated_searchable_and_filterable(): void
    {
        User::factory()->count(25)->create();
        $target = User::factory()->create(['name' => 'Zed Findme', 'email' => 'zed@findme.test']);
        $target->subscriptions()->create(['plan_id' => Plan::factory()->create()->id, 'status' => 'active', 'current_period_end' => now()->addMonth()]);

        $this->actingAs($this->admin)->getJson('/api/admin/users?per_page=10')
            ->assertOk()->assertJsonCount(10, 'users')->assertJsonPath('meta.total', 27)->assertJsonPath('meta.lastPage', 3);
        $this->actingAs($this->admin)->getJson('/api/admin/users?q=FINDME')
            ->assertJsonCount(1, 'users')->assertJsonPath('users.0.subscription.status', 'active');
        $this->actingAs($this->admin)->getJson('/api/admin/users?subscription=active')->assertJsonCount(1, 'users');
        $this->actingAs($this->admin)->getJson('/api/admin/users?role=admin')->assertJsonCount(1, 'users');
    }

    public function test_admin_refund_records_the_refund_ends_the_subscription_and_revokes_vpn(): void
    {
        $plan = Plan::factory()->create();
        $customer = $this->paidCustomer($plan, 1500);
        $payment = $customer->payments()->sole();
        VpnCredential::issueFor($customer, $customer->subscriptions()->sole());

        $this->actingAs($this->admin)->postJson("/api/admin/payments/{$payment->id}/refund", ['reason' => 'Customer request'])
            ->assertOk()
            ->assertJsonPath('payment.status', 'refunded')
            ->assertJsonPath('payment.refundReason', 'Customer request');

        $this->assertNotNull($payment->refresh()->refunded_at);
        $this->assertFalse($customer->hasVpnAccess());
        $this->assertSame('revoked', $customer->vpnCredentials()->sole()->status);
        $this->assertTrue(AuditLog::query()->where('action', 'payment.refunded')->where('actor_id', $this->admin->id)->exists());

        $this->actingAs($this->admin)->postJson("/api/admin/payments/{$payment->id}/refund", ['reason' => 'Again'])
            ->assertStatus(409);
    }

    public function test_payments_and_subscriptions_lists_support_filters(): void
    {
        $plan = Plan::factory()->create(['slug' => 'filtered']);
        $this->paidCustomer($plan, 1500);
        $this->paidCustomer($plan, 1500, 'refunded');

        $this->actingAs($this->admin)->getJson('/api/admin/payments?status=refunded')
            ->assertOk()->assertJsonCount(1, 'payments')->assertJsonPath('payments.0.status', 'refunded');
        $this->actingAs($this->admin)->getJson('/api/admin/subscriptions?plan=filtered')
            ->assertOk()->assertJsonCount(2, 'subscriptions')->assertJsonPath('subscriptions.0.paidPayments', 1);
    }

    public function test_plans_with_history_are_deactivated_not_deleted_and_pricing_updates_immediately(): void
    {
        $plan = Plan::factory()->create(['slug' => 'legacy']);
        $this->paidCustomer($plan, 1500);
        $this->getJson('/api/pricing')->assertJsonFragment(['slug' => 'legacy']);

        $this->actingAs($this->admin)->deleteJson("/api/admin/plans/{$plan->id}")->assertStatus(409);
        $this->actingAs($this->admin)->patchJson("/api/admin/plans/{$plan->id}", $plan->only([
            'slug', 'name', 'description', 'monthly_price', 'yearly_price', 'yearly_total', 'currency', 'features', 'popular', 'cta',
        ]) + ['is_active' => false])->assertOk();

        $this->getJson('/api/pricing')->assertJsonMissing(['slug' => 'legacy']);
        $this->assertNotSoftDeleted($plan);

        $unused = Plan::factory()->create();
        $this->actingAs($this->admin)->deleteJson("/api/admin/plans/{$unused->id}")->assertOk();
        $this->assertSoftDeleted($unused);
    }

    public function test_testimonials_require_moderation_before_they_are_public(): void
    {
        $author = User::factory()->create();

        $this->actingAs($author)->postJson('/api/testimonials', ['content' => 'Great service, fast servers.'])
            ->assertCreated()->assertJsonPath('testimonial.status', 'pending');
        $this->actingAs($author)->postJson('/api/testimonials', ['content' => 'Another one while pending.'])
            ->assertStatus(429);
        $this->getJson('/api/testimonials')->assertJsonCount(0, 'testimonials');

        $testimonial = Testimonial::query()->sole();
        $this->actingAs($this->admin)->getJson('/api/admin/testimonials?status=pending')->assertJsonPath('pendingCount', 1);
        $this->actingAs($this->admin)->patchJson("/api/admin/testimonials/{$testimonial->id}", ['status' => 'approved'])->assertOk();

        $this->getJson('/api/testimonials')->assertJsonCount(1, 'testimonials')->assertJsonPath('meta.total', 1);

        $this->actingAs($this->admin)->deleteJson("/api/admin/testimonials/{$testimonial->id}")->assertOk();
        $this->getJson('/api/testimonials')->assertJsonCount(0, 'testimonials');
    }

    public function test_admin_actions_appear_in_the_audit_log(): void
    {
        $user = User::factory()->create();
        $this->actingAs($this->admin)->patchJson("/api/admin/users/{$user->id}", ['role' => 'admin'])->assertOk();

        $this->actingAs($this->admin)->getJson('/api/admin/audit-logs?action=admin.')
            ->assertOk()
            ->assertJsonPath('logs.0.action', 'admin.user_updated')
            ->assertJsonPath('logs.0.actor.id', $this->admin->id)
            ->assertJsonPath('logs.0.metadata.role_to', 'admin');
    }

    public function test_team_subscription_flow_with_seat_limits(): void
    {
        config(['services.vpn.server_public_key' => base64_encode(str_repeat('k', 32)), 'services.vpn.endpoint' => 'vpn.test:51820']);
        $team = Plan::factory()->salesAssisted()->create(['slug' => 'team']);
        $owner = User::factory()->create();
        [$memberA, $memberB] = User::factory()->count(2)->create();

        $this->actingAs($this->admin)->postJson('/api/admin/subscriptions/team', [
            'email' => $owner->email, 'plan_slug' => $team->slug, 'team_name' => 'Acme', 'seats' => 2, 'months' => 12,
        ])->assertCreated()->assertJsonPath('team.seats', 2)->assertJsonPath('team.seatsUsed', 1);

        $this->actingAs($memberA)->postJson('/api/team/members', ['email' => $memberB->email])->assertForbidden();
        $this->actingAs($owner)->postJson('/api/team/members', ['email' => $memberA->email])->assertCreated();
        $this->actingAs($owner)->postJson('/api/team/members', ['email' => $memberB->email])
            ->assertStatus(409)->assertJsonPath('message', 'All 2 seats are in use.');

        $this->assertTrue($memberA->hasVpnAccess());
        $this->actingAs($memberA)->get('/api/vpn/config')->assertOk();
        $this->actingAs($memberA)->getJson('/api/me')->assertJsonPath('user.team.name', 'Acme')->assertJsonPath('user.team.isOwner', false);
        $this->actingAs($memberA)->getJson('/api/team')->assertJsonMissingPath('team.members');

        $this->actingAs($owner)->deleteJson("/api/team/members/{$memberA->id}")->assertOk();
        $this->assertFalse($memberA->fresh()->hasVpnAccess());
        $this->assertSame('revoked', $memberA->vpnCredentials()->sole()->status);
    }

    private function paidCustomer(Plan $plan, int $amount, string $status = 'succeeded'): User
    {
        $user = User::factory()->create();
        $subscription = $user->subscriptions()->create([
            'plan_id' => $plan->id, 'status' => 'active', 'started_at' => now(), 'current_period_end' => now()->addMonth(),
        ]);
        Payment::query()->create([
            'user_id' => $user->id, 'subscription_id' => $subscription->id, 'amount' => $amount, 'currency' => 'DZD',
            'status' => $status, 'provider' => 'chargily', 'paid_at' => now(),
            'refunded_at' => $status === 'refunded' ? now() : null,
            'metadata' => ['user_id' => $user->id, 'plan_id' => $plan->id, 'billing_period' => 'monthly'],
        ]);

        return $user;
    }
}
