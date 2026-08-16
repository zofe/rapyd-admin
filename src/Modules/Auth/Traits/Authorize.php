<?php

namespace Zofe\Rapyd\Modules\Auth\Traits;

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
            redirect()->to(route_lang('login'))->send();
            exit;
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

        if ($entity) {
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
