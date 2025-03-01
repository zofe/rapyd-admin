<?php

namespace App\Modules\Auth\tests\Feature;

use App\Models\User;
use App\Modules\Auth\Database\Seeders\DatabaseSeederTests;
use App\Modules\Auth\tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

class BasicPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    public function getUserByRole($slug)
    {
        return User::whereHas('roles', function ($query) use ($slug) {
            $query->whereName($slug);
        })->first();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('permission.roles',  ['admin', 'user']);
        Config::set('permission.permissions', [
            'view everything', 'edit everything', 'export everything',
            'view own business', 'edit own business',
            'view own users', 'edit own users',
            'view own tickets', 'edit own tickets',
        ]);
        Config::set('permission.role_permissions', [
            'admin' => [
                'view everything', 'edit everything', 'export everything',
            ],
            'user' => [
                'view own business', 'edit own business',
                'view own tickets', 'edit own tickets',
            ]
        ]);
        Config::set('roles.role_to_component_class',[]);

        $this->seed(DatabaseSeederTests::class);

        $this->admin = $this->getUserByRole('admin');
        $this->user = $this->getUserByRole('user');
    }

    public function test_user_can_see_login()
    {
        $this->get(route_lang('login'))
            ->assertSuccessful()
            ->assertViewIs('auth::auth.login');
    }

    public function test_can_see_livewire_component_on_permissions_page()
    {
        $this->actingAs($this->admin)
            ->get(route_lang('auth.permissions'))
            ->assertSuccessful()
            ->assertSeeLivewire('auth::admin-permissions-table');
    }

    public function test_user_cant_see_livewire_component_on_permissions_page()
    {
        $this->actingAs($this->user)
            ->get(route_lang('auth.permissions'))
            ->assertForbidden()
            ;
    }

    public function incomplete_can_change_permission_for_role()
    {
        //user crea ticket
        Livewire::actingAs($this->admin)
            ->test('auth::admin-permissions-table')
        ;

    }

}
