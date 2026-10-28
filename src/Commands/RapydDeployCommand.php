<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Writes what the application needs to go online as containers: a Dockerfile (web, queue and
 * scheduler from one image), a compose.yml with the database, a .dockerignore. Files the
 * application owns from then on: an existing one is kept unless --force.
 */
class RapydDeployCommand extends Command
{
    protected $signature = 'rpd:deploy {--force : Overwrite a Dockerfile, compose.yml or .dockerignore already there}';

    protected $description = 'Write a Dockerfile, a compose.yml and a .dockerignore to run the application as containers';

    public function handle(): int
    {
        $name = Str::slug(config('app.name', 'app')) ?: 'app';
        $written = 0;

        foreach (['Dockerfile' => 'Dockerfile.stub', 'compose.yml' => 'compose.yml.stub', '.dockerignore' => 'dockerignore.stub'] as $file => $stub) {
            $path = base_path($file);
            if (File::exists($path) && ! $this->option('force')) {
                $this->line("  <fg=yellow>SKIP</> {$file} (already there — --force to overwrite)");

                continue;
            }
            File::put($path, str_replace('{{ name }}', $name, File::get(__DIR__ . "/Templates/deploy/{$stub}")));
            $this->line("  <fg=green>DONE</> {$file}");
            $written++;
        }

        $this->newLine();
        $this->line('<fg=cyan>To try it here:</>');
        $this->line('  <fg=white>docker compose up -d --build</>   then open http://localhost:8080');
        $this->line('<fg=cyan>Online:</>');
        $this->line('  Coolify — an Application from the repository, Dockerfile build pack, a database resource, APP_KEY/APP_URL/DB_*/MAIL_* as variables;');
        $this->line('  one Application for the web, one each for the queue (php artisan queue:work) and the scheduler (php artisan schedule:work).');
        $this->line('  Any server — PHP 8.3, nginx on public/, composer install --no-dev, php artisan migrate --force, a queue worker and the scheduler in cron.');
        $this->line('  Before going live: <fg=white>php artisan rpd:doctor</>');

        return self::SUCCESS;
    }
}
