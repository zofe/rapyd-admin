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

    protected function fakeGoogle(): void
    {
        config(['rapyd.addresses.lookup' => 'google', 'rapyd.addresses.google_key' => 'k', 'rapyd.addresses.lookup_country' => 'it']);
        \Illuminate\Support\Facades\Http::fake([
            'places.googleapis.com/v1/places:autocomplete' => \Illuminate\Support\Facades\Http::response(['suggestions' => [
                ['placePrediction' => ['placeId' => 'ChIJduomo', 'text' => ['text' => 'Piazza del Duomo, 1, Milano MI, Italy']]],
                ['placePrediction' => ['placeId' => 'ChIJroad', 'text' => ['text' => 'Piazza del Duomo, Milano MI, Italy']]],
            ]]),
            'places.googleapis.com/v1/places/*' => \Illuminate\Support\Facades\Http::response([
                'formattedAddress' => 'P.za del Duomo, 1, 20122 Milano MI, Italy',
                'location' => ['latitude' => 45.4641, 'longitude' => 9.1919],
                'addressComponents' => [
                    ['longText' => '1', 'shortText' => '1', 'types' => ['street_number']],
                    ['longText' => 'Piazza del Duomo', 'shortText' => 'P.za del Duomo', 'types' => ['route']],
                    ['longText' => 'Milano', 'shortText' => 'Milano', 'types' => ['locality', 'political']],
                    ['longText' => 'Città Metropolitana di Milano', 'shortText' => 'MI', 'types' => ['administrative_area_level_2', 'political']],
                    ['longText' => 'Lombardia', 'shortText' => 'Lombardia', 'types' => ['administrative_area_level_1', 'political']],
                    ['longText' => 'Italy', 'shortText' => 'IT', 'types' => ['country', 'political']],
                    ['longText' => '20122', 'shortText' => '20122', 'types' => ['postal_code']],
                ],
            ]),
            'addressvalidation.googleapis.com/*' => \Illuminate\Support\Facades\Http::response(['result' => [
                'verdict' => ['validationGranularity' => 'OTHER', 'hasUnconfirmedComponents' => true],
                'address' => ['formattedAddress' => 'Via Inesistente 999, Milano', 'addressComponents' => []],
            ]]),
        ]);
    }

    public function test_google_places_fills_the_form_within_one_billing_session()
    {
        $this->fakeGoogle();

        $modal = Livewire::test('addresses::addresses-modal-edit-embed')
            ->call('editAddress', null, 'company', $this->company->id)
            ->assertSee('Search address')
            ->assertSeeHtml('readonly')
            ->set('lookup', 'piazza duomo 1 milano')
            ->assertCount('suggestions', 2)
            ->assertSee('Piazza del Duomo, 1, Milano MI, Italy');
        $session = $modal->get('lookupSession');
        $this->assertNotEmpty($session);

        $modal->call('pick', 0)
            ->assertSet('address.address', 'Piazza del Duomo')
            ->assertSet('address.street_number', '1')
            ->assertSet('address.zipcode', '20122')
            ->assertSet('address.city', 'Milano')
            ->assertSet('address.province', 'MI')
            ->assertSet('address.region', 'Lombardia')
            ->assertSet('address.country_code', 'IT')
            ->assertSet('address.state_code', 'MI')
            ->assertSet('address.confidence', 'verified')
            ->assertSet('address.verified_by', 'google')
            ->assertCount('suggestions', 0)
            ->call('save')
            ->assertHasNoErrors();

        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => str_contains($r->url(), 'places:autocomplete') && $r['sessionToken'] === $session && $r['includedRegionCodes'] === ['it'] && $r->hasHeader('X-Goog-Api-Key', 'k'));
        \Illuminate\Support\Facades\Http::assertSent(fn ($r) => str_contains($r->url(), 'places/ChIJduomo') && str_contains($r->url(), 'sessionToken=' . $session) && $r->hasHeader('X-Goog-FieldMask'));
        $this->assertNotSame($session, $modal->get('lookupSession'), 'a new session after the details call');

        $address = $this->company->addresses()->first();
        $this->assertEqualsWithDelta(45.4641, $address->address_lat, 0.0001);
        $this->assertNotNull($address->verified_at);
    }

    public function test_us_components_give_the_state_code()
    {
        $c = (new \Zofe\Rapyd\Modules\Addresses\Lookup\GoogleLookup())->fromComponents([
            ['longText' => '1600', 'shortText' => '1600', 'types' => ['street_number']],
            ['longText' => 'Amphitheatre Parkway', 'shortText' => 'Amphitheatre Pkwy', 'types' => ['route']],
            ['longText' => 'Mountain View', 'shortText' => 'Mountain View', 'types' => ['locality']],
            ['longText' => 'Santa Clara County', 'shortText' => 'Santa Clara County', 'types' => ['administrative_area_level_2']],
            ['longText' => 'California', 'shortText' => 'CA', 'types' => ['administrative_area_level_1']],
            ['longText' => 'United States', 'shortText' => 'US', 'types' => ['country']],
            ['longText' => '94043', 'shortText' => '94043', 'types' => ['postal_code']],
        ], ['latitude' => 37.42, 'longitude' => -122.08], 'x');
        $this->assertSame(['CA', 'US', 'Santa Clara County', 'verified'], [$c->state_code, $c->country_code, $c->province, $c->confidence]);
    }

    public function test_strict_validation_refuses_an_address_google_cannot_find()
    {
        $this->fakeGoogle();
        config(['rapyd.addresses.validate' => 'strict']);

        Livewire::test('addresses::addresses-modal-edit-embed')
            ->call('editAddress', null, 'company', $this->company->id)
            ->set('address.address', 'Via Inesistente')->set('address.street_number', '999')
            ->set('address.city', 'Milano')->set('address.zipcode', '20100')->set('address.country_code', 'IT')
            ->call('save')
            ->assertHasErrors(['address.address']);
        $this->assertEquals(0, $this->company->addresses()->count());

        config(['rapyd.addresses.validate' => true]);
        Livewire::test('addresses::addresses-modal-edit-embed')
            ->call('editAddress', null, 'company', $this->company->id)
            ->set('address.address', 'Via Inesistente')->set('address.street_number', '999')
            ->set('address.city', 'Milano')->set('address.zipcode', '20100')->set('address.country_code', 'IT')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame(['google', 'unknown'], [$this->company->addresses()->first()->verified_by, $this->company->addresses()->first()->confidence], 'saved, but flagged');
    }

    public function test_without_a_lookup_service_the_form_has_no_search_box()
    {
        Livewire::test('addresses::addresses-modal-edit-embed')
            ->call('editAddress', null, 'company', $this->company->id)
            ->assertDontSee('Search address')
            ->assertDontSeeHtml('readonly')
            ->set('lookup', 'anything')
            ->assertCount('suggestions', 0);
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
