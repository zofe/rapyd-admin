<?php

namespace Zofe\Rapyd\Modules\Companies\Authorizations;

use Zofe\Rapyd\Modules\Companies\Models\Company;

class CompanyAuth
{
    public static string $model = Company::class;

    public static function check($company, $user = null): bool
    {
        if (! $user || ! $user->company_id) {
            return false;
        }

        // Own company, or a child of it.
        return $company->id === $user->company_id
            || $company->parent_id === $user->company_id;
    }
}
