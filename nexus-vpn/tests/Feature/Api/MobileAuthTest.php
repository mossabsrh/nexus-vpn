<?php

namespace Tests\Feature\Api;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_a_token_and_starts_a_trial(): void
    {
        Plan::factory()->create(['slug' => 'standard', 'name' => 'Standard']);

        $response = $this->postJson('/api/mobile/auth/register', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'plan_slug' => 'standard',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'ada@example.com')
            ->assertJsonPath('user.subscription.status', 'trialing')
            ->assertJsonPath('success', true);

        $this->assertNotEmpty($response->json('token'));

        $user = User::query()->where('email', 'ada@example.com')->sole();
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_login_returns_a_token(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $response = $this->postJson('/api/mobile/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->assertJsonPath('user.id', $user->id);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_rejects_an_invalid_password(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $this->postJson('/api/mobile/auth/login', [
            'email' => $user->email,
            'password' => 'wrong',
        ])->assertUnauthorized()->assertJsonPath('success', false);
    }

    public function test_login_locks_the_account_after_five_failed_attempts(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/mobile/auth/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnauthorized();
        }

        // Even the correct password is refused once the account is locked.
        $this->postJson('/api/mobile/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(429);
    }

    public function test_protected_routes_require_a_token(): void
    {
        $this->getJson('/api/mobile/me')->assertUnauthorized();
        $this->getJson('/api/mobile/account/subscriptions')->assertUnauthorized();
    }

    public function test_a_token_grants_access_to_protected_routes(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $token = $this->postJson('/api/mobile/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->json('token');

        $this->withToken($token)->getJson('/api/mobile/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->withToken($token)->getJson('/api/mobile/account/subscriptions')
            ->assertOk()
            ->assertJsonStructure(['subscriptions', 'meta']);
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $token = $this->postJson('/api/mobile/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->json('token');

        $this->withToken($token)->postJson('/api/mobile/auth/logout')->assertOk();

        $this->assertSame(0, $user->fresh()->tokens()->count());

        // In production each request is a fresh process; clear the guard memoised in this test run.
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/mobile/me')->assertUnauthorized();
    }

    public function test_mobile_token_does_not_authenticate_web_session_routes(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('phone')->plainTextToken;

        // The website's /api/me uses the session guard, not Sanctum: the token must not work there.
        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();

        // The same token works on the mobile route.
        $this->withToken($token)->getJson('/api/mobile/me')->assertOk();
    }

    public function test_password_update_via_token_revokes_other_tokens(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $keep = $user->createToken('phone')->plainTextToken;
        $other = $user->createToken('tablet')->plainTextToken;

        $this->withToken($keep)->putJson('/api/mobile/account/password', [
            'current_password' => 'password',
            'password' => 'new-password',
        ])->assertOk();

        // The token that made the change still works; the other one is revoked.
        // forgetGuards() before each check mimics a fresh request (no memoised guard user).
        $this->app['auth']->forgetGuards();
        $this->withToken($keep)->getJson('/api/mobile/me')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($other)->getJson('/api/mobile/me')->assertUnauthorized();
    }
}
