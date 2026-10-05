<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PricingController extends Controller
{
    public const CACHE_KEY = 'pricing.plans';

    /**
     * Public plan list. Cached briefly and invalidated whenever an admin changes a plan,
     * so the pricing page never shows prices that differ from what checkout charges.
     */
    public function index(): JsonResponse
    {
        $plans = Cache::remember(self::CACHE_KEY, now()->addMinutes(10), fn () => Plan::query()
            ->active()
            ->orderByRaw('monthly_price is null')
            ->orderBy('monthly_price')
            ->get()
            ->map(fn (Plan $plan) => [
                'slug' => $plan->slug,
                'name' => $plan->name,
                'description' => $plan->description,
                'monthlyPrice' => $plan->monthly_price,
                'yearlyPrice' => $plan->yearly_price,
                'yearlyTotal' => $plan->yearly_total,
                'currency' => $plan->currency,
                'features' => $plan->features ?? [],
                'popular' => $plan->popular,
                'cta' => $plan->cta,
                'selfServe' => $plan->isSelfServe(),
            ])
            ->values()
            ->all());

        return response()->json(['plans' => $plans])
            ->header('Cache-Control', 'public, max-age=60');
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
