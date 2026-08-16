<?php

namespace Zofe\Rapyd\Modules\Auth\Traits;

use Illuminate\Support\Facades\Auth;

trait Limit
{
    public static array $classInstance = [];

    private static function limit(array $except = [], $user = null): void
    {
        if (! $user) {
            $user = Auth::user();
        }

        if (! app()->environment('testing')) {
            $key = ($user ? $user->id : 'guest') . '|' . get_called_class();

            if (isset(self::$classInstance[$key])) {
                return;
            }
            self::$classInstance[$key] = 1;
        }

        foreach (config('auth.limits', []) as $limit) {
            call_user_func([$limit, 'limit'], $except, $user);
        }
    }
}
