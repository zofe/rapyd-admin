<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RapydMakeSetupCommand extends Command
{
    public $signature = 'rpd:make:setup';
    public $description = 'setup basic rapyd admin application';

    public function handle()
    {
        if (! file_exists(base_path('.env'))) {
            if (file_exists(base_path('.env.example'))) {
                if (copy(base_path('.env.example'), base_path('.env'))) {
                    $this->info('.env created');

                    Artisan::call('key:generate', ['--ansi' => true]);
                }
            }
        }

        if (config('database.default') === 'sqlite') {
            $sqlitePath = database_path('database.sqlite');
            if (! file_exists($sqlitePath)) {
                if (touch($sqlitePath)) {
                    $this->info('database.sqlite created');
                }
            }
        }

        $this->setEnv('SCOUT_DRIVER', 'collection');
        $this->setEnv('APP_NAME', '"Rapyd Admin"', onlyIfCurrent: 'Laravel');
        config()->set('scout.driver', 'collection');

        $this->call('rpd:make:home');
        $this->call('rpd:install', [
            '--uuid-users' => true,
            '--companies' => (bool) config('rapyd.companies.enabled', true),
        ]);
        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--class' => \Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder::class, '--force' => true]);

        if (config('rapyd.companies.enabled', true)) {
            $this->call('db:seed', ['--class' => \Zofe\Rapyd\Modules\Companies\Database\Seeders\CompaniesSeeder::class, '--force' => true]);
        }
    }

    /**
     * Write KEY=value into .env. With $onlyIfCurrent the key is changed only when
     * it is missing or still holds that default value (never clobber user choices).
     */
    protected function setEnv(string $key, string $value, ?string $onlyIfCurrent = null): void
    {
        $envPath = base_path('.env');
        $env = file_get_contents($envPath);

        if (preg_match("/^{$key}=(.*)$/m", $env, $m)) {
            $current = trim($m[1], " \"'");
            if ($onlyIfCurrent !== null && $current !== $onlyIfCurrent) {
                return;
            }
            $env = preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $env);
        } else {
            $env .= "\n{$key}={$value}\n";
        }

        file_put_contents($envPath, $env);
        $this->info("{$key} set to {$value}");
    }
}
