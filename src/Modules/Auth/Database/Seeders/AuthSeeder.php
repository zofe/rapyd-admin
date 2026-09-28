<?php

namespace Zofe\Rapyd\Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

        $this->seedAdminUser();
    }

    /**
     * The first super admin, created once with a password nobody else knows: a random
     * one printed on the console (or RAPYD_ADMIN_PASSWORD). Never in production, never
     * again once the account exists, so re-running the seeder — every rpd:make does,
     * to refresh roles and permissions — cannot reset it or bring it back.
     */
    protected function seedAdminUser(): void
    {
        if (app()->environment('production') || ! config('rapyd.auth.seed_admin', true)) {
            return;
        }

        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        $email = config('rapyd.auth.admin_email', 'admin@laravel');
        if ($userModel::where('email', $email)->exists()) {
            return;
        }

        $password = env('RAPYD_ADMIN_PASSWORD') ?: Str::password(16, symbols: false);
        $admin = $userModel::create(['name' => 'Admin', 'email' => $email, 'password' => Hash::make($password)]);
        $admin->assignRole('admin');

        $this->command?->newLine();
        $this->command?->warn("Admin user created: {$email} / {$password}");
        $this->command?->warn('This password is shown once: change it after the first login.');
    }
}
