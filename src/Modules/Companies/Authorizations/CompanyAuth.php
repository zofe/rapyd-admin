<?php

namespace Zofe\Rapyd\Modules\Companies\Authorizations;

use Zofe\Rapyd\Modules\Companies\Models\Company;

class CompanyAuth
{
    public static string $model = Company::class;

    public static function check($company, $user = null): bool
    {
        if (! $user) {
            return false;
        }

        // Direct member via pivot
        if ($company->users()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Member of the parent company inherits access to children
        if ($company->parentCompany && $company->parentCompany->users()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return false;
    }
}
