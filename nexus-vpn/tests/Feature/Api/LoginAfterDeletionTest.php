<?php

namespace Tests\Feature\Api;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginAfterDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_active_user_can_log_in_with_the_correct_password(): void
    {
        $user = User::factory()->create(['email' => 'active@example.com', 'password' => Hash::make('secret-pass-1')]);

        $this->postJson('/api/auth/login', ['email' => 'active@example.com', 'password' => 'secret-pass-1'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.email', 'active@example.com');

        // Email is normalised, so a different case still logs in.
        $this->postJson('/api/auth/login', ['email' => 'ACTIVE@example.com', 'password' => 'secret-pass-1'])
            ->assertOk();
    }

    public function test_a_closed_account_can_no_longer_log_in(): void
    {
        $user = User::factory()->create(['email' => 'gone@example.com', 'password' => Hash::make('secret-pass-2')]);
        $user->closeAccount($user);

        $this->postJson('/api/auth/login', ['email' => 'gone@example.com', 'password' => 'secret-pass-2'])
            ->assertStatus(401);
    }

    public function test_the_email_is_freed_and_can_be_reused_after_the_account_is_closed(): void
    {
        Plan::factory()->create(['slug' => 'standard', 'monthly_price' => 1500]);
        $first = User::factory()->create(['email' => 'reuse@example.com', 'password' => Hash::make('old-pass')]);
        $first->closeAccount($first);

        // Re-registration with the freed address succeeds (the closed row holds a placeholder email).
        $this->postJson('/api/auth/register', [
            'name' => 'Second Owner',
            'email' => 'reuse@example.com',
            'password' => 'new-pass-123',
            'password_confirmation' => 'new-pass-123',
            'plan_slug' => 'standard',
        ])->assertCreated();

        $this->postJson('/api/auth/logout')->assertOk();

        // The new owner can log in; the old password no longer works.
        $this->postJson('/api/auth/login', ['email' => 'reuse@example.com', 'password' => 'new-pass-123'])->assertOk();
        $this->postJson('/api/auth/login', ['email' => 'reuse@example.com', 'password' => 'old-pass'])->assertStatus(401);

        $this->assertSame(1, User::where('email', 'reuse@example.com')->count());
    }
}
