<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;

class AssetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rapyd-assets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-publish the packagename assets';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('vendor:publish', [
            '--tag' => 'rapyd-assets',
            '--force' => true,
        ]);

        //        $this->call('vendor:publish', [
        //            '--tag' => 'views',
        //            '--force' => true,
        //        ]);
    }
}
