<?php

namespace Zofe\Rapyd\Tests\Modules;

use Zofe\Rapyd\Modules\RapydModuleServiceProvider;

/** An external module package, the way zofe/demo-module is built. */
class SampleModuleServiceProvider extends RapydModuleServiceProvider
{
    protected string $moduleName = 'Sample';

    protected ?string $modulePath = __DIR__ . '/../resources/modules/Sample';

    public function boot(): void
    {
        if ($this->isEjected()) {
            return;
        }

        $this->bootAppModule('sample');
    }
}
