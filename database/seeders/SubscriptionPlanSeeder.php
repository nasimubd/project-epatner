<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    public function run()
    {
        SubscriptionPlan::create([
            'name' => 'Basic-Monthly',
            'price' => 999,
            'duration_days' => 30,
            'is_active' => true
        ]);

        SubscriptionPlan::create([
            'name' => 'Advanced-Monthly',
            'price' => 1999,
            'duration_days' => 30,
            'is_active' => true
        ]);

        SubscriptionPlan::create([
            'name' => 'Advanced-Yearly',
            'price' => 22222,
            'duration_days' => 365,
            'is_active' => true
        ]);

        SubscriptionPlan::create([
            'name' => 'Basic-Yearly',
            'price' => 9999,
            'duration_days' => 365,
            'is_active' => true
        ]);
    }
}
