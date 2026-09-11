<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Livewire\Livewire;
use Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder;
use Zofe\Rapyd\Modules\Log\Services\LogParser;
use Zofe\Rapyd\Tests\Models\User;
use Zofe\Rapyd\Tests\TestCase;

class LogTest extends TestCase
{
    use DatabaseMigrations;

    protected string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AuthSeeder::class);
        $this->actingAs(User::where('email', 'admin@laravel')->firstOrFail());

        @mkdir(storage_path('logs'), 0777, true);
        $this->file = storage_path('logs/laravel-2026-09-11.log');
        file_put_contents($this->file, implode("\n", [
            '[2026-09-11 10:00:00] testing.INFO: User logged in {"id":1}',
            '[2026-09-11 10:01:00] testing.ERROR: Division by zero at /app/Calc.php:12',
            '#0 /app/Calc.php(12): intdiv()',
            '#1 {main}',
            '[2026-09-11 10:02:00] testing.ERROR: Division by zero at /app/Calc.php:12',
            '#0 /app/Calc.php(12): intdiv()',
            '[2026-09-11 10:03:00] testing.WARNING: Disk almost full',
        ]) . "\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        parent::tearDown();
    }

    public function test_parser_reads_entries_newest_first_with_stacks()
    {
        $entries = (new LogParser())->entries(basename($this->file));

        $this->assertCount(4, $entries);
        $this->assertEquals('warning', $entries[0]['level']);
        $this->assertEquals('Disk almost full', $entries[0]['text']);
        $this->assertStringContainsString('#1 {main}', $entries[2]['stack']);
        $this->assertEquals('', $entries[3]['stack']);

        $summary = (new LogParser())->summary(basename($this->file));
        $this->assertEquals(2, $summary[0]['count']);
        $this->assertStringContainsString('Division by zero', $summary[0]['message']);
    }

    public function test_table_filters_and_loads_the_stack_on_demand()
    {
        Livewire::test('log::log-app-table')
            ->assertSee('Disk almost full')
            ->assertSee('Division by zero')
            ->assertDontSee('#0 /app/Calc.php')
            ->set('level', 'error')
            ->assertDontSee('Disk almost full')
            ->set('level', '')
            ->set('search', 'disk')
            ->assertSee('Disk almost full')
            ->assertDontSee('Division by zero');

        $id = (new LogParser())->entries(basename($this->file))[2]['id'];

        Livewire::test('log::log-app-table')
            ->call('showStack', $id)
            ->assertDispatched('show-modal')
            ->assertSee('#0 /app/Calc.php');
    }

    public function test_logs_require_the_permission()
    {
        $customer = User::create(['name' => 'Mario', 'email' => 'mario@example.com', 'password' => 'secret']);
        $customer->assignRole('customer');
        $this->actingAs($customer);

        Livewire::test('log::log-app-table')->assertForbidden();
    }
}
