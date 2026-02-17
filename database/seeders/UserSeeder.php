<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create Super Admin
        $superAdmin = User::create([
            'name' => 'ePATNER-Dev',
            'email' => 'begold@epatner.com',
            'password' => Hash::make('Subd1050')
        ]);
        $superAdmin->assignRole('super-admin');
    }
}
