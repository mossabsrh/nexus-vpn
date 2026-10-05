<?php

namespace Tests\Feature\Api;

use App\Models\Plan;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use RuntimeException;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.frontend_url' => 'https://frontend.test']);
    }

    public function test_login_locks_the_account_after_five_failed_attempts(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnauthorized();
        }

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(429);
        $this->assertGuest();
    }

    public function test_successful_login_clears_failed_attempts(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 4) as $attempt) {
            $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
    }

    public function test_registration_failure_does_not_leak_internal_errors(): void
    {
        $plan = Plan::factory()->create();
        User::creating(fn () => throw new RuntimeException('SQLSTATE[HY000]: secret internal detail'));

        $this->postJson('/api/auth/register', [
            'name' => 'Jane', 'email' => 'jane@example.com', 'password' => 'password',
            'password_confirmation' => 'password', 'plan_slug' => $plan->slug,
        ])->assertStatus(500)
            ->assertJsonPath('error', 'Registration failed. Please try again later.')
            ->assertDontSee('SQLSTATE');
    }

    public function test_registration_rejects_sales_assisted_plans_explicitly(): void
    {
        $team = Plan::factory()->salesAssisted()->create(['slug' => 'team', 'name' => 'Team']);

        $this->postJson('/api/auth/register', [
            'name' => 'Jane', 'email' => 'jane@example.com', 'password' => 'password',
            'password_confirmation' => 'password', 'plan_slug' => $team->slug,
        ])->assertJsonValidationErrors('plan_slug');

        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
    }

    public function test_registration_sends_verification_and_welcome_emails(): void
    {
        Notification::fake();
        $plan = Plan::factory()->create();

        $this->postJson('/api/auth/register', [
            'name' => 'Jane', 'email' => 'Jane@Example.com', 'password' => 'password',
            'password_confirmation' => 'password', 'plan_slug' => $plan->slug,
        ])->assertCreated()
            ->assertJsonPath('user.email', 'jane@example.com')
            ->assertJsonPath('user.emailVerified', false)
            ->assertJsonPath('user.subscription.status', 'trialing');

        $user = User::query()->where('email', 'jane@example.com')->sole();
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user) {
            return str_starts_with($notification->toMail($user)->actionUrl, 'https://frontend.test/verify-email?');
        });
        Notification::assertSentTo($user, WelcomeNotification::class);
    }

    public function test_email_change_returns_fresh_data_and_requires_reverification(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->putJson('/api/account/email', [
            'current_password' => 'password', 'email' => 'new@example.com',
        ])->assertOk()
            ->assertJsonPath('user.email', 'new@example.com')
            ->assertJsonPath('user.emailVerified', false);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_signed_verification_link_verifies_the_email(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $user->sendEmailVerificationNotification();

        $url = null;
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification) use ($user, &$url) {
            $url = $notification->toMail($user)->actionUrl;

            return true;
        });
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $this->getJson("/api/email/verify/{$query['id']}/{$query['hash']}?expires={$query['expires']}&signature=bad")
            ->assertForbidden();
        $this->getJson("/api/email/verify/{$query['id']}/{$query['hash']}?expires={$query['expires']}&signature={$query['signature']}")
            ->assertOk();

        $this->assertTrue($user->refresh()->hasVerifiedEmail());
    }

    public function test_forgot_password_does_not_reveal_whether_an_account_exists(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $known = $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk()->json('message');
        $unknown = $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@example.com'])->assertOk()->json('message');

        $this->assertSame($known, $unknown);
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            return str_starts_with($notification->toMail($user)->actionUrl, 'https://frontend.test/reset-password?token=');
        });
    }

    public function test_password_can_be_reset_with_a_valid_token_only(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->postJson('/api/auth/reset-password', [
            'token' => 'invalid', 'email' => $user->email, 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertStatus(422);

        $this->postJson('/api/auth/reset-password', [
            'token' => $token, 'email' => $user->email, 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertOk();

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'new-password'])->assertOk();
    }

    public function test_api_responses_carry_security_headers_and_pricing_has_no_wildcard_cors(): void
    {
        Plan::factory()->create(['slug' => 'visible']);
        Plan::factory()->inactive()->create(['slug' => 'hidden']);

        $response = $this->getJson('/api/pricing')->assertOk();

        $response->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'")
            ->assertHeaderMissing('Access-Control-Allow-Origin');
        $this->assertSame(['visible'], array_column($response->json('plans'), 'slug'));
    }
}
