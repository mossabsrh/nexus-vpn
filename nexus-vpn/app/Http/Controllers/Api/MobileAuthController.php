<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Token authentication for the mobile (Flutter) client.
 *
 * It mirrors the web {@see AuthController} but issues a Sanctum personal access token
 * instead of starting a cookie session, so it carries no CSRF requirement. The website's
 * own session+CSRF login is untouched.
 */
class MobileAuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Register a new user, start the 7-day trial of the chosen self-serve plan, and return a token.
     */
    public function register(Request $request): JsonResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'plan_slug' => ['required', 'string', Rule::exists('plans', 'slug')->where('is_active', true)->whereNull('deleted_at')],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $plan = Plan::query()->active()->where('slug', $request->input('plan_slug'))->firstOrFail();

        // Team plans are arranged with sales (custom seats and price), not through self sign-up.
        if (! $plan->isSelfServe()) {
            throw ValidationException::withMessages([
                'plan_slug' => 'The '.$plan->name.' plan is arranged with our sales team. Choose another plan or contact us.',
            ]);
        }

        try {
            [$user, $trial] = DB::transaction(function () use ($request, $plan): array {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);

                $trialEndsAt = now()->addDays(7);
                $trial = $user->subscriptions()->create([
                    'plan_id' => $plan->id,
                    'seats' => 1,
                    'billing_period' => 'monthly',
                    'status' => 'trialing',
                    'started_at' => now(),
                    'trial_ends_at' => $trialEndsAt,
                    'current_period_end' => $trialEndsAt,
                ]);

                return [$user, $trial];
            });
        } catch (Throwable $exception) {
            Log::error('Mobile registration failed.', ['email_domain' => Str::after($request->email, '@'), 'exception' => $exception]);

            return response()->json([
                'success' => false,
                'error' => 'Registration failed. Please try again later.',
            ], 500);
        }

        AuditLog::record('user.registered', $user, ['plan' => $plan->slug, 'via' => 'mobile'], $user);

        $this->sendSafely(fn () => $user->sendEmailVerificationNotification(), 'verification', $user);
        $this->sendSafely(fn () => $user->notify(new WelcomeNotification($trial->load('plan'))), 'welcome', $user);

        return response()->json([
            'success' => true,
            'token' => $this->issueToken($user, $request),
            'user' => AuthController::userPayload($user),
            'message' => 'User registered successfully',
        ], 201);
    }

    /**
     * Log in and return a token. Shares the same per-account brute-force protection as the web login
     * (5 failures lock the email+IP pair for a minute), on top of the per-IP route throttle.
     */
    public function login(Request $request): JsonResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $throttleKey = 'login:'.Str::transliterate($request->input('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            return response()->json([
                'success' => false,
                'error' => 'Too many login attempts. Please try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ], 429);
        }

        $user = User::query()->where('email', $request->input('email'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            AuditLog::record('auth.login_failed', null, ['email_domain' => Str::after($request->input('email'), '@'), 'via' => 'mobile']);

            return response()->json([
                'success' => false,
                'error' => 'Invalid email or password.',
            ], 401);
        }

        RateLimiter::clear($throttleKey);

        if ($user->isAdmin()) {
            AuditLog::record('auth.admin_login', $user, ['via' => 'mobile'], $user);
        }

        return response()->json([
            'success' => true,
            'token' => $this->issueToken($user, $request),
            'user' => AuthController::userPayload($user),
            'message' => 'Logged in successfully',
        ]);
    }

    /**
     * Revoke the token used for the current request (sign out this device only).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    private function issueToken(User $user, Request $request): string
    {
        $device = (string) ($request->input('device_name') ?: 'mobile');

        return $user->createToken($device)->plainTextToken;
    }

    private function sendSafely(callable $send, string $kind, User $user): void
    {
        try {
            $send();
        } catch (Throwable $exception) {
            Log::error('Account email could not be sent.', ['kind' => $kind, 'user_id' => $user->id, 'exception' => $exception->getMessage()]);
        }
    }
}
