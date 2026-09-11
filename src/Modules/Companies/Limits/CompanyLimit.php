<?php

namespace Zofe\Rapyd\Modules\Companies\Limits;

use Illuminate\Database\Eloquent\Builder;
use Zofe\Rapyd\Modules\Companies\Models\Company;

class CompanyLimit
{
    public static function limit(array $except = [], $user = null): void
    {
        if ($user && $user->hasAnyRole(config('rapyd.auth.super_admin_roles', ['admin']))) {
            return;
        }

        // Guests and users without a company see nothing.
        if (! $user || ! $user->company_id) {
            Company::addGlobalScope('noAccess', fn (Builder $q) => $q->whereRaw('1 = 0'));

            return;
        }

        // Own company plus its children.
        Company::addGlobalScope('onlyMine', function (Builder $builder) use ($user) {
            $builder->where(function ($q) use ($user) {
                $q->where('companies.id', $user->company_id)
                  ->orWhere('companies.parent_id', $user->company_id);
            });
        });
    }
}
