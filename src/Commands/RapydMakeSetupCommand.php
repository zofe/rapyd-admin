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
        if (!file_exists(base_path('.env'))) {
            if (file_exists(base_path('.env.example'))) {
                if (copy(base_path('.env.example'), base_path('.env'))) {
                    $this->info('.env created');

                    Artisan::call('key:generate', ['--ansi' => true]);
                }
            }
        }

        if (config('database.default') === 'sqlite') {
            $sqlitePath = database_path('database.sqlite');
            if (!file_exists($sqlitePath)) {
                if (touch($sqlitePath)) {
                    $this->info('database.sqlite created');
                }
            }
        }

        $this->call('rpd:make:home');
        $this->call('rpd:make:auth');


    }
}
