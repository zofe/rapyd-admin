<?php

namespace App\Modules\Auth\Database\Seeders;

use App\Modules\Auth\Models\Permission;
use App\Modules\Auth\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach (config('permission.permissions', []) as $permission) {
            Permission::firstOrNew(['name' => $permission])->save();
        }

        foreach (config('permission.roles', []) as $role) {
            $adminRole = Role::firstOrNew(['name' => $role]);
            $adminRole->save();
            $adminRole->givePermissionTo(config('permission.role_permissions.' . $role));
        }

        $this->seedAdminUser();
    }

    /**
     * The first super admin, created once with a password nobody else knows: a random
     * one printed on the console (or RAPYD_ADMIN_PASSWORD). Never in production, never
     * again once the account exists, so re-running the seeder cannot reset it.
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
