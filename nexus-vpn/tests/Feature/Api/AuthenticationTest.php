<?php

namespace Tests\Feature\Api;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_is_authenticated(): void
    {
        Plan::create(['slug' => 'standard', 'name' => 'Standard', 'monthly_price' => 1500, 'currency' => 'DZD']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'plan_slug' => 'standard',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'ada@example.com');
        $this->assertAuthenticated();
    }

    public function test_user_can_update_email_and_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->actingAs($user)->putJson('/api/account/email', [
            'current_password' => 'wrong-password',
            'email' => 'new@example.com',
        ])->assertUnprocessable();

        $this->actingAs($user)->putJson('/api/account/email', [
            'current_password' => 'password',
            'email' => 'New@Example.com',
        ])->assertOk()->assertJsonPath('user.email', 'new@example.com');

        $this->actingAs($user)->putJson('/api/account/password', [
            'current_password' => 'password',
            'password' => 'new-password',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_user_can_login_and_read_session_user(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk();

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_user_can_logout_and_session_is_invalidated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertGuest();
        $this->getJson('/api/me')->assertUnauthorized();
    }
}
