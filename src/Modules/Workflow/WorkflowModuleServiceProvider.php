<?php

namespace Zofe\Rapyd\Modules\Workflow;

use Illuminate\Foundation\AliasLoader;
use Zofe\Rapyd\Modules\RapydModuleServiceProvider;

class WorkflowModuleServiceProvider extends RapydModuleServiceProvider
{
    protected string $moduleName = 'Workflow';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/workflow.php', 'rapyd.workflow');
        $this->mergeConfigFrom(__DIR__ . '/workflow_registry.php', 'workflow_registry');

        if (! config('rapyd.workflow.enabled', true)) {
            return;
        }

        $this->loadDefinitions();

        if (! $this->app->providerIsLoaded(\ZeroDaHero\LaravelWorkflow\WorkflowServiceProvider::class)) {
            $this->app->register(\ZeroDaHero\LaravelWorkflow\WorkflowServiceProvider::class);
        }
        AliasLoader::getInstance()->alias('Workflow', \ZeroDaHero\LaravelWorkflow\Facades\WorkflowFacade::class);
    }

    public function boot(): void
    {
        if ($this->isEjected() || ! config('rapyd.workflow.enabled', true)) {
            return;
        }

        $this->loadMigrationsFrom($this->srcPath('Database/Migrations'));
        $this->bootAppModule('workflow');
    }

    /**
     * Collect state machine definitions from every module's workflow.php
     * (app/Modules for ejected modules, vendor/zofe for installed packages)
     * into config('workflow'), which zerodahero/laravel-workflow reads lazily.
     */
    protected function loadDefinitions(): void
    {
        $files = array_merge(
            glob(app_path('Modules') . '/*/workflow.php') ?: [],
            glob(base_path('vendor/zofe') . '/*/workflow.php') ?: [],
            config('rapyd.workflow.definition_files', []),
        );

        $definitions = config('workflow', []);
        foreach ($files as $file) {
            $defs = require $file;
            if (! is_array($defs)) {
                continue;
            }
            foreach ($defs as $name => $definition) {
                if (is_array($definition) && ! isset($definitions[$name])) {
                    $definitions[$name] = $definition;
                }
            }
        }

        config(['workflow' => $definitions]);
    }
}
