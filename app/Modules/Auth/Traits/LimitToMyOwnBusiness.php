<?php

namespace App\Modules\Auth\Traits;


use Illuminate\Support\Facades\Auth;

trait LimitToMyOwnBusiness {

    public static $classInstance = [];

    private static function limitOwnBusiness($except=[], $user=null)
    {
        if(!$user) {
            $user = Auth::user();
        }

        if(!app()->environment('testing')) {
            $class = get_called_class();
            if(isset(self::$classInstance[$user->id.'|'.$class])) {
                return ;
            }
            self::$classInstance[$user->id.'|'.$class] = 1;
        }

       //todo apply global scopes


    }
}
