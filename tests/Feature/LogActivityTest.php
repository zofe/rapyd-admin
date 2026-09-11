<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Livewire\Livewire;
use Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder;
use Zofe\Rapyd\Modules\Companies\Models\Company;
use Zofe\Rapyd\Modules\Log\Models\Activity;
use Zofe\Rapyd\Tests\Models\User;
use Zofe\Rapyd\Tests\TestCase;

class LogActivityTest extends TestCase
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

    public function test_company_changes_are_tracked_automatically()
    {
        $company = Company::create(['business_name' => 'Acme', 'status' => 'active']);
        $company->update(['status' => 'suspended', 'note' => 'ignored attribute']);

        $created = Activity::where('log_name', 'company')->where('event', 'created')->firstOrFail();
        $this->assertEquals($this->admin->id, $created->causer_id);
        $this->assertEquals($company->id, $created->subject_id);

        $updated = Activity::where('log_name', 'company')->where('event', 'updated')->firstOrFail();
        $changes = collect($updated->readableChanges())->keyBy('key');
        $this->assertEquals(['active', 'suspended'], [$changes['status']['old'], $changes['status']['new']]);
        $this->assertArrayNotHasKey('note', $changes->all());
    }

    public function test_helper_records_custom_events()
    {
        $company = Company::create(['business_name' => 'Acme', 'status' => 'active']);

        log_activity('invoice_sent', $company, ['number' => 'INV-1', 'password' => 'hidden']);

        $activity = Activity::where('log_name', 'invoice_sent')->firstOrFail();
        $this->assertEquals('invoice sent', $activity->description);
        $this->assertEquals($this->admin->id, $activity->causer_id);
        $this->assertEquals([['key' => 'number', 'old' => null, 'new' => 'INV-1']], $activity->readableChanges());
    }

    public function test_logins_are_tracked()
    {
        event(new Login('web', $this->admin, false));

        $this->assertDatabaseHas('activity_log', ['log_name' => 'login', 'causer_id' => $this->admin->id, 'subject_id' => $this->admin->id]);
    }

    public function test_activity_table_lists_and_filters()
    {
        $company = Company::create(['business_name' => 'Acme', 'status' => 'active']);
        log_activity('invoice_sent', $company, ['number' => 'INV-1']);
        $mario = User::create(['name' => 'Mario', 'email' => 'mario@example.com', 'password' => 'secret']);
        log_activity('login', $mario, [], null, $mario);

        Livewire::test('log::log-activity-table')
            ->assertSee('invoice sent')
            ->assertSee('Mario')
            ->assertSee('INV-1')
            ->set('user', [$mario->id])
            ->assertDontSee('invoice sent')
            ->set('user', [])
            ->set('search', 'INV')
            ->assertSee('invoice sent')
            ->assertDontSee('logout');
    }

    public function test_activity_requires_the_permission()
    {
        $customer = User::create(['name' => 'Mario', 'email' => 'mario@example.com', 'password' => 'secret']);
        $customer->assignRole('customer');
        $this->actingAs($customer);

        Livewire::test('log::log-activity-table')->assertForbidden();
    }
}
