<?php

namespace Zofe\Rapyd;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Zofe\Rapyd\Breadcrumbs\BreadcrumbsServiceProvider;
use Zofe\Rapyd\Commands\RapydMakeCommand;
use Zofe\Rapyd\Commands\RapydMakeEditCommand;
use Zofe\Rapyd\Commands\RapydMakeHomeCommand;
use Zofe\Rapyd\Commands\RapydMakeModelCommand;
use Zofe\Rapyd\Commands\RapydMakeTableCommand;
use Zofe\Rapyd\Commands\RapydMakeViewCommand;
use Zofe\Rapyd\Http\Livewire\RapydApp;
use Zofe\Rapyd\Mechanisms\RapydTagPrecompiler;
use Zofe\Rapyd\Modules\ModuleServiceProvider;

class RapydServiceProvider extends ServiceProvider
{
    protected $shouldInjectAssets = true;
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            //            $this->publishes([
            //                __DIR__ . '/../config/rapyd.php' => config_path('rapyd.php'),
            //            ], 'config');


            $this->publishes([
                __DIR__.'/../public' => public_path('vendor/rapyd'),
                __DIR__ . '/../config/rapyd.php' => config_path('rapyd.php'),
                __DIR__ . '/../config/livewire.php' => config_path('livewire.php'),

            ], 'laravel-assets');

            $this->commands([
                RapydMakeCommand::class,
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

            return $scripts;
        });

        Blade::directive('rapydStyles', function () {
            $styles = "<link rel=\"stylesheet\" href=\"{{ asset('vendor/rapyd/rapyd.css') }}\">\n";
            // $styles .= "<link rel=\"stylesheet\" href=\"{{ asset('vendor/rapyd/bootstrap.css') }}\">";

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


        //Paginator::defaultView('pagination');

        //Artisan::call('rpd:make:home');

        /*

              Blade::directive('ifcomponent', function ($expression) {
                  return "<?php if((bool) array_key_exists($expression, app(\Livewire\LivewireComponentsFinder::class)->getManifest())): ?>\n";
              });

              Blade::directive('endifcomponent', function ($expression) {
                  return "<?php endif; ?>\n";
              });

              Livewire::component('rpd-app', RapydApp::class);

              if (! Collection::hasMacro('paginate')) {
                  Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
                      $currentPage = LengthAwarePaginator::resolveCurrentPage($pageName);
                      $total = $total ?: $this->count();
                      $items = $this->forPage($currentPage, $perPage);
                      $options = [
                          'path' => LengthAwarePaginator::resolveCurrentPath(),
                          'pageName' => $pageName,
                      ];

                      return Container::getInstance()->makeWith(
                          LengthAwarePaginator::class,
                          compact(
                              'items',
                              'total',
                              'perPage',
                              'currentPage',
                              'options'
                          )
                      )->withQueryString();
                  });
              }
              */
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
        //$this->app->register(StubGeneratorServiceProvider::class);
    }
}
