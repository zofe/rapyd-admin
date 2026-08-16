<?php

namespace Zofe\Rapyd\Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        $admin = $userModel::firstOrCreate(
            ['email' => 'admin@laravel'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin'),
            ]
        );

        $admin->assignRole($adminRole);
    }
}
