<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Livewire\Livewire;
use Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder;
use Zofe\Rapyd\Modules\Companies\Models\Company;
use Zofe\Rapyd\Tests\Models\User;
use Zofe\Rapyd\Tests\TestCase;

class CompaniesTest extends TestCase
{
    use DatabaseMigrations;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AuthSeeder::class);
        $this->admin = User::where('email', 'admin@laravel')->firstOrFail();
        $this->actingAs($this->admin);
    }

    protected function makeCompany(string $name = 'Acme'): Company
    {
        return Company::create(['business_name' => $name, 'status' => 'active']);
    }

    public function test_table_lists_and_searches_companies()
    {
        $this->makeCompany('Acme Corp');
        $this->makeCompany('Globex');

        Livewire::test('companies::companies-table')
            ->assertSee('Acme Corp')
            ->assertSee('Globex')
            ->set('search', 'Acme')
            ->assertSee('Acme Corp')
            ->assertDontSee('Globex');
    }

    public function test_create_requires_business_name()
    {
        Livewire::test('companies::companies-edit')
            ->set('company.email', 'info@acme.test')
            ->call('save')
            ->assertHasErrors(['company.business_name' => 'required']);

        $this->assertDatabaseCount('companies', 0);
    }

    public function test_create_company_and_redirect_to_view()
    {
        Livewire::test('companies::companies-edit')
            ->set('company.business_name', 'Acme Corp')
            ->set('company.status', 'active')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $company = Company::where('business_name', 'Acme Corp')->firstOrFail();
        $this->assertEquals('active', $company->status);
    }

    public function test_super_admin_can_view_any_company()
    {
        $company = $this->makeCompany();

        Livewire::test('companies::companies-view', ['company' => $company])
            ->assertSee('Acme');
    }

    public function test_customer_sees_only_his_own_company()
    {
        $company = $this->makeCompany();
        $customer = User::create(['name' => 'Mario', 'email' => 'mario@example.com', 'password' => 'secret']);
        $customer->assignRole('customer');
        $this->actingAs($customer);

        Livewire::test('companies::companies-view', ['company' => $company])
            ->assertNotFound();

        $customer->attachToCompany($company, 'member');

        Livewire::test('companies::companies-view', ['company' => $company])
            ->assertSee('Acme');
    }

    public function test_operator_sees_only_companies_he_belongs_to()
    {
        $company = $this->makeCompany();
        $operator = User::create(['name' => 'Anna', 'email' => 'anna@example.com', 'password' => 'secret']);
        $operator->assignRole('operator');
        $this->actingAs($operator);

        Livewire::test('companies::companies-view', ['company' => $company])
            ->assertNotFound();

        $operator->attachToCompany($company, 'member');

        Livewire::test('companies::companies-view', ['company' => $company])
            ->assertSee('Acme');
    }

    public function test_user_without_any_company_permission_gets_403()
    {
        $company = $this->makeCompany();
        $nobody = User::create(['name' => 'Nobody', 'email' => 'nobody@example.com', 'password' => 'secret']);
        $this->actingAs($nobody);

        Livewire::test('companies::companies-view', ['company' => $company])
            ->assertForbidden();
    }

    public function test_add_user_to_company_as_owner()
    {
        $company = $this->makeCompany();

        Livewire::test('companies::users-modal-edit-embed')
            ->call('editUser', null, $company->id)
            ->set('user.name', 'Mario Rossi')
            ->set('user.email', 'mario@example.com')
            ->set('passwd', 'password123')
            ->set('role', 'owner')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('savedUser');

        $user = User::where('email', 'mario@example.com')->firstOrFail();
        $this->assertTrue($user->isOwnerOf($company));
        $this->assertEquals($company->id, $user->primaryCompany()?->id);
        $this->assertEquals($company->id, $user->company_id);
        $this->assertTrue($user->hasRole(config('rapyd.companies.user_role')));

        Livewire::test('companies::users-table-embed', ['companyId' => $company->id, 'editable' => true])
            ->assertSee('Mario Rossi')
            ->assertSee('Owner');
    }
}
