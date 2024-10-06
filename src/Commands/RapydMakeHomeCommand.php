<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Touhidurabir\StubGenerator\Facades\StubGenerator;
use Zofe\Rapyd\Utilities\StrReplacer;

class RapydMakeHomeCommand extends Command
{
    public $signature = 'rpd:make:home';
    public $description = 'generate home page';

    public function handle()
    {
        $this->module = null;


        if (! file_exists(base_path("resources/views/livewire/home.blade.php"))) {

            $this->comment('generate home component');

            $routename = 'home';
            $routeuri = '/';

            $classPath = path_module("app/Livewire", $this->module);
            $classNamespace = namespace_module('App\\Livewire', $this->module);
            $viewPrefix = $this->module? Str::lower($this->module).'::' : "";
            $viewPath = $viewPrefix.'livewire.home';

            //component
            StubGenerator::from(__DIR__.'/Templates/Livewire/Home.stub', true)
                ->to(base_path($classPath), true, true)
                ->as('Home')
                ->withReplacers([
                    'class' => 'Home',
                    'view' => $viewPath,
                    'baseClass' => 'Livewire\\Component',
                    'baseClassName' => 'Component',
                    'classNamespace' => $classNamespace,
                ])
                ->save();

            //view
            StubGenerator::from(__DIR__.'/Templates/resources/livewire/home.blade.stub', true)
                ->to(base_path('resources/views/livewire'), true, true)
                ->as('home.blade')
                ->withReplacers([
                    'routename' => $routename,
                ])
                ->save();

            //rotta
            $strSubstitutor = (new StrReplacer([
                'class' => "\\".$classNamespace."\\Home",
                'routepath' => $routeuri,
                'routename' => $routename,
                'layout' => 'layout::frontend',
            ]));

            $substituted = $strSubstitutor->replace(file_get_contents(__DIR__.'/Templates/routes/home.stub'));
            file_put_contents(base_path("routes/web.php"), $substituted, FILE_APPEND);
        }
    }
}
