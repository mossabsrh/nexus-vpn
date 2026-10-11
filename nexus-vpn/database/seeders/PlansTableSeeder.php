<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlansTableSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'essential',
                'name' => 'Essential',
                'description' => 'Private WireGuard access for one person.',
                'monthly_price' => 1119,
                'yearly_price' => 558,
                'yearly_total' => 6704,
                'features' => json_encode(['WireGuard® VPN access', 'Encrypted traffic, hidden IP', 'No activity logs', 'Pay in DZD (EDAHABIA / CIB)', '7-day free trial', 'Email support']),
                'popular' => 0,
                'cta' => 'Start trial',
            ],
            [
                'slug' => 'standard',
                'name' => 'Standard',
                'description' => 'The recommended plan, with priority support.',
                'monthly_price' => 1679,
                'yearly_price' => 838,
                'yearly_total' => 10063,
                'features' => json_encode(['WireGuard® VPN access', 'Encrypted traffic, hidden IP', 'No activity logs', 'Pay in DZD (EDAHABIA / CIB)', '7-day free trial', 'Priority support']),
                'popular' => 1,
                'cta' => 'Start trial',
            ],
            [
                'slug' => 'team',
                'name' => 'Team',
                'description' => 'Centralized billing for 5–50 seats.',
                'monthly_price' => null,
                'yearly_price' => null,
                'yearly_total' => null,
                'features' => json_encode(['Everything in Standard', 'From 5 to 50 seats', 'Centralized team dashboard', 'Add & remove members', 'Centralized billing', 'Dedicated support']),
                'popular' => 0,
                'cta' => 'Contact us',
            ],
        ];

        // Only adds missing plans: the container seeds on every start, and plans edited or
        // deleted by admins must stay as the admins left them.
        foreach ($plans as $plan) {
            if (DB::table('plans')->where('slug', $plan['slug'])->exists()) {
                continue;
            }

            DB::table('plans')->insert([
                ...$plan,
                'currency' => 'DZD',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
