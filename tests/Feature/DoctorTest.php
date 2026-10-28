<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\File;
use Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder;
use Zofe\Rapyd\Tests\Models\User;
use Zofe\Rapyd\Tests\TestCase;

/** rpd:doctor looks at an installation and says what is wrong, in a throwaway application. */
class DoctorTest extends TestCase
{
    use DatabaseMigrations;

    protected string $base;

    protected function setUp(): void
    {
        parent::setUp();
        $this->base = sys_get_temp_dir() . '/rapyd-doctor-' . uniqid();
        File::ensureDirectoryExists($this->base . '/config');
        File::ensureDirectoryExists($this->base . '/app/Modules');
        File::ensureDirectoryExists($this->base . '/public');
        File::ensureDirectoryExists($this->base . '/database/migrations');
        $this->app->setBasePath($this->base);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->base);
        parent::tearDown();
    }

    /** What rpd:make:setup leaves behind, by hand. */
    protected function installed(): void
    {
        foreach (['rapyd', 'livewire', 'fortify', 'permission'] as $name) {
            File::put($this->base . "/config/{$name}.php", "<?php return [];");
        }
        File::ensureDirectoryExists($this->base . '/public/vendor/rapyd');
        File::copy(dirname(__DIR__, 2) . '/public/rapyd.css', $this->base . '/public/vendor/rapyd/rapyd.css');
        File::put($this->base . '/boost.json', json_encode(['packages' => ['zofe/rapyd-admin']]));
        File::put($this->base . '/AGENTS.md', 'zofe/rapyd-admin/core rules');
        File::ensureDirectoryExists($this->base . '/app/Modules/Blog');
    }

    public function test_a_bare_application_gets_told_what_is_missing_and_fails_with_the_flag()
    {
        $this->artisan('rpd:doctor', ['--fail' => true])
            ->expectsOutputToContain('✗ config/rapyd.php, config/livewire.php, config/fortify.php, config/permission.php not published')
            ->expectsOutputToContain('✗ assets not published')
            ->expectsOutputToContain('! no module in app/Modules yet')
            ->expectsOutputToContain('! boost.json does not list zofe/rapyd-admin')
            ->expectsOutputToContain('! your agent does not know Rapyd Admin yet')
            ->expectsOutputToContain('problems,')
            ->assertFailed();

        $this->artisan('rpd:doctor')->assertSuccessful();   // without --fail it only reports
    }

    public function test_an_installed_application_passes_and_the_admin_password_is_watched()
    {
        $this->installed();
        $this->seed(AuthSeeder::class);

        $this->artisan('rpd:doctor', ['--fail' => true])
            ->expectsOutputToContain('✓ config/rapyd.php, livewire.php, fortify.php, permission.php published')
            ->expectsOutputToContain('✓ assets published')
            ->expectsOutputToContain('✓ migrations up to date')
            ->expectsOutputToContain('✓ 1 module(s) in app/Modules: Blog')
            ->expectsOutputToContain('✓ boost.json lists zofe/rapyd-admin')
            ->expectsOutputToContain('✓ the AI guideline is installed')
            ->expectsOutputToContain('✗ admin@laravel still has the password printed at setup')
            ->assertFailed();

        $admin = User::where('email', 'admin@laravel')->firstOrFail();
        $admin->forceFill(['updated_at' => now()->addMinutes(5)])->save();   // changed later: fine
        $this->artisan('rpd:doctor', ['--fail' => true])
            ->expectsOutputToContain('✓ admin@laravel has a password of its own')
            ->assertSuccessful();
    }

    public function test_assets_behind_the_package_and_mail_to_the_log_are_pointed_out()
    {
        $this->installed();
        File::put($this->base . '/public/vendor/rapyd/rapyd.css', '/* an older build */');
        config(['mail.default' => 'log']);

        $this->artisan('rpd:doctor')
            ->expectsOutputToContain('✗ public/vendor/rapyd is behind the package: php artisan vendor:publish --tag=laravel-assets --force')
            ->expectsOutputToContain('! MAIL_MAILER=log: "forgot password" and every other mail go to the log')
            ->assertSuccessful();
    }
}
