<?php

namespace Zofe\Rapyd\Modules\Log;

use Zofe\Rapyd\Modules\Log\Ai\LogAiToolProvider;
use Zofe\Rapyd\Modules\RapydModuleServiceProvider;

class LogModuleServiceProvider extends RapydModuleServiceProvider
{
    protected string $moduleName = 'Log';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/log.php', 'rapyd.log');

        if (file_exists($this->appModulePath('config.php'))) {
            $this->mergeConfigFrom($this->appModulePath('config.php'), 'log');
        }
    }

    public function boot(): void
    {
        if ($this->isEjected() || ! config('rapyd.log.enabled', true)) {
            return;
        }

        $this->bootAppModule('log');

        if (class_exists(\Zofe\Ai\AiRegistry::class)) {
            \Zofe\Ai\AiRegistry::register(new LogAiToolProvider());
        }
    }
}
