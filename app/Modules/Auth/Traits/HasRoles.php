<?php

namespace App\Modules\Auth\Traits;



use Spatie\Permission\Traits\HasRoles as UserHasRoles;

trait HasRoles {

    use UserHasRoles;

    public function hasRoleOrPermission($roleOrPermission)
    {
        $rolesOrPermissions = is_array($roleOrPermission)
            ? $roleOrPermission
            : explode('|', $roleOrPermission);

        if ($this->hasAnyRole($rolesOrPermissions) || $this->hasAnyPermission($rolesOrPermissions)) {
            return true;
        }

        return false;
    }
}
