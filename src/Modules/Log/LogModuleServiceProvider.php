<?php

namespace Zofe\Rapyd\Modules\Log;

use Illuminate\Support\Facades\Event;
use Zofe\Rapyd\Modules\Log\Ai\LogAiToolProvider;
use Zofe\Rapyd\Modules\Log\Listeners\LogAuthEvents;
use Zofe\Rapyd\Modules\Log\Models\Activity;
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

        if (class_exists(\Spatie\Activitylog\ActivitylogServiceProvider::class)) {
            if (! $this->app->providerIsLoaded(\Spatie\Activitylog\ActivitylogServiceProvider::class)) {
                $this->app->register(\Spatie\Activitylog\ActivitylogServiceProvider::class);
            }
            // Our model unless the app published its own choice.
            if (config('activitylog.activity_model') === \Spatie\Activitylog\Models\Activity::class) {
                config(['activitylog.activity_model' => Activity::class]);
            }
            config(['activitylog.delete_records_older_than_days' => config('rapyd.log.activity.delete_records_older_than_days', 180)]);
        }
    }

    public function boot(): void
    {
        if ($this->isEjected() || ! config('rapyd.log.enabled', true)) {
            return;
        }

        $this->loadMigrationsFrom($this->srcPath('Database/Migrations'));
        $this->bootAppModule('log');

        if (config('rapyd.log.activity.enabled', true) && config('rapyd.log.activity.track_auth', true)) {
            Event::subscribe(LogAuthEvents::class);
        }

        if (class_exists(\Zofe\Ai\AiRegistry::class)) {
            \Zofe\Ai\AiRegistry::register(new LogAiToolProvider());
        }
    }
}
