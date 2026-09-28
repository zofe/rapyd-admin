<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder;
use Zofe\Rapyd\Modules\Companies\Models\Company;
use Zofe\Rapyd\Tests\Models\User;
use Zofe\Rapyd\Tests\TestCase;

/**
 * One test per vulnerability reported against the stock install. The ids and the
 * values of these components all come from the browser: what a caller may do with
 * them is decided on the server, on every entry point.
 */
class SecurityTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthSeeder::class);
    }

    protected function member(Company $company, string $role = 'member', string $email = 'member@acme.test'): User
    {
        $user = User::create(['name' => 'Member', 'email' => $email, 'password' => Hash::make('secret-password')]);
        $user->assignRole('customer');          // the role that carries "edit own users"
        $user->assignToCompany($company, $role);

        return $user;
    }

    protected function company(string $name = 'Acme'): Company
    {
        return Company::create(['business_name' => $name, 'status' => 'active']);
    }

    /** 1. A member of a company must not reach another account by id. */
    public function test_a_company_member_cannot_take_over_another_account()
    {
        $acme = $this->company();
        $member = $this->member($acme);
        $owner = $this->member($acme, 'owner', 'owner@acme.test');
        $stranger = $this->member($this->company('Globex'), 'owner', 'owner@globex.test');

        $this->actingAs($member);

        // the owner of his own company, an account of another tenant, the super admin
        foreach ([$owner, $stranger, User::where('email', 'admin@laravel')->firstOrFail()] as $victim) {
            Livewire::test('companies::users-modal-edit-embed')
                ->call('editUser', $victim->getKey(), $acme->getKey())
                ->assertStatus(403);

            Livewire::test('companies::users-modal-edit-embed')
                ->call('deleteUser', $victim->getKey())
                ->assertStatus(403);
        }
        $this->assertDatabaseCount('users', 4);

        // his own row is his: he may open it, but not promote himself to owner
        Livewire::test('companies::users-modal-edit-embed')
            ->call('editUser', $member->getKey(), $acme->getKey())
            ->assertOk()
            ->set('role', 'owner')
            ->call('save')
            ->assertStatus(403);
        $this->assertSame('member', $member->fresh()->company_role);

        // and he does not create accounts, in his company or anywhere else
        Livewire::test('companies::users-modal-edit-embed')
            ->call('editUser', null, $acme->getKey())
            ->assertStatus(403);
    }

    /** 1b. The owner administers his own company, and only that one. */
    public function test_an_owner_administers_his_own_company_only()
    {
        $acme = $this->company();
        $owner = $this->member($acme, 'owner', 'owner@acme.test');
        $mate = $this->member($acme);
        $stranger = $this->member($this->company('Globex'), 'member', 'member@globex.test');

        $this->actingAs($owner);

        Livewire::test('companies::users-modal-edit-embed')
            ->call('editUser', $mate->getKey(), $acme->getKey())
            ->assertOk()
            ->set('user.name', 'Renamed')
            ->call('save')
            ->assertOk();
        $this->assertSame('Renamed', $mate->fresh()->name);

        Livewire::test('companies::users-modal-edit-embed')
            ->call('editUser', $stranger->getKey(), $stranger->company_id)
            ->assertStatus(403);

        // a new account lands in his company, whatever company_id the browser sent
        Livewire::test('companies::users-modal-edit-embed')
            ->call('editUser', null, $stranger->company_id)
            ->assertOk()
            ->set('user.name', 'New')
            ->set('user.email', 'new@acme.test')
            ->set('passwd', 'a-long-password')
            ->set('role', 'member')
            ->call('save')
            ->assertOk();
        $this->assertSame($acme->getKey(), User::where('email', 'new@acme.test')->firstOrFail()->company_id);
    }

    /** 1c. The icons follow the same rule as the component. */
    public function test_the_company_page_shows_the_icons_only_on_the_rows_the_viewer_may_touch()
    {
        $acme = $this->company();
        $member = $this->member($acme);
        $owner = $this->member($acme, 'owner', 'owner@acme.test');

        $this->actingAs($member);
        $html = view('companies::users_table_embed', ['users' => $acme->users()->get(), 'company' => $acme, 'editable' => true])->render();

        $this->assertStringContainsString('editUser', $html);
        $this->assertStringContainsString($member->getKey(), $html, 'his own row is editable');
        $this->assertStringNotContainsString($owner->getKey(), $html, "the owner's row is not");
        $this->assertStringNotContainsString('deleteUser', $html, 'nothing to delete, not even himself');
    }

    /** 2. An operator must not grant himself a super-admin role. */
    public function test_an_operator_cannot_grant_himself_the_admin_role()
    {
        $operator = User::create(['name' => 'Op', 'email' => 'op@acme.test', 'password' => Hash::make('secret-password')]);
        $operator->assignRole('operator');      // carries "edit users"
        $adminRoleId = \Spatie\Permission\Models\Role::where('name', 'admin')->firstOrFail()->id;

        $this->actingAs($operator);

        // the role is not even offered
        $component = Livewire::test('auth::users-edit', ['user' => $operator]);
        $this->assertNotContains('admin', $component->get('available_roles'));

        // and sending its id anyway is refused
        $component->set('roles', [$adminRoleId])->call('save')->assertStatus(403);
        $this->assertFalse($operator->fresh()->hasRole('admin'));
    }

    /** 2b. And must not edit a super admin: that would reset his password. */
    public function test_an_operator_cannot_edit_a_super_admin()
    {
        $operator = User::create(['name' => 'Op', 'email' => 'op@acme.test', 'password' => Hash::make('secret-password')]);
        $operator->assignRole('operator');
        $admin = User::where('email', 'admin@laravel')->firstOrFail();
        $before = $admin->password;

        $this->actingAs($operator);

        Livewire::test('auth::users-edit', ['user' => $admin])
            ->set('psswd', 'taken-over-password')
            ->call('save')
            ->assertStatus(403);

        $this->assertSame($before, $admin->fresh()->password);
    }

    /** 3. The first admin has a password nobody knows, and rpd:make does not bring him back. */
    public function test_the_seeded_admin_has_a_random_password_and_is_not_recreated()
    {
        $admin = User::where('email', 'admin@laravel')->firstOrFail();

        $this->assertFalse(Hash::check('admin', $admin->password), 'the well-known password is gone');
        $this->assertFalse(Hash::check('password', $admin->password));

        // the seeder runs again (every rpd:make does): the account is left alone
        $password = $admin->password;
        $this->seed(AuthSeeder::class);
        $this->assertSame($password, $admin->fresh()->password);

        // deleted on purpose: a generator refreshes roles and permissions with seed_admin off
        $admin->delete();
        config(['rapyd.auth.seed_admin' => false]);
        $this->seed(AuthSeeder::class);
        $this->assertDatabaseMissing('users', ['email' => 'admin@laravel']);

        // and nothing is seeded in production
        app()['env'] = 'production';
        config(['rapyd.auth.seed_admin' => true]);
        (new AuthSeeder)->setContainer(app())->run();
        $this->assertDatabaseMissing('users', ['email' => 'admin@laravel']);

        app()['env'] = 'testing';
        (new AuthSeeder)->setContainer(app())->run();
        $this->assertDatabaseHas('users', ['email' => 'admin@laravel']);
    }

    /** 4. A display name is data: it must not become markup in a select. */
    public function test_a_user_name_cannot_carry_a_script_into_a_select_list()
    {
        $payload = '<img src=x onerror=alert(document.cookie)>';

        // the real path: a name (here a company, same sink) reaches a select-list of the admin
        Company::create(['business_name' => $payload, 'status' => 'active']);
        $this->actingAs(User::where('email', 'admin@laravel')->firstOrFail());

        $html = Livewire::test('auth::users-edit')->html();

        $this->assertStringNotContainsString($payload, $html, 'never as markup');
        $this->assertStringContainsString('&lt;img src=x onerror=alert(document.cookie)&gt;', $html, 'as text');

        // and the registration refuses it in the first place. The action Fortify uses is the
        // bundled one; the copy in app/Modules (for an ejected Auth module) carries the same rule,
        // it just cannot run here because it is bound to the App\Models\User of a real application.
        $this->assertThrows(
            fn () => (new \Zofe\Rapyd\Modules\Auth\Actions\Fortify\CreateNewUser)->create([
                'name' => $payload, 'email' => 'x@acme.test', 'password' => 'a-long-password', 'password_confirmation' => 'a-long-password',
            ]),
            \Illuminate\Validation\ValidationException::class
        );
        $ejectable = (new \ReflectionClass(\App\Modules\Auth\Actions\Fortify\CreateNewUser::class))->getFileName();
        $this->assertStringContainsString("'not_regex:/[<>]/'", file_get_contents($ejectable), 'the ejectable copy too');
    }

    /** 5. A rich-text value is written by the browser: it is sanitised before it is echoed. */
    public function test_a_rich_text_value_is_sanitised_before_being_echoed()
    {
        $clean = rapyd_clean_html('<p>Hello <strong>world</strong></p><script>alert(1)</script><a href="javascript:alert(2)">x</a><img src=x onerror=alert(3)>');

        $this->assertStringContainsString('<p>Hello <strong>world</strong></p>', $clean, 'formatting is kept');
        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);

        $this->assertSame('', rapyd_clean_html(null));
        $this->assertStringNotContainsString('<script', rapyd_clean_html('<script>alert(1)</script>'));
    }
}
