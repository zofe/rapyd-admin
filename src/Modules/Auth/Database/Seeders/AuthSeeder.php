<?php

namespace Zofe\Rapyd\Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AuthSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = config('auth.defaults.guard', 'web');

        foreach (config('auth.permissions', []) as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
        }

        $roles = array_unique(array_merge(['admin'], config('auth.roles', [])));
        foreach ($roles as $name) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
            $role->syncPermissions(config("auth.role_permissions.{$name}", []));
        }

        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        $admin = $userModel::firstOrCreate(
            ['email' => 'admin@laravel'],
            ['name' => 'Admin', 'password' => Hash::make('admin')]
        );
        $admin->assignRole('admin');
    }
}
