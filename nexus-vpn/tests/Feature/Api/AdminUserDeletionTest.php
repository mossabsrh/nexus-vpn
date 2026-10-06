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

class AdminUserDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_an_admin_anonymises_a_user_and_keeps_billing_history(): void
    {
        $admin = $this->admin();
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

        $this->actingAs($admin)->deleteJson('/api/admin/users/'.$user->id)
            ->assertOk()
            ->assertJsonPath('message', 'User account deleted.');

        $user = User::withTrashed()->find($user->id);
        $this->assertNotNull($user->deleted_at);
        $this->assertSame('Deleted account', $user->name);
        $this->assertSame('deleted+'.$user->id.'@deleted.invalid', $user->email);
        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com', 'deleted_at' => null]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'succeeded']);
        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id, 'status' => 'canceled']);
        $this->assertSame('revoked', $user->vpnCredentials()->sole()->status);

        $this->assertSame(1, DB::table('audit_logs')
            ->where('action', 'account.deleted')->where('actor_id', $admin->id)->count());
    }

    public function test_an_admin_can_delete_another_admin_when_one_remains(): void
    {
        $admin = $this->admin();
        $other = $this->admin();

        $this->actingAs($admin)->deleteJson('/api/admin/users/'.$other->id)->assertOk();

        $this->assertNotNull(User::withTrashed()->find($other->id)->deleted_at);
    }

    public function test_an_admin_cannot_delete_themselves(): void
    {
        $admin = $this->admin();
        $this->admin(); // a second admin, so the "last admin" guard does not fire first

        $this->actingAs($admin)->deleteJson('/api/admin/users/'.$admin->id)
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot delete your own account from the admin panel.');

        $this->assertNull(User::find($admin->id)->deleted_at);
    }

    public function test_the_last_admin_cannot_be_deleted(): void
    {
        $admin = $this->admin(); // the only admin

        $this->actingAs($admin)->deleteJson('/api/admin/users/'.$admin->id)
            ->assertStatus(422)
            ->assertJsonPath('message', 'You cannot delete the last administrator.');

        $this->assertNull(User::find($admin->id)->deleted_at);
    }

    public function test_a_team_owner_cannot_be_deleted_from_the_admin_panel(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        Team::query()->create(['name' => 'Acme', 'owner_id' => $owner->id]);

        $this->actingAs($admin)->deleteJson('/api/admin/users/'.$owner->id)
            ->assertStatus(422);

        $this->assertNull(User::find($owner->id)->deleted_at);
    }

    public function test_non_admins_cannot_delete_users(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($user)->deleteJson('/api/admin/users/'.$target->id)->assertForbidden();

        $this->assertNull(User::find($target->id)->deleted_at);
    }
}
