<?php

namespace Zofe\Rapyd\Tests\Feature;

use ReflectionMethod;
use Zofe\Rapyd\Commands\RapydMakeTableCommand;
use Zofe\Rapyd\Tests\TestCase;

class MakeMenuTest extends TestCase
{
    protected string $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base = sys_get_temp_dir() . '/rapyd-make-' . uniqid();
        mkdir($this->base . '/resources/views', 0755, true);
        $this->app->setBasePath($this->base);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->base));
        parent::tearDown();
    }

    protected function addItems(array $items): void
    {
        $cmd = $this->app->make(RapydMakeTableCommand::class);
        $m = new ReflectionMethod($cmd, 'addNavItemIfNotExists');
        $m->setAccessible(true);
        $m->invoke($cmd, $items);
    }

    public function test_the_global_menu_is_created_only_when_an_entry_is_written()
    {
        $menu = $this->base . '/resources/views/menu.blade.php';
        $this->assertFileDoesNotExist($menu);

        $this->addItems([]);
        $this->assertFileDoesNotExist($menu, 'nothing to write, no empty file');

        $this->addItems([['label' => 'Articles', 'route' => 'articles', 'active' => '/articles']]);
        $this->assertFileExists($menu);
        $this->assertStringContainsString('route="articles"', file_get_contents($menu));

        $this->addItems([['label' => 'Articles', 'route' => 'articles']]);
        $this->assertSame(1, substr_count(file_get_contents($menu), 'route="articles"'), 'no duplicates');
    }
}
