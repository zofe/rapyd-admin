<?php

namespace App\Modules\Auth\Traits;



use Spatie\Permission\Exceptions\UnauthorizedException;


trait Authorize {

    public static $classInstance = [];


    public function authorize($roleOrPermission, $entity = null, $user=null)
    {
        if (! $user) {
            $user = \Illuminate\Support\Facades\Auth::user();
        }

        if (! $user ) {
            return redirect()->to(route_lang('login'));
        }

        if(!app()->environment('testing')) {
            $class = get_called_class().json_encode($entity);

            if(isset(self::$classInstance[$user->id.'|'.$roleOrPermission.'|'.$class])) {
                return ;
            }
            self::$classInstance[$user->id.'|'.$roleOrPermission.'|'.$class] = 1;
        }


        //check rule or pemission
        $rolesOrPermissions = is_array($roleOrPermission)
            ? $roleOrPermission
            : explode('|', $roleOrPermission);


        if (! $user->hasAnyRole($rolesOrPermissions) && ! $user->hasAnyPermission($rolesOrPermissions)) {
            throw UnauthorizedException::forRolesOrPermissions($rolesOrPermissions);
        }


        if($entity) {
            foreach(config('auth.authorizations') as $check) {

                if(get_class($entity) == $check::$model && $entity->exists) {
                    $result = call_user_func(array($check, 'check'), $entity, $user);

                    if(!$result) {
                        abort(404);
                    };
                }

            }

        }

    }
}
