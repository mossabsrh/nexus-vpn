<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function users(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(['user', 'admin'])],
            'subscription' => ['nullable', Rule::in(['active', 'trialing', 'none'])],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $users = User::query()
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.str_replace(['%', '_'], ['\%', '\_'], strtolower($request->input('q'))).'%';
                $query->where(fn ($inner) => $inner->whereRaw('lower(name) like ?', [$term])->orWhereRaw('lower(email) like ?', [$term]));
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->input('role')))
            ->when($request->input('subscription') === 'none', fn ($query) => $query->whereDoesntHave('subscriptions', fn ($sub) => $sub->current()))
            ->when(in_array($request->input('subscription'), ['active', 'trialing'], true), fn ($query) => $query->whereHas('subscriptions', fn ($sub) => $sub->where('status', $request->input('subscription'))))
            ->with(['subscriptions' => fn ($query) => $query->current()->with('plan')])
            ->latest('id')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'users' => $users->getCollection()->map(function (User $user) {
                $subscription = $user->subscriptions->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'emailVerified' => $user->hasVerifiedEmail(),
                    'created_at' => $user->created_at?->toISOString(),
                    'subscription' => $subscription ? [
                        'status' => $subscription->status,
                        'planId' => $subscription->plan?->slug,
                        'currentPeriodEnd' => $subscription->current_period_end?->toISOString(),
                    ] : null,
                ];
            }),
            'meta' => self::meta($users),
        ]);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['sometimes', Rule::in(['user', 'admin'])],
        ]);

        if ($user->is($request->user()) && ($validated['role'] ?? $user->role) !== 'admin') {
            return response()->json(['message' => 'You cannot remove your own admin access.'], 422);
        }

        $before = $user->only(array_keys($validated));
        $user->update($validated);
        $changes = array_keys($user->getChanges());

        if ($changes !== []) {
            AuditLog::record('admin.user_updated', $user, [
                'fields' => array_values(array_diff($changes, ['updated_at'])),
                'role_from' => $before['role'] ?? null,
                'role_to' => $validated['role'] ?? null,
            ]);
        }

        return response()->json(['user' => $user->fresh()]);
    }

    public function plans(Request $request): JsonResponse
    {
        $plans = Plan::query()
            ->withCount(['subscriptions as active_subscriptions_count' => fn ($query) => $query->current()])
            ->orderByDesc('is_active')
            ->latest('id')
            ->paginate(min(max($request->integer('per_page', 50), 5), 100));

        return response()->json(['plans' => $plans->items(), 'meta' => self::meta($plans)]);
    }

    public function storePlan(Request $request): JsonResponse
    {
        $plan = Plan::create($this->validatePlan($request));
        PricingController::flushCache();
        AuditLog::record('admin.plan_created', $plan, ['slug' => $plan->slug]);

        return response()->json(['plan' => $plan->fresh()], 201);
    }

    public function updatePlan(Request $request, Plan $plan): JsonResponse
    {
        $plan->update($this->validatePlan($request, $plan));
        PricingController::flushCache();
        AuditLog::record('admin.plan_updated', $plan, ['slug' => $plan->slug, 'fields' => array_keys($plan->getChanges())]);

        return response()->json(['plan' => $plan->fresh()]);
    }

    /**
     * Plans referenced by subscriptions are part of billing history: deactivate them instead.
     */
    public function destroyPlan(Plan $plan): JsonResponse
    {
        if ($plan->subscriptions()->withTrashed()->exists()) {
            return response()->json(['message' => 'This plan has subscriptions. Deactivate it instead to hide it from new customers.'], 409);
        }

        $plan->delete();
        PricingController::flushCache();
        AuditLog::record('admin.plan_deleted', $plan, ['slug' => $plan->slug]);

        return response()->json(['message' => 'Plan deleted.']);
    }

    public function testimonials(Request $request): JsonResponse
    {
        $request->validate(['status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])]]);

        $testimonials = Testimonial::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->with('user:id,name,email')
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'testimonials' => $testimonials->getCollection()->map(fn (Testimonial $testimonial) => [
                'id' => $testimonial->id,
                'content' => $testimonial->content,
                'status' => $testimonial->status,
                'name' => $testimonial->user?->name,
                'email' => $testimonial->user?->email,
                'createdAt' => $testimonial->created_at?->toISOString(),
                'moderatedAt' => $testimonial->moderated_at?->toISOString(),
            ]),
            'meta' => self::meta($testimonials),
            'pendingCount' => Testimonial::query()->where('status', 'pending')->count(),
        ]);
    }

    public function moderateTestimonial(Request $request, Testimonial $testimonial): JsonResponse
    {
        $validated = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);

        $testimonial->update([
            'status' => $validated['status'],
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);
        AuditLog::record('admin.testimonial_'.$validated['status'], $testimonial);

        return response()->json(['testimonial' => ['id' => $testimonial->id, 'status' => $testimonial->status]]);
    }

    public function destroyTestimonial(Testimonial $testimonial): JsonResponse
    {
        $testimonial->delete();
        AuditLog::record('admin.testimonial_deleted', $testimonial);

        return response()->json(['message' => 'Testimonial deleted.']);
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $request->validate(['action' => ['nullable', 'string', 'max:100']]);

        $logs = AuditLog::query()
            ->when($request->filled('action'), fn ($query) => $query->where('action', 'like', str_replace(['%', '_'], ['\%', '\_'], $request->input('action')).'%'))
            ->with('actor:id,name,email')
            ->latest('id')
            ->paginate(30);

        return response()->json([
            'logs' => $logs->getCollection()->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'subjectType' => $log->subject_type,
                'subjectId' => $log->subject_id,
                'metadata' => $log->metadata,
                'actor' => $log->actor ? ['id' => $log->actor->id, 'name' => $log->actor->name, 'email' => $log->actor->email] : null,
                'ipAddress' => $log->ip_address,
                'createdAt' => $log->created_at?->toISOString(),
            ]),
            'meta' => self::meta($logs),
        ]);
    }

    /**
     * @return array<string, int>
     */
    public static function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    private function validatePlan(Request $request, ?Plan $plan = null): array
    {
        return $request->validate([
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', Rule::unique('plans')->ignore($plan?->id)],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'monthly_price' => ['nullable', 'integer', 'min:0'],
            'yearly_price' => ['nullable', 'integer', 'min:0'],
            'yearly_total' => ['nullable', 'integer', 'min:0'],
            'currency' => ['required', 'string', Rule::in(['DZD'])],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
            'popular' => ['boolean'],
            'cta' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
