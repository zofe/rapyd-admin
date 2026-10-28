<?php

namespace Zofe\Rapyd\Commands;

use Illuminate\Console\Command;
use Zofe\Rapyd\Stubs\Facades\StubGenerator;
use Zofe\Rapyd\Utilities\StrReplacer;

class RapydMakeHomeCommand extends Command
{
    public $signature = 'rpd:make:home';
    public $description = 'generate home page';

    public function handle()
    {
        $this->module = null;


        if (! file_exists(base_path(path_module('app/Livewire', $this->module) . '/Home.php'))) {

            $this->comment('generate home component');
            $this->dropSkeletonWelcomeRoute();

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
                    'layout' => 'layout::frontend',
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
                'middleware' => "'web'",
            ]));

            $substituted = $strSubstitutor->replace(file_get_contents(__DIR__.'/Templates/routes/home.stub'));
            file_put_contents(base_path("routes/web.php"), $substituted, FILE_APPEND);
        }


        if (! file_exists(base_path(path_module('app/Livewire', $this->module) . '/AdminHome.php'))) {

            $this->comment('generate admin component');

            $routename = 'admin.home';
            $routeuri = '/admin';

            $classPath = path_module("app/Livewire", $this->module);
            $classNamespace = namespace_module('App\\Livewire', $this->module);
            $viewPrefix = $this->module? Str::lower($this->module).'::' : "";
            $viewPath = $viewPrefix.'livewire.admin_home';

            //component: the dashboard (tiles, getting started, recent activity), not the landing again
            StubGenerator::from(__DIR__.'/Templates/Livewire/AdminHome.stub', true)
                ->to(base_path($classPath), true, true)
                ->as('AdminHome')
                ->withReplacers([
                    'class' => 'AdminHome',
                    'view' => $viewPath,
                    'layout' => 'layout::admin',
                    'classNamespace' => $classNamespace,
                ])
                ->save();

            //view
            StubGenerator::from(__DIR__.'/Templates/resources/livewire/admin_home.blade.stub', true)
                ->to(base_path('resources/views/livewire'), true, true)
                ->as('admin_home.blade')
                ->withReplacers([
                    'routename' => $routename,
                ])
                ->save();

            //rotta: behind auth, a dashboard has nothing to say to a visitor
            $strSubstitutor = (new StrReplacer([
                'class' => "\\".$classNamespace."\\AdminHome",
                'routepath' => $routeuri,
                'routename' => $routename,
                'middleware' => "'web', 'auth'",
            ]));

            $substituted = $strSubstitutor->replace(file_get_contents(__DIR__.'/Templates/routes/home.stub'));
            file_put_contents(base_path("routes/web.php"), $substituted, FILE_APPEND);
        }

        //global menu
        //        if (! file_exists(base_path('resources/views/menu.blade.php'))) {
        //            StubGenerator::from(__DIR__.'/Templates/resources/views/menu.blade.stub', true)
        //                ->to(base_path('resources/views'), true, true)
        //                ->as('menu.blade')
        //                ->save();
        //
        //            file_put_contents(base_path('resources/views/menu.blade.php'), '<x-rpd::nav-link label="Home" route="home"  />', FILE_APPEND);
        //        }

    }

    /**
     * A fresh Laravel skeleton ships a welcome route on "/": the home generated here takes its
     * place, and leaving both would show two routes for the same path to whoever opens the file.
     */
    protected function dropSkeletonWelcomeRoute(): void
    {
        $file = base_path('routes/web.php');
        if (! is_file($file)) {
            return;
        }

        $content = file_get_contents($file);
        $cleaned = preg_replace("/Route::get\\('\\/', function \\(\\) \\{\\s*return view\\('welcome'\\);\\s*\\}\\);\\s*/", '', $content, 1);

        if ($cleaned !== null && $cleaned !== $content) {
            file_put_contents($file, $cleaned);
        }
    }
}
