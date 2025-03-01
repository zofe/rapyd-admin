<?php

namespace App\Modules\Auth;

use Illuminate\Support\Facades\Facade;


class AuthFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'zofe-auth';
    }
}
