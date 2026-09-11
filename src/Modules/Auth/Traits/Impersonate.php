<?php

namespace Zofe\Rapyd\Modules\Auth\Traits;

trait Impersonate
{
    use \Lab404\Impersonate\Models\Impersonate;

    public function canImpersonate(): bool
    {
        return $this->hasAnyRole(config('rapyd.auth.super_admin_roles', ['admin']));
    }

    public function canBeImpersonated(): bool
    {
        $superAdmin = config('rapyd.auth.super_admin_roles', ['admin']);

        return auth()->user()?->hasAnyRole($superAdmin) && ! $this->hasAnyRole($superAdmin);
    }
}
