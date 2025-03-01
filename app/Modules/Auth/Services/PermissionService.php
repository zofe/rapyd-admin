<?php

namespace App\Modules\Auth\Services;


use App\Models\Company;
use App\Modules\Auth\Models\CompanyRoles;
use Illuminate\Support\Facades\Log;


class PermissionService {

    public static function makeCompanyPermissionsPreset(Company $company): void
    {
        if($company->hasRole('msp') && $company->companyRoles()->count()<1) {
            foreach(config('company_role_permissions') as $role=>$permissions) {
                if($role!='groups' && $role!='triggers') {
                    $companyRole = CompanyRoles::firstOrNew(['company_id'=> $company->id, 'name'=>$role]);
                    $companyRole->permissions = $permissions;
                    $companyRole->save();
                }
            }
        }
    }

    public static function removeCompanyPermissionsPreset(Company $company): void
    {
        if($company->hasRole('msp')) {
            CompanyRoles::where('company_id', $company->id)->delete();
        }
    }

}
