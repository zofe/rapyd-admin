<?php

namespace Zofe\Rapyd\Modules\Companies\Authorizations;

use Zofe\Rapyd\Modules\Companies\Models\Company;

class CompanyOwnerAuth
{
    public static string $model = Company::class;

    public static function check($company, $user = null): bool
    {
        if (! $user) {
            return false;
        }

        return $company->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }
}
