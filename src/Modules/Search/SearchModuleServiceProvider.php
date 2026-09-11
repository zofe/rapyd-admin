<?php

namespace Zofe\Rapyd\Modules\Search;

use Zofe\Rapyd\Modules\RapydModuleServiceProvider;

class SearchModuleServiceProvider extends RapydModuleServiceProvider
{
    protected string $moduleName = 'Search';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/search.php', 'rapyd.search');

        // Scout is optional: only provide its defaults when the package is installed.
        if (class_exists(\Laravel\Scout\Searchable::class)) {
            $this->mergeConfigFrom(__DIR__ . '/scout.php', 'scout');
        }
    }

    public function boot(): void
    {
        if ($this->isEjected() || ! config('rapyd.search.enabled', true)) {
            return;
        }

        $this->bootAppModule('search');
    }
}
