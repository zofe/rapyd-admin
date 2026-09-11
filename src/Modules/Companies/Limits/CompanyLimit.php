<?php

namespace Zofe\Rapyd\Modules\Companies\Limits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
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

        // Plain subqueries on the pivot: touching Company relations here would re-apply
        // this very scope and recurse forever.
        Company::addGlobalScope('onlyMine', function (Builder $builder) use ($user) {
            $mine = DB::table('company_user')->select('company_id')->where('user_id', $user->id);

            $builder->where(function ($q) use ($mine) {
                $q->whereIn('companies.id', $mine)
                  ->orWhereIn('companies.parent_id', $mine);
            });
        });
    }
}
