<?php

namespace Zofe\Rapyd;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Zofe\Rapyd\Rapyd
 */
class RapydFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'rapyd';
    }
}
