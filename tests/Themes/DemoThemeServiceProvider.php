<?php

namespace Zofe\Rapyd\Tests\Themes;

use Zofe\Rapyd\Themes\RapydThemeServiceProvider;

class DemoThemeServiceProvider extends RapydThemeServiceProvider
{
    protected string $name = 'demo';

    protected string $path = __DIR__ . '/../resources/themes/demo';
}
