<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;

class DataTableCommand extends Command
{
    public $signature = 'rpd:datatable {component} {model} {--module=}';
    public $description = 'generate datatable component';

    public function handle()
    {
        $this->comment('class='.$this->argument('class').' table='.$this->option('table'));
    }
}
