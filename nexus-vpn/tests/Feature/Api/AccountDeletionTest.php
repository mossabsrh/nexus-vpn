<?php

namespace Tests\Feature\Api;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Team;
use App\Models\User;
use App\Models\VpnCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_anonymises_the_user_and_keeps_billing_history(): void
    {
        $plan = Plan::factory()->create(['monthly_price' => 1500]);
        $user = User::factory()->create(['email' => 'jane@example.com', 'name' => 'Jane Doe']);
        $subscription = $user->subscriptions()->create([
            'plan_id' => $plan->id, 'status' => 'active', 'started_at' => now()->subDay(), 'current_period_end' => now()->addMonth(),
        ]);
        $payment = Payment::query()->create([
            'user_id' => $user->id, 'subscription_id' => $subscription->id, 'amount' => 1500,
            'currency' => 'DZD', 'status' => 'succeeded', 'provider' => 'chargily', 'paid_at' => now(),
        ]);
        VpnCredential::query()->create([
            'user_id' => $user->id, 'subscription_id' => $subscription->id, 'status' => 'active',
            'public_key' => 'pub', 'private_key' => 'priv', 'address' => '10.66.0.2',
        ]);

        $this->actingAs($user)->deleteJson('/api/account', ['current_password' => 'password'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $user = User::withTrashed()->find($user->id);
        $this->assertNotNull($user->deleted_at);
        $this->assertSame('Deleted account', $user->name);
        $this->assertSame('deleted+'.$user->id.'@deleted.invalid', $user->email);
        $this->assertNull($user->email_verified_at);

        // The original e-mail is freed, and the billing rows survive.
        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com', 'deleted_at' => null]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'user_id' => $user->id, 'status' => 'succeeded']);
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'canceled']);
        $this->assertSame('revoked', $user->vpnCredentials()->sole()->status);
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'account.deleted')->count());
    }

    public function test_the_deleted_user_can_no_longer_authenticate(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->deleteJson('/api/account', ['current_password' => 'password'])->assertOk();

        $this->assertGuest();
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_it_requires_the_correct_current_password(): void
    {
        $user = User::factory()->create(['email' => 'keep@example.com']);

        $this->actingAs($user)->deleteJson('/api/account', ['current_password' => 'wrong-password'])
            ->assertStatus(422);

        $this->assertNull(User::find($user->id)->deleted_at);
        $this->assertDatabaseHas('users', ['email' => 'keep@example.com', 'deleted_at' => null]);
    }

    public function test_admins_cannot_delete_their_account_from_here(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->deleteJson('/api/account', ['current_password' => 'password'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'admin_account');

        $this->assertNull(User::find($user->id)->deleted_at);
    }

    public function test_team_owners_cannot_delete_their_account_from_here(): void
    {
        $user = User::factory()->create();
        Team::query()->create(['name' => 'Acme', 'owner_id' => $user->id]);

        $this->actingAs($user)->deleteJson('/api/account', ['current_password' => 'password'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'team_owner');

        $this->assertNull(User::find($user->id)->deleted_at);
    }
}
