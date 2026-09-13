<?php

namespace Zofe\Rapyd\Modules\Auth\Traits;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;

trait Authorize
{
    public static array $classInstance = [];

    public function authorize($roleOrPermission, $entity = null, $user = null): void
    {
        if (! $user) {
            $user = Auth::user();
        }

        if (! $user) {
            // A plain response, not the redirect() helper: inside a Livewire component the
            // helper returns Livewire's Redirector, which has no send() on some 4.x releases.
            throw new HttpResponseException(new RedirectResponse(route_lang('login')));
        }

        if (! app()->environment('testing')) {
            $key = $user->id . '|' . $roleOrPermission . '|' . get_called_class() . json_encode($entity);

            if (isset(self::$classInstance[$key])) {
                return;
            }
            self::$classInstance[$key] = 1;
        }

        $rolesOrPermissions = is_array($roleOrPermission)
            ? $roleOrPermission
            : explode('|', $roleOrPermission);

        if (! $user->hasAnyRole($rolesOrPermissions) && ! $user->hasAnyPermission($rolesOrPermissions)) {
            throw UnauthorizedException::forRolesOrPermissions($rolesOrPermissions);
        }

        if ($entity && ! $user->hasAnyRole(config('rapyd.auth.super_admin_roles', ['admin']))) {
            foreach (config('auth.authorizations', []) as $check) {
                if (get_class($entity) === $check::$model && $entity->exists) {
                    if (! call_user_func([$check, 'check'], $entity, $user)) {
                        abort(404);
                    }
                }
            }
        }
    }
}
