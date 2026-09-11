<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Livewire\Livewire;
use Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder;
use Zofe\Rapyd\Modules\Companies\Models\Company;
use Zofe\Rapyd\Tests\Models\User;
use Zofe\Rapyd\Tests\TestCase;

class SearchTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AuthSeeder::class);
        Company::create(['business_name' => 'Acme Corp', 'status' => 'active']);
        Company::create(['business_name' => 'Globex', 'status' => 'active']);
        User::create(['name' => 'Mario Acme', 'email' => 'mario@example.com', 'password' => 'secret']);
    }

    public function test_search_requires_authentication()
    {
        $this->getJson('/search/items?q=acme')->assertUnauthorized();
    }

    public function test_admin_finds_users_and_companies()
    {
        $this->actingAs(User::where('email', 'admin@laravel')->firstOrFail());

        $response = $this->getJson('/search/items?q=acme')->assertOk();
        $html = collect($response->json())->pluck('html')->implode(' ');

        $this->assertStringContainsString('Acme Corp', $html);
        $this->assertStringContainsString('Mario Acme', $html);
        $this->assertStringNotContainsString('Globex', $html);
        $this->assertStringContainsString('/companies/view/', collect($response->json())->pluck('url')->implode(' '));
    }

    public function test_customer_only_finds_companies_he_belongs_to()
    {
        $customer = User::where('email', 'mario@example.com')->firstOrFail();
        $customer->assignRole('customer');
        $customer->assignToCompany(Company::where('business_name', 'Globex')->firstOrFail(), 'member');
        $this->actingAs($customer);

        $html = collect($this->getJson('/search/items?q=o')->assertOk()->json())->pluck('html')->implode(' ');

        $this->assertStringContainsString('Globex', $html);
        $this->assertStringNotContainsString('Acme Corp', $html);
    }

    public function test_navbar_renders()
    {
        $this->actingAs(User::where('email', 'admin@laravel')->firstOrFail());

        Livewire::test('search::search-navbar')->assertSee('search/items', false);
    }
}
