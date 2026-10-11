<?php

namespace Zofe\Rapyd\Modules\Remote\Http;

use Closure;
use Illuminate\Http\Request;

/** The calling app proves itself with its token and says which user it acts for (X-Remote-User). */
class AuthenticateRemoteCaller
{
    public function handle(Request $request, Closure $next)
    {
        $token = (string) $request->bearerToken();
        $valid = $token !== '' && collect(config('rapyd.remote.callers'))->contains(fn ($secret) => hash_equals((string) $secret, $token));
        abort_unless($valid, 401);

        $userModel = config('auth.providers.users.model');
        $request->attributes->set('remote_user', $userModel::find($request->header('X-Remote-User')));

        return $next($request);
    }
}
