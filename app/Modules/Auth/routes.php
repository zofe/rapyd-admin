<?php

use App\Modules\Auth\Livewire\PermissionsEdit;
use App\Modules\Auth\Livewire\PermissionsTable;
use App\Modules\Auth\Livewire\UsersTable;
use App\Modules\Auth\Livewire\UsersView;
use App\Modules\Auth\Livewire\UsersEdit;
use App\Modules\Auth\Services\SocialiteService;

use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::get('auth/users', UsersTable::class)
    ->middleware(['web'])
    ->name('auth.users')
    ->crumbs(fn ($crumbs) => $crumbs->push('Users', route_lang('auth.users')));

Route::get('auth/users/view/{user:id}', UsersView::class)
    ->middleware(['web'])
    ->name('auth.users.view')
    ->crumbs(function ($crumbs, $user) {
        $crumbs->parent('auth.users')->push('View User', route_lang('auth.users.view', $user));
    });

Route::get('auth/users/edit/{user:id?}', UsersEdit::class)
    ->middleware(['web'])
    ->name('auth.users.edit')
    ->crumbs(function ($crumbs, $user = null) {
        if ($user) {
            $crumbs->parent('auth.users.view', $user)
                ->push('Edit User', route_lang('auth.users.edit', $user));
        } else {
            $crumbs->parent('auth.users')
                ->push('Add User', route_lang('auth.users.edit'));
        }
    });

Route::get('auth/permissions', PermissionsTable::class)
    ->middleware(['web'])
    ->name('auth.permissions')
    ->crumbs(fn ($crumbs) => $crumbs->push('Permissions', route_lang('auth.permissions')));

Route::get('auth/permissions/edit/{role:id}', PermissionsEdit::class)
    ->middleware(['web'])
    ->name('auth.permissions.edit')
    ->crumbs(function ($crumbs, $role) {
        $crumbs->parent('auth.permissions')
            ->push('Edit User', route_lang('auth.permissions.edit', $role));
    });



Route::impersonate();

Route::get('/google/auth/redirect', function () {
    return Socialite::driver('google')->redirect();
})->name('google.redirect');

Route::get('/google/auth/callback', function () {
    try {
        $payload = Socialite::driver('google')->stateless()->user();
    } catch (\Exception $e) {
        return redirect('/login');
    }
    return SocialiteService::loginOrRegister('google', $payload);
});



