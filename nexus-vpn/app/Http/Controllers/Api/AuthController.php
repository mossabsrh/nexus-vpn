<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * Register a new user and start the 7-day trial of the chosen self-serve plan.
     */
    public function register(Request $request): JsonResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'plan_slug' => ['required', 'string', Rule::exists('plans', 'slug')->where('is_active', true)->whereNull('deleted_at')],
        ]);

        $plan = Plan::query()->active()->where('slug', $request->input('plan_slug'))->firstOrFail();

        // Team plans are set up with sales (custom seats and price), not through self sign-up.
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
            Log::error('Registration failed.', ['email_domain' => Str::after($request->email, '@'), 'exception' => $exception]);

            return response()->json([
                'success' => false,
                'error' => 'Registration failed. Please try again later.',
            ], 500);
        }

        Auth::login($user);
        $request->session()->regenerate();
        AuditLog::record('user.registered', $user, ['plan' => $plan->slug], $user);

        $this->sendSafely(fn () => $user->sendEmailVerificationNotification(), 'verification', $user);
        $this->sendSafely(fn () => $user->notify(new WelcomeNotification($trial->load('plan'))), 'welcome', $user);

        return response()->json([
            'success' => true,
            'user' => $this->userPayload($user),
            'message' => 'User registered successfully',
        ], 201);
    }

    /**
     * Login with per-account brute-force protection (5 failures lock the email+IP pair for a minute),
     * on top of the per-IP route throttle.
     */
    public function login(Request $request): JsonResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $throttleKey = 'login:'.Str::transliterate($request->input('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            return response()->json([
                'success' => false,
                'error' => 'Too many login attempts. Please try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ], 429);
        }

        if (! Auth::attempt($request->only('email', 'password'))) {
            RateLimiter::hit($throttleKey, 60);
            AuditLog::record('auth.login_failed', null, ['email_domain' => Str::after($request->input('email'), '@')]);

            return response()->json([
                'success' => false,
                'error' => 'Invalid email or password.',
            ], 401);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        $user = $request->user();

        if ($user->isAdmin()) {
            AuditLog::record('auth.admin_login', $user, [], $user);
        }

        return response()->json([
            'success' => true,
            'user' => $this->userPayload($user),
            'message' => 'Logged in successfully',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function updateEmail(Request $request): JsonResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
        ]);

        $user = $request->user();

        if ($validated['email'] !== $user->email) {
            $previousDomain = Str::after($user->email, '@');
            $user->forceFill(['email' => $validated['email'], 'email_verified_at' => null])->save();
            AuditLog::record('account.email_changed', $user, ['previous_domain' => $previousDomain], $user);
            $this->sendSafely(fn () => $user->sendEmailVerificationNotification(), 'verification', $user);
        }

        return response()->json(['user' => $this->userPayload($user->refresh())]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Rules\Password::defaults()],
        ]);

        $user = $request->user();
        $user->update(['password' => Hash::make($validated['password'])]);

        // Sign out every other session of this account.
        DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
        AuditLog::record('account.password_changed', $user, [], $user);

        return response()->json(['message' => 'Password updated successfully']);
    }

    /**
     * Permanently close the account: scrub the personal data, revoke access and sign out everywhere.
     *
     * Payment and subscription rows are kept (anonymised) for billing history; the user row is
     * soft-deleted so it can no longer authenticate and the original e-mail is freed for reuse.
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Admins and team owners cannot self-delete: it would remove privileged access or cut off
        // a whole team's seats. These are handled by support instead.
        if ($user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin accounts cannot be deleted from here. Please contact support.',
                'code' => 'admin_account',
            ], 409);
        }

        if ($user->ownedTeams()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You own a team subscription. Please contact us so we can transfer or close it first.',
                'code' => 'team_owner',
            ], 409);
        }

        $user->closeAccount($user);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['success' => true, 'message' => 'Your account has been permanently deleted.']);
    }

    /**
     * Always answers the same way so the endpoint cannot be used to discover registered emails.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return response()->json(['message' => 'Please wait before requesting another reset link.'], 429);
        }

        return response()->json(['message' => 'If an account exists for that email, a reset link has been sent.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email')))]);
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                DB::table('sessions')->where('user_id', $user->id)->delete();
                AuditLog::record('account.password_reset', $user, [], $user);
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => 'This password reset link is invalid or has expired.'], 422);
        }

        return response()->json(['message' => 'Your password has been reset. You can now sign in.']);
    }

    /**
     * Signed link from the verification email. Works without a session (the link may be opened on another device).
     */
    public function verifyEmail(Request $request, string $id, string $hash): JsonResponse
    {
        $user = User::query()->find($id);

        if (! $user || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json(['message' => 'This verification link is invalid.'], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            AuditLog::record('account.email_verified', $user, [], $user);
        }

        return response()->json(['message' => 'Email verified.', 'verified' => true]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Your email is already verified.']);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent.']);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * @return array<string, mixed>
     */
    public static function userPayload(User $user): array
    {
        $current = $user->currentSubscription();
        $access = $user->accessSubscription();
        $latest = $user->subscriptions()->with('plan')->latest('id')->first();
        $team = $access?->team ?? $user->teams()->with('subscription')->first();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ?? 'user',
            'emailVerified' => $user->hasVerifiedEmail(),
            'planId' => $current?->plan?->slug,
            'subscriptionStatus' => $current?->status ?? $latest?->status,
            'lastPlanId' => $latest?->plan?->slug,
            'subscription' => ($current ?? $access ?? $latest)?->toApiArray(),
            'hasVpnAccess' => $access !== null,
            'team' => $team ? [
                'id' => $team->id,
                'name' => $team->name,
                'isOwner' => $team->owner_id === $user->id,
            ] : null,
        ];
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
