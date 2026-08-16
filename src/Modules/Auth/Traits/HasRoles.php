<?php

namespace Zofe\Rapyd\Modules\Auth\Traits;

use Spatie\Permission\Traits\HasRoles as SpatieHasRoles;

trait HasRoles
{
    use SpatieHasRoles;

    public function hasRoleOrPermission($roleOrPermission): bool
    {
        $rolesOrPermissions = is_array($roleOrPermission)
            ? $roleOrPermission
            : explode('|', $roleOrPermission);

        return $this->hasAnyRole($rolesOrPermissions)
            || $this->hasAnyPermission($rolesOrPermissions);
    }
}
