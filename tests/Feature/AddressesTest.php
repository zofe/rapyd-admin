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
            ->set('address.country_code', 'it')
            ->set('address.state_code', 'mi')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('savedAddress');

        $this->assertEquals(1, $this->company->addresses()->count());
        $address = $this->company->addresses()->first();
        $this->assertSame(['IT', 'Italy', 'MI'], [$address->country_code, $address->country, $address->state_code], 'normalised from the ISO code');

        Livewire::test('addresses::addresses-table-embed', ['addressableType' => 'company', 'addressableId' => $this->company->id, 'editable' => true])
            ->assertSee('Via Roma 1')
            ->assertSee('Milano');
    }

    public function test_address_requires_street_city_zipcode_and_country()
    {
        Livewire::test('addresses::addresses-modal-edit-embed')
            ->call('editAddress', null, 'company', $this->company->id)
            ->call('save')
            ->assertHasErrors(['address.address', 'address.city', 'address.zipcode', 'address.country_code']);
    }

    public function test_selectable_list_dispatches_the_chosen_address_and_defaults_to_the_first_with_a_country()
    {
        $old = $this->company->addresses()->create(['address' => 'Old 1', 'city' => 'Nowhere', 'zipcode' => '00000']);
        $it = $this->company->addresses()->create(['address' => 'Via Roma 1', 'city' => 'Milano', 'zipcode' => '20100', 'country_code' => 'IT']);

        $embed = Livewire::test('addresses::addresses-table-embed', ['addressableType' => 'company', 'addressableId' => $this->company->id, 'selectable' => true])
            ->assertSet('selected', (string) $it->id)
            ->assertDispatched('selectedAddress', addressId: (string) $it->id)
            ->assertSeeHtml('type="radio"');

        $embed->call('select', (string) $old->id)
            ->assertSet('selected', (string) $old->id)
            ->assertDispatched('selectedAddress', addressId: (string) $old->id);

        Livewire::test('addresses::addresses-table-embed', ['addressableType' => 'company', 'addressableId' => $this->company->id])
            ->assertDontSeeHtml('type="radio"');
    }

    public function test_countries_helper()
    {
        $this->assertSame('Italy', \Zofe\Rapyd\Support\Countries::name('it'));
        $this->assertTrue(\Zofe\Rapyd\Support\Countries::isEu('DE'));
        $this->assertFalse(\Zofe\Rapyd\Support\Countries::isEu('CH'));
        $this->assertCount(27, \Zofe\Rapyd\Support\Countries::EU);
        $this->assertArrayHasKey('US', \Zofe\Rapyd\Support\Countries::all());
    }

    public function test_customer_sees_addresses_only_of_his_own_company()
    {
        $this->company->addresses()->create(['address' => 'Via Roma 1', 'city' => 'Milano', 'zipcode' => '20100']);
        $customer = User::create(['name' => 'Mario', 'email' => 'mario@example.com', 'password' => 'secret']);
        $customer->assignRole('customer');
        $this->actingAs($customer);

        Livewire::test('addresses::addresses-table-embed', ['addressableType' => 'company', 'addressableId' => $this->company->id])
            ->assertNotFound();

        $customer->assignToCompany($this->company, 'member');

        Livewire::test('addresses::addresses-table-embed', ['addressableType' => 'company', 'addressableId' => $this->company->id])
            ->assertSee('Via Roma 1');
    }
}
