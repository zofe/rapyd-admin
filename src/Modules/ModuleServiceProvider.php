<?php

namespace Zofe\Rapyd\Modules;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Livewire;
use ReflectionClass;
use Symfony\Component\Finder\SplFileInfo;
use Zofe\Rapyd\Commands\ModuleCommand;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ModuleCommand::class,
            ]);
        }

        $this->enableComponents();
        $this->enableModules();
        $this->configMenus();
    }


    private function enableComponents(): void
    {
        $componentsBasePath = $componentsPath = app_path(). '/Livewire';

        $lang_prefix = $this->detectLocaleByPrefix();
        if (File::exists($componentsBasePath.DIRECTORY_SEPARATOR.'routes.php')) {
            Route::prefix($lang_prefix)->middleware(['web'])->group($componentsBasePath.DIRECTORY_SEPARATOR.'routes.php');
        }


        $this->loadViewsFrom($componentsPath, 'livewire');
        $this->registerComponentDirectory($componentsBasePath, "App\\Livewire\\", "livewire::");
    }

    /**
     *
     * Enable module functionality
     */
    private function enableModules(): void
    {
        $moduleBasePath = $modulePath = app_path(). '/Modules/';
        config(['rapyd.modules' => []]);
        config(['auth.authorizations' => []]);
        config(['auth.limits' => []]);

        if (File::exists($moduleBasePath)) {
            $dirs = File::directories($moduleBasePath);

            foreach ($dirs as $moduleP) {
                $module = Str::lower(basename($moduleP));
                $modulePath = $moduleP . DIRECTORY_SEPARATOR;

                $moduleName = Str::snake($module);
                $moduleConfigPath = $modulePath . 'config.php';
                $modules[] = $module;

                config(['rapyd.modules' => $modules]);
                $lang_prefix = $this->detectLocaleByPrefix();
                if (File::exists($modulePath.'routes.php')) {
                    Route::prefix($lang_prefix)->middleware(['web'])->group($modulePath.'routes.php');
                }

                if (File::exists($moduleConfigPath)) {
                    $this->mergeConfigFrom($moduleConfigPath, $moduleName);
                    //overrire default layout
                    if (config($moduleName.'.layout')) {
                        config(['livewire.layout' => config($moduleName.'.layout')]);
                    }
                }
                $this->loadViewsFrom($modulePath . 'Views', $moduleName);
                $this->loadViewsFrom($modulePath . 'Livewire', $moduleName);//'components');
                $this->loadMigrationsFrom($modulePath . 'Database/Migrations');
                $this->loadTranslationsFrom($modulePath . 'Lang', $moduleName);

                $routePrefix = $lang_prefix . '/';

                //                if (File::exists($modulePath . 'Components/routes.php')) {
                //                    Route::prefix($routePrefix)->middleware(config($module . '.route_middleware', ['web']))
                //                        ->group($modulePath . 'Components/routes.php');
                //                }

                if ($this->app->runningInConsole()) {
                    $moduleCommands = [];
                    $commandDirPath = $modulePath . 'Commands';
                    if (File::isDirectory($commandDirPath)) {
                        foreach (File::files($modulePath . 'Commands') as $file) {
                            $pathInfo = pathinfo($file);
                            $moduleCommands[] = namespace_module("App\\Commands\\{$pathInfo['filename']}", Str::studly($module));
                        }
                        $this->commands($moduleCommands);
                    }
                }


                // register service provider
                //                $moduleProviders = config($module . '.providers', []);
                //                foreach ($moduleProviders as $provider) {
                //                    dd($provider);
                //                    $this->app->register($provider);
                //                }

                //register livewire components
                $directory = (string)Str::of($modulePath . 'Livewire')
                    ->replace(['\\'], '/');

                $namespace = namespace_module('App\\Livewire\\',  basename($modulePath));//Str::studly($module));
                $this->registerComponentDirectory($directory, $namespace, Str::lower($module) . '::');
                $this->registerModuleClassDirectory($modulePath);
            }

            //            //todo se non c'è modulo Layout mettere per default layout:: puntato a rapyd:: ?
            //            if(!in_array('Layout',collect($dirs)->map(function ($dir){
            //                return basename($dir);
            //            })->toArray())){
            //                $this->loadViewsFrom(resource_path('views'), 'layout');
            //            }
        }
    }

    protected function registerComponentDirectory($directory, $namespace, $aliasPrefix = '')
    {
        $filesystem = new Filesystem();

        if (! $filesystem->isDirectory($directory)) {
            return false;
        }

        $directories = [];
        if (basename($directory) == 'Livewire') {
            foreach ($filesystem->directories($directory) as $dir) {
                $component_namespace = $namespace.basename($dir);
                $directories[$component_namespace] = $dir;
            }
            $directories[rtrim($namespace, '\\')] = $directory;
            // dd($directories);
        } else {
            $directories[$namespace] = $directory;
        }

        //livewire manifest
        $defaultManifestPath = $this->app['livewire']->isRunningServerless()
            ? '/tmp/storage/bootstrap/cache/livewire-components.php'
            : app()->bootstrapPath('cache/livewire-components.php');
        $livewire_manifest = config('livewire.manifest_path') ?: $defaultManifestPath;
        $livewire_array = [];
        if (file_exists($livewire_manifest)) {
            $livewire_array = include $livewire_manifest;
        }

        foreach ($directories as $namespace => $directory) {
            collect($filesystem->allFiles($directory))
                ->map(function (SplFileInfo $file) use ($namespace) {
                    return (string) Str::of($namespace)
                        ->append('\\', $file->getRelativePathname())
                        ->replace(['/', '.php'], ['\\', '']);
                })
                ->filter(function ($class) {
                    return is_subclass_of($class, Component::class) && ! (new ReflectionClass($class))->isAbstract();
                })
                ->each(function ($class) use ($namespace, $aliasPrefix, $livewire_array, $livewire_manifest) {
                    $alias = $aliasPrefix.Str::kebab(Str::replace('\\', '', Str::after($class, 'Livewire\\')));

                    Livewire::component($alias, $class);

                    if (! isset($livewire_array[$alias])) {
                        $livewire_array[$alias] = $class;
                        file_put_contents($livewire_manifest, "<?php\nreturn ".var_export($livewire_array, true).";");
                    }
                });
        }
    }


    public function registerModuleClassDirectory($modulePath)
    {
        $filesystem = new Filesystem();

        $limitsDirPath = $modulePath . 'Authorizations';
        if (File::isDirectory($limitsDirPath)) {
            $checks = config('auth.authorizations', []);
            $namespace = namespace_module('App\\Authorizations\\',  basename($modulePath));
            foreach ($filesystem->files($limitsDirPath) as $file) {
                $checks[] = $namespace.$file->getBasename('.' . $file->getExtension());
            }
            config(['auth.authorizations' => $checks]);
        }
        $limitsDirPath = $modulePath . 'Limits';
        if (File::isDirectory($limitsDirPath)) {
            $limits = config('auth.limits', []);
            $namespace = namespace_module('App\\Limits\\',  basename($modulePath));
            foreach ($filesystem->files($limitsDirPath) as $file) {
                $limits[] = $namespace.$file->getBasename('.' . $file->getExtension());
            }
            config(['auth.limits' => $limits]);
        }
    }


    public function detectLocaleByPrefix()
    {
        $lang_prefix = '';
        $locale = request()->segment(1);

        if (in_array($locale, config('app.locales', ['en']))) {
            $lang_prefix = ($locale !== config('app.fallback_locale')) ? $locale : '';

            if ($lang_prefix) {
                session(['lang_prefix' => $lang_prefix]);
            }
            app()->setlocale($locale);
        }

        return $lang_prefix;
    }

    public function configMenus()
    {
        $menuArray = ['admin' => [], 'frontend' => []];
        foreach (['admin','frontend'] as $area) {
            foreach (config('rapyd.modules') as $module) {
                $config = config($module);
                if (isset($config["menu_{$area}"])) {
                    $position = isset($config["menu_{$area}_position"]) ? $config["menu_{$area}_position"] : 0;
                    $menuArray[$area][] = [
                        "menu_{$area}" => $config["menu_{$area}"],
                        "menu_{$area}_position" => $position,
                    ];
                }
            }
            usort($menuArray[$area], function ($a, $b) use ($area) {
                return $a["menu_{$area}_position"] <=> $b["menu_{$area}_position"];
            });

            $menuArray[$area] = array_column($menuArray[$area], "menu_{$area}");
        }
        config(['rapyd.menus' => $menuArray]);
    }
}
