<?php

namespace Zofe\Rapyd\Modules;

/**
 * A module's config.php may declare 'permissions' and 'role_permissions': they join
 * the ones of the Auth module in auth.permissions / auth.role_permissions, where
 * AuthSeeder creates them and authorize() checks them. Used for app modules
 * (ModuleServiceProvider) and for module packages (RapydModuleServiceProvider).
 */
trait MergesPermissions
{
    protected function mergePermissions(?array $module): void
    {
        if (empty($module['permissions'])) {
            return;
        }
        config(['auth.permissions' => array_values(array_unique(array_merge(config('auth.permissions', []), $module['permissions'])))]);
        foreach ($module['role_permissions'] ?? [] as $role => $permissions) {
            config(["auth.role_permissions.{$role}" => array_values(array_unique(array_merge(config("auth.role_permissions.{$role}", []), $permissions)))]);
        }
    }
}
