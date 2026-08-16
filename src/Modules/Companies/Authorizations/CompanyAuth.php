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

        // User is a direct member of this company
        if ($company->users->contains($user->id)) {
            return true;
        }

        // User is a member of the parent company (tier-based access)
        if ($company->parentCompany && $company->parentCompany->users->contains($user->id)) {
            return true;
        }

        return false;
    }
}
