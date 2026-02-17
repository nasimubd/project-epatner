<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'manage roles',
            'manage permissions',
            'create users',
            'edit users',
            'delete users',
            'view users',
            'manage business',
            'assign staff permissions',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'manage business',
            'assign staff permissions',
            'create users',
            'edit users',
            'view users'
        ]);


        $staff = Role::create(['name' => 'staff']);
        $staff->givePermissionTo([
            'view staff dashboard',
            'view customer ledgers',
            'view assigned categories',
            'view users'
        ]);
    }
}
