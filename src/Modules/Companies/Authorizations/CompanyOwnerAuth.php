<?php

namespace Zofe\Rapyd\Modules\Companies\Authorizations;

use Zofe\Rapyd\Modules\Companies\Models\Company;

// Not registered in auth.authorizations on purpose: the generic Authorize gate would
// apply it to every Company and lock out plain members. Call it explicitly from
// owner-only actions (e.g. CompanyOwnerAuth::check($company)).
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
