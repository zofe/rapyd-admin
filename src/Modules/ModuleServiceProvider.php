<?php

namespace Zofe\Rapyd\Modules;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Livewire;
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

        if (File::isDirectory($componentsBasePath)) {
            Livewire::addNamespace('livewire', null, 'App\\Livewire', $componentsBasePath);
        }
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

                if (File::exists($moduleConfigPath) && basename($moduleP) !== 'Workflow') {
                    $this->mergeConfigFrom($moduleConfigPath, $moduleName);
                    //overrire default layout
                    //                    if (config($moduleName.'.layout')) {
                    //                        config(['livewire.layout' => config($moduleName.'.layout')]);
                    //                    }
                }

                //                if (File::exists($modulePath.'workflow.php')) {
                //                    $workflows = require $modulePath.'workflow.php';
                //                    foreach ($workflows as $workflowName => $workflowDefinition) {
                //                        $registry = app()->make('workflow');
                //                        $registry->addFromArray($workflowName, $workflowDefinition);
                //                    }
                //                }

                $this->loadViewsFrom($modulePath . 'Views', $moduleName);
                $this->loadViewsFrom($modulePath . 'Livewire', $moduleName);
                $this->loadViewsFrom($modulePath . 'Components', $moduleName);
                $this->loadMigrationsFrom($modulePath . 'Database/Migrations');
                $this->loadTranslationsFrom($modulePath . 'Lang', $moduleName);


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

                // Livewire component registration via LW4 addNamespace
                $componentsDir = File::isDirectory($modulePath . 'Components')
                    ? $modulePath . 'Components'
                    : $modulePath . 'Livewire';
                $componentsDir = (string) Str::of($componentsDir)->replace(['\\'], '/');

                $classNamespace = namespace_module('App\\Livewire', basename($moduleP));
                if (File::isDirectory($componentsDir)) {
                    Livewire::addNamespace($module, null, $classNamespace, $componentsDir);
                }
                $this->registerModuleClassDirectory($modulePath);
            }
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

        foreach (['admin', 'frontend'] as $area) {
            $menus = [];
            foreach (config()->all() as $cfg) {
                if (! is_array($cfg) || ! isset($cfg["menu_{$area}"])) {
                    continue;
                }
                $menus[] = [
                    'view' => $cfg["menu_{$area}"],
                    'position' => $cfg["menu_{$area}_position"] ?? 0,
                ];
            }
            usort($menus, fn ($a, $b) => $a['position'] <=> $b['position']);
            $menuArray[$area] = array_column($menus, 'view');
        }

        config(['rapyd.menus' => $menuArray]);
    }
}
