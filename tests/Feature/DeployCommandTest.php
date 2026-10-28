<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Support\Facades\File;
use Zofe\Rapyd\Tests\TestCase;

/** rpd:deploy writes the container files into the application, and never over the application's own. */
class DeployCommandTest extends TestCase
{
    protected string $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base = sys_get_temp_dir() . '/rapyd-deploy-' . uniqid();
        File::ensureDirectoryExists($this->base);
        $this->app->setBasePath($this->base);
        config(['app.name' => 'My Shop']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->base);
        parent::tearDown();
    }

    public function test_it_writes_the_three_files_named_after_the_application()
    {
        $this->artisan('rpd:deploy')
            ->expectsOutputToContain('DONE Dockerfile')
            ->expectsOutputToContain('DONE compose.yml')
            ->expectsOutputToContain('DONE .dockerignore')
            ->expectsOutputToContain('docker compose up -d --build')
            ->assertSuccessful();

        $dockerfile = File::get($this->base . '/Dockerfile');
        $this->assertStringContainsString('FROM serversideup/php:8.3-fpm-nginx', $dockerfile);
        $this->assertStringContainsString('docker build -t my-shop .', $dockerfile);
        $this->assertStringContainsString('composer install --no-dev', $dockerfile);
        $this->assertStringContainsString('vendor:publish --tag=laravel-assets --force', $dockerfile, 'the package assets go into the image');
        $this->assertStringContainsString('USER www-data', $dockerfile);

        $compose = File::get($this->base . '/compose.yml');
        foreach (['app:', 'queue:', 'scheduler:', 'db:'] as $service) {
            $this->assertStringContainsString($service, $compose);
        }
        $this->assertStringContainsString('${DB_DATABASE:-my-shop}', $compose);
        $this->assertStringContainsString('queue:work', $compose);
        $this->assertStringContainsString('schedule:work', $compose);

        $ignore = File::get($this->base . '/.dockerignore');
        foreach (['.env', 'vendor', 'node_modules', '.git'] as $line) {
            $this->assertMatchesRegularExpression('/^' . preg_quote($line, '/') . '$/m', $ignore, "{$line} stays out of the image");
        }
    }

    public function test_a_file_the_application_already_has_is_kept_unless_forced()
    {
        File::put($this->base . '/Dockerfile', '# mine');

        $this->artisan('rpd:deploy')->expectsOutputToContain('SKIP Dockerfile')->assertSuccessful();
        $this->assertSame('# mine', File::get($this->base . '/Dockerfile'));
        $this->assertFileExists($this->base . '/compose.yml', 'the others are still written');

        $this->artisan('rpd:deploy', ['--force' => true])->expectsOutputToContain('DONE Dockerfile')->assertSuccessful();
        $this->assertStringContainsString('FROM serversideup', File::get($this->base . '/Dockerfile'));
    }
}
