<?php

namespace Zofe\Rapyd\Modules\Companies\Limits;

use Illuminate\Database\Eloquent\Builder;
use Zofe\Rapyd\Modules\Companies\Models\Company;

class CompanyLimit
{
    public static function limit(array $except = [], $user = null): void
    {
        if (! $user) {
            Company::addGlobalScope('noAccess', fn (Builder $q) => $q->whereRaw('1 = 0'));

            return;
        }

        $superAdminRoles = config('rapyd.auth.super_admin_roles', ['admin']);

        if ($user->hasAnyRole($superAdminRoles)) {
            return;
        }

        Company::addGlobalScope('onlyMine', function (Builder $builder) use ($user) {
            $builder->where(function ($q) use ($user) {
                // The user's own company
                if ($user->company_id) {
                    $q->where('id', $user->company_id)
                      ->orWhere('parent_id', $user->company_id);
                }
                // Companies where the user is a member via pivot
                $q->orWhereHas('users', fn ($qq) => $qq->where('company_user.user_id', $user->id));
            });
        });
    }
}
