<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DsrRoleSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create DSR-specific permissions if they don't exist
        $dsrPermissions = [
            'view staff dashboard',
            'view customer ledgers',
            'view assigned categories',
            'create sales',
            'view sales history',
            'manage returns',
            'view daily reports'
        ];

        foreach ($dsrPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create DSR role and assign permissions
        $dsr = Role::firstOrCreate(['name' => 'dsr']);
        $dsr->syncPermissions([
            'view staff dashboard',
            'view customer ledgers',
            'view assigned categories',
            'create sales',
            'view sales history',
            'manage returns',
            'view daily reports'
        ]);
    }
}
