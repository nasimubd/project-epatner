<?php

namespace Database\Factories;

use App\Models\BusinessAdmin;
use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusinessAdminFactory extends Factory
{
    protected $model = BusinessAdmin::class;

    public function definition()
    {
        return [
            'business_id' => Business::factory(),
            'user_id' => User::factory()->create(['role' => 'business-admin']),
        ];
    }
}
