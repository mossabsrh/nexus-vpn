<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word());

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'name' => $name,
            'description' => fake()->sentence(),
            'monthly_price' => 1500,
            'yearly_price' => 750,
            'yearly_total' => 9000,
            'currency' => 'DZD',
            'features' => ['Unlimited devices'],
            'popular' => false,
            'cta' => 'Start trial',
            'is_active' => true,
        ];
    }

    /**
     * Sales-assisted plan without a public price (like Team).
     */
    public function salesAssisted(): static
    {
        return $this->state(fn () => ['monthly_price' => null, 'yearly_price' => null, 'yearly_total' => null, 'cta' => 'Contact us']);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
