<?php

namespace Zofe\Rapyd;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Zofe\Rapyd\Breadcrumbs\BreadcrumbsServiceProvider;
use Zofe\Rapyd\Commands\EjectCommand;
use Zofe\Rapyd\Commands\InstallCommand;
use Zofe\Rapyd\Commands\RapydMakeCommand;
use Zofe\Rapyd\Commands\RapydMakeEditCommand;
use Zofe\Rapyd\Commands\RapydMakeHomeCommand;
use Zofe\Rapyd\Commands\RapydMakeModelCommand;
use Zofe\Rapyd\Commands\RapydMakeSetupCommand;
use Zofe\Rapyd\Commands\RapydMakeTableCommand;
use Zofe\Rapyd\Commands\RapydMakeViewCommand;
use Zofe\Rapyd\Mechanisms\RapydTagPrecompiler;
use Zofe\Rapyd\Modules\Auth\AuthModuleServiceProvider;
use Zofe\Rapyd\Modules\Companies\CompaniesModuleServiceProvider;
use Zofe\Rapyd\Modules\Layout\LayoutModuleServiceProvider;
use Zofe\Rapyd\Modules\ModuleServiceProvider;
use Zofe\Rapyd\Stubs\StubGenerator;

class RapydServiceProvider extends ServiceProvider
{
    protected $shouldInjectAssets = true;
    public function boot()
    {
        \Livewire\Livewire::resolveMissingComponent(function ($name) {
            $class = collect(explode('.', $name))
                ->map(fn ($segment) => \Illuminate\Support\Str::studly($segment))
                ->implode('\\');

            if (class_exists($class) && is_subclass_of($class, \Livewire\Component::class)) {
                return $class;
            }

            return null;
        });

        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__ . '/../public' => public_path('vendor/rapyd'),
                __DIR__ . '/../config/rapyd.php' => config_path('rapyd.php'),
                __DIR__ . '/../config/livewire.php' => config_path('livewire.php'),
            ], 'laravel-assets');

            $this->publishes([
                __DIR__ . '/../config/permission.php' => config_path('permission.php'),
                __DIR__ . '/../config/fortify.php' => config_path('fortify.php'),
            ], 'rapyd-config');

            $this->commands([
                InstallCommand::class,
                EjectCommand::class,
                RapydMakeCommand::class,
                RapydMakeSetupCommand::class,
                RapydMakeHomeCommand::class,
                RapydMakeTableCommand::class,
                RapydMakeViewCommand::class,
                RapydMakeEditCommand::class,
                RapydMakeModelCommand::class,
            ]);
        }

        $this->loadViewsFrom(resource_path('views/vendor/rapyd'), 'rpd');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'rpd');
        $this->loadViewsFrom(__DIR__ . '/../resources/views/livewire', 'livewire');

        Blade::directive('rapydScripts', function () {
            $scripts = "<script src=\"{{ asset('vendor/rapyd/rapyd.js') }}\"></script>\n";
            // $scripts .= "<script src=\"{{ asset('vendor/rapyd/bootstrap.js') }}\"></script>";
            $scripts .= '<?php echo $__env->yieldPushContent(\'rapyd_scripts\'); ?>';

            return $scripts;
        });

        Blade::directive('rapydStyles', function () {
            $styles = "<link rel=\"stylesheet\" href=\"{{ asset('vendor/rapyd/rapyd.css') }}\">\n";
            // $styles .= "<link rel=\"stylesheet\" href=\"{{ asset('vendor/rapyd/bootstrap.css') }}\">";
            $styles .= '<?php echo $__env->yieldPushContent(\'rapyd_styles\'); ?>';

            return $styles;
        });

        $this->app->afterResolving('blade.compiler', function () {
            (new RapydTagPrecompiler)->register();
        });

        app('events')->listen(RequestHandled::class, function ($handled) {
            // If this is a successful HTML response...
            if (! str($handled->response->headers->get('content-type'))->contains('text/html')) {
                return;
            }
            if (! method_exists($handled->response, 'status') || $handled->response->status() !== 200) {
                return;
            }

            $html = $handled->response->getContent();


            if (str($html)->contains('</html>') && ! $this->assetsAreIncluded($html)) {
                $originalContent = $handled->response->original;
                $html = $this->injectAssets($html);
                $handled->response->setContent($html);
                $handled->response->original = $originalContent;
            }
        });
    }

    protected function assetsAreIncluded($content)
    {
        $bodyTagIncluded = strpos($content, '</body>') !== false;
        $scriptDirectiveIncluded = strpos($content, 'rapyd/rapyd.js') !== false;

        return $bodyTagIncluded && $scriptDirectiveIncluded;
    }

    protected function injectAssets($content)
    {


        $content = str_replace('</head>', Blade::render('@rapydStyles') . '</head>', $content);
        $content = str_replace('</body>', Blade::render('@rapydScripts') . '</body>', $content);

        return $content;
    }

    public function forceAssetInjection()
    {
        $this->shouldInjectAssets = true;
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/rapyd.php', 'rapyd');
        $this->mergeConfigFrom(__DIR__ . '/../config/livewire.php', 'livewire');

        $this->app->register(BreadcrumbsServiceProvider::class);
        $this->app->register(ModuleServiceProvider::class);
        $this->app->register(AuthModuleServiceProvider::class);
        $this->app->register(CompaniesModuleServiceProvider::class);
        $this->app->register(LayoutModuleServiceProvider::class);
        $this->app->bind('stub-generator', function () {
            return new StubGenerator;
        });

    }
}
