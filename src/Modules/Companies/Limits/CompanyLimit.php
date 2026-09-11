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
                // Direct member via pivot
                $q->whereHas('users', fn ($qq) => $qq->where('user_id', $user->id));
                // Children of a company where the user is a member
                $q->orWhereHas('parentCompany.users', fn ($qq) => $qq->where('user_id', $user->id));
            });
        });
    }
}
