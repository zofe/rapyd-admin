<?php

namespace App\Modules\Auth\Traits;


use Illuminate\Support\Facades\Auth;

trait Limit {

    public static $classInstance = [];

    private static function limit($except=[], $user=null)
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
        foreach(config('auth.limits') as $limit) {
            call_user_func(array($limit, 'limit'), $except, $user);

        }

    }
}
