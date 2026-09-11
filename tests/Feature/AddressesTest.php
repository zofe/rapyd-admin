<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Livewire\Livewire;
use Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder;
use Zofe\Rapyd\Modules\Companies\Models\Company;
use Zofe\Rapyd\Tests\Models\User;
use Zofe\Rapyd\Tests\TestCase;

class AddressesTest extends TestCase
{
    use DatabaseMigrations;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AuthSeeder::class);
        $this->actingAs(User::where('email', 'admin@laravel')->firstOrFail());
        $this->company = Company::create(['business_name' => 'Acme', 'status' => 'active']);
    }

    public function test_admin_adds_an_address_to_a_company()
    {
        Livewire::test('addresses::addresses-modal-edit-embed')
            ->call('editAddress', null, 'company', $this->company->id)
            ->set('address.address', 'Via Roma 1')
            ->set('address.city', 'Milano')
            ->set('address.zipcode', '20100')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('savedAddress');

        $this->assertEquals(1, $this->company->addresses()->count());

        Livewire::test('addresses::addresses-table-embed', ['addressableType' => 'company', 'addressableId' => $this->company->id, 'editable' => true])
            ->assertSee('Via Roma 1')
            ->assertSee('Milano');
    }

    public function test_address_requires_street_city_and_zipcode()
    {
        Livewire::test('addresses::addresses-modal-edit-embed')
            ->call('editAddress', null, 'company', $this->company->id)
            ->call('save')
            ->assertHasErrors(['address.address', 'address.city', 'address.zipcode']);
    }

    public function test_customer_sees_addresses_only_of_his_own_company()
    {
        $this->company->addresses()->create(['address' => 'Via Roma 1', 'city' => 'Milano', 'zipcode' => '20100']);
        $customer = User::create(['name' => 'Mario', 'email' => 'mario@example.com', 'password' => 'secret']);
        $customer->assignRole('customer');
        $this->actingAs($customer);

        Livewire::test('addresses::addresses-table-embed', ['addressableType' => 'company', 'addressableId' => $this->company->id])
            ->assertNotFound();

        $customer->attachToCompany($this->company, 'member');

        Livewire::test('addresses::addresses-table-embed', ['addressableType' => 'company', 'addressableId' => $this->company->id])
            ->assertSee('Via Roma 1');
    }
}
