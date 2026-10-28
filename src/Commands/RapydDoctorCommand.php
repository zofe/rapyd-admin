<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\Features;

/**
 * Checks the installation and says what is wrong, without changing anything — as composer diagnose
 * does. The same checks the dashboard's "Getting started" makes, plus the ones an administrator
 * cares about (assets behind the package, migrations not run, debug on in production), in a place
 * an agent can run and read. With --fail it exits with 1 on a problem, for CI.
 */
class RapydDoctorCommand extends Command
{
    protected $signature = 'rpd:doctor {--fail : Exit with 1 when a problem is found (for CI)}';

    protected $description = 'Check the installation: published files, assets, migrations, the first admin, mail, what an agent needs';

    public const OK = 'ok';
    public const PROBLEM = 'problem';
    public const NOTE = 'note';

    public function handle(): int
    {
        $rows = $this->checks();

        foreach ($rows as [$status, $message]) {
            $this->line('  ' . match ($status) {
                self::OK => "<fg=green>✓</> {$message}",
                self::PROBLEM => "<fg=red>✗</> {$message}",
                default => "<fg=yellow>!</> {$message}",
            });
        }

        $problems = count(array_filter($rows, fn ($r) => $r[0] === self::PROBLEM));
        $notes = count(array_filter($rows, fn ($r) => $r[0] === self::NOTE));
        $this->newLine();
        $this->line($problems || $notes
            ? "  {$problems} " . ($problems === 1 ? 'problem' : 'problems') . ", {$notes} " . ($notes === 1 ? 'note' : 'notes')
            : '  <fg=green>Nothing to report.</>');

        return $problems && $this->option('fail') ? self::FAILURE : self::SUCCESS;
    }

    /** @return array<int, array{0: string, 1: string}> */
    public function checks(): array
    {
        return [
            $this->publishedConfigs(),
            $this->assets(),
            $this->migrations(),
            $this->debugInProduction(),
            $this->adminPassword(),
            $this->mail(),
            $this->registration(),
            $this->modules(),
            $this->boost(),
            $this->agentGuideline(),
        ];
    }

    protected function publishedConfigs(): array
    {
        $missing = array_filter(['rapyd', 'livewire', 'fortify', 'permission'], fn ($name) => ! File::exists(config_path("{$name}.php")));

        return $missing
            ? [self::PROBLEM, 'config/' . implode('.php, config/', $missing) . '.php not published: php artisan vendor:publish --tag=rapyd-config']
            : [self::OK, 'config/rapyd.php, livewire.php, fortify.php, permission.php published'];
    }

    protected function assets(): array
    {
        $published = public_path('vendor/rapyd/rapyd.css');
        $shipped = dirname(__DIR__, 2) . '/public/rapyd.css';
        if (! File::exists($published)) {
            return [self::PROBLEM, 'assets not published: php artisan vendor:publish --tag=laravel-assets'];
        }
        if (File::exists($shipped) && md5_file($shipped) !== md5_file($published)) {
            return [self::PROBLEM, 'public/vendor/rapyd is behind the package: php artisan vendor:publish --tag=laravel-assets --force'];
        }

        return [self::OK, 'assets published (public/vendor/rapyd)'];
    }

    protected function migrations(): array
    {
        try {
            $migrator = app('migrator');
            if (! $migrator->repositoryExists()) {
                return [self::PROBLEM, 'the database has no migrations table: php artisan migrate'];
            }
            $paths = array_merge($migrator->paths(), [database_path('migrations')]);
            $pending = array_diff(array_keys($migrator->getMigrationFiles($paths)), $migrator->getRepository()->getRan());
        } catch (\Throwable $e) {
            return [self::PROBLEM, 'the database does not answer: ' . $e->getMessage()];
        }

        return $pending
            ? [self::PROBLEM, count($pending) . ' migration(s) not run: php artisan migrate']
            : [self::OK, 'migrations up to date'];
    }

    protected function debugInProduction(): array
    {
        if (app()->environment('production') && config('app.debug')) {
            return [self::PROBLEM, 'APP_DEBUG=true in production: every error page shows your code and your .env'];
        }

        return [self::OK, 'APP_ENV=' . app()->environment() . ', APP_DEBUG=' . (config('app.debug') ? 'true' : 'false')];
    }

    protected function adminPassword(): array
    {
        $users = config('auth.providers.users.model');
        if (! $users || ! class_exists($users) || ! Schema::hasTable((new $users)->getTable())) {
            return [self::NOTE, 'no users table yet'];
        }
        $admin = $users::where('email', 'admin@laravel')->first();
        if (! $admin) {
            return [self::OK, 'no admin@laravel account (the first admin was renamed or removed)'];
        }
        if (env('RAPYD_ADMIN_PASSWORD') || $admin->updated_at?->gt($admin->created_at->addMinute())) {
            return [self::OK, 'admin@laravel has a password of its own'];
        }

        return [self::PROBLEM, 'admin@laravel still has the password printed at setup: change it, or delete the account'];
    }

    protected function mail(): array
    {
        $mailer = config('mail.default');
        if ($mailer === 'log' || $mailer === 'array') {
            return [
                app()->environment('production') ? self::PROBLEM : self::NOTE,
                "MAIL_MAILER={$mailer}: \"forgot password\" and every other mail go to the log, nobody receives them",
            ];
        }

        return [self::OK, "mail through {$mailer}"];
    }

    protected function registration(): array
    {
        if (class_exists(Features::class) && Features::enabled(Features::registration())) {
            return [self::NOTE, 'registration is open: anyone can create an account (it gets no role) — RAPYD_REGISTRATION=false closes it'];
        }

        return [self::OK, 'registration closed (RAPYD_REGISTRATION=true opens it)'];
    }

    protected function modules(): array
    {
        $modules = array_map('basename', File::directories(app_path('Modules')) ?: []);

        return $modules
            ? [self::OK, count($modules) . ' module(s) in app/Modules: ' . implode(', ', $modules)]
            : [self::NOTE, 'no module in app/Modules yet: php artisan rpd:make Things Thing --module=Name --fields="name,active:boolean" --seed'];
    }

    protected function boost(): array
    {
        $file = base_path('boost.json');
        $config = File::exists($file) ? json_decode(File::get($file), true) : null;
        if (is_array($config) && in_array('zofe/rapyd-admin', $config['packages'] ?? [], true)) {
            return [self::OK, 'boost.json lists zofe/rapyd-admin'];
        }

        return [self::NOTE, 'boost.json does not list zofe/rapyd-admin: php artisan rpd:install, or add it to "packages"'];
    }

    protected function agentGuideline(): array
    {
        $known = File::isDirectory(base_path('.claude/skills/rapyd-module'))
            || (File::exists(base_path('AGENTS.md')) && str_contains(File::get(base_path('AGENTS.md')), 'zofe/rapyd-admin'))
            || (File::exists(base_path('CLAUDE.md')) && str_contains(File::get(base_path('CLAUDE.md')), 'zofe/rapyd-admin'));

        return $known
            ? [self::OK, 'the AI guideline is installed (AGENTS.md / .claude/skills)']
            : [self::NOTE, 'your agent does not know Rapyd Admin yet: php artisan boost:install (with Laravel Boost) or php artisan rpd:ai'];
    }
}
