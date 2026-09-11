<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Livewire\Livewire;
use Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder;
use Zofe\Rapyd\Modules\Companies\Models\Company;
use Zofe\Rapyd\Tests\Models\User;
use Zofe\Rapyd\Tests\TestCase;

class AuthUsersTest extends TestCase
{
    use DatabaseMigrations;

    protected User $admin;
    protected Company $acme;
    protected Company $globex;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AuthSeeder::class);
        $this->admin = User::where('email', 'admin@laravel')->firstOrFail();
        $this->actingAs($this->admin);

        $this->acme = Company::create(['business_name' => 'Acme Corp', 'status' => 'active']);
        $this->globex = Company::create(['business_name' => 'Globex', 'status' => 'active']);
    }

    protected function makeUser(string $name, string $email): User
    {
        return User::create(['name' => $name, 'email' => $email, 'password' => 'secret']);
    }

    public function test_table_shows_company_and_owner_badge()
    {
        $this->makeUser('Mario', 'mario@example.com')->assignToCompany($this->acme, 'owner');
        $this->makeUser('Anna', 'anna@example.com')->assignToCompany($this->globex, 'member');

        Livewire::test('auth::users-table')
            ->assertSeeInOrder(['Anna', 'Globex'])
            ->assertSeeInOrder(['Mario', 'Acme Corp', 'Owner']);
    }

    public function test_view_shows_company_card()
    {
        $user = $this->makeUser('Mario', 'mario@example.com');
        $user->assignToCompany($this->acme, 'owner');

        Livewire::test('auth::users-view', ['user' => $user])
            ->assertSee('Acme Corp')
            ->assertSee('Owner');

        Livewire::test('auth::users-view', ['user' => $this->admin])
            ->assertSee('No company assigned');
    }

    public function test_create_user_with_company_and_role()
    {
        Livewire::test('auth::users-edit')
            ->set('user.name', 'Mario Rossi')
            ->set('user.email', 'mario@example.com')
            ->set('psswd', 'password123')
            ->set('user.company_id', $this->acme->id)
            ->set('user.company_role', 'owner')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $user = User::where('email', 'mario@example.com')->firstOrFail();
        $this->assertTrue($user->isOwnerOf($this->acme));
        $this->assertEquals($this->acme->id, $user->company->id);
    }

    public function test_role_is_required_when_a_company_is_set()
    {
        Livewire::test('auth::users-edit')
            ->set('user.name', 'Mario Rossi')
            ->set('user.email', 'mario@example.com')
            ->set('psswd', 'password123')
            ->set('user.company_id', $this->acme->id)
            ->set('user.company_role', 'boss')
            ->call('save')
            ->assertHasErrors(['user.company_role']);
    }

    public function test_super_admin_can_move_user_to_another_company()
    {
        $user = $this->makeUser('Mario', 'mario@example.com');
        $user->assignToCompany($this->acme, 'owner');

        Livewire::test('auth::users-edit', ['user' => $user])
            ->set('user.company_id', $this->globex->id)
            ->set('user.company_role', 'member')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals($this->globex->id, $user->company_id);
        $this->assertEquals('member', $user->company_role);
    }

    public function test_clearing_the_company_removes_the_membership()
    {
        $user = $this->makeUser('Mario', 'mario@example.com');
        $user->assignToCompany($this->acme, 'owner');

        Livewire::test('auth::users-edit', ['user' => $user])
            ->set('user.company_id', '')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertNull($user->company_id);
        $this->assertNull($user->company_role);
    }

    public function test_operator_cannot_move_an_existing_user()
    {
        $operator = $this->makeUser('Op', 'op@example.com');
        $operator->assignRole('operator');
        $this->actingAs($operator);

        $user = $this->makeUser('Mario', 'mario@example.com');
        $user->assignToCompany($this->acme, 'owner');

        Livewire::test('auth::users-edit', ['user' => $user])
            ->assertSee('Acme Corp')
            ->set('user.company_id', $this->globex->id)
            ->set('user.company_role', 'member')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertEquals($this->acme->id, $user->company_id);
        $this->assertEquals('owner', $user->company_role);
    }

    public function test_operator_can_only_assign_companies_he_sees()
    {
        $operator = $this->makeUser('Op', 'op@example.com');
        $operator->assignRole('operator');
        $operator->assignToCompany($this->acme, 'member');
        $this->actingAs($operator);

        Livewire::test('auth::users-edit')
            ->assertSee('Acme Corp')
            ->assertDontSee('Globex')
            ->set('user.name', 'Mario Rossi')
            ->set('user.email', 'mario@example.com')
            ->set('psswd', 'password123')
            ->set('user.company_id', $this->globex->id)
            ->set('user.company_role', 'member')
            ->call('save')
            ->assertHasErrors(['user.company_id']);
    }

    public function test_company_page_edits_only_the_role_of_a_member()
    {
        $user = $this->makeUser('Mario', 'mario@example.com');
        $user->assignToCompany($this->acme, 'member');

        Livewire::test('companies::users-modal-edit-embed')
            ->call('editUser', $user->id, $this->acme->id)
            ->assertSet('role', 'member')
            ->set('role', 'owner')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($user->fresh()->isOwnerOf($this->acme));

        // Not a member of Globex: saving from that page does not move him.
        Livewire::test('companies::users-modal-edit-embed')
            ->call('editUser', $user->id, $this->globex->id)
            ->set('role', 'member')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals($this->acme->id, $user->fresh()->company_id);
    }
}
