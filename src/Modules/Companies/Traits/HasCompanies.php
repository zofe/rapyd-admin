<?php

namespace Zofe\Rapyd\Modules\Companies\Traits;

use Zofe\Rapyd\Modules\Companies\Models\Company;

/**
 * A user belongs to at most one company (users.company_id) with a role in it
 * (users.company_role: owner|member). Seeing more than one company is a matter
 * of hierarchy (parent/children), not of multiple memberships.
 */
trait HasCompanies
{
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function isOwner(): bool
    {
        return $this->company_id && $this->company_role === 'owner';
    }

    public function isOwnerOf(Company $company): bool
    {
        return $this->isOwner() && $this->company_id === $company->id;
    }

    public function belongsToCompany(Company $company): bool
    {
        return $this->company_id && $this->company_id === $company->id;
    }

    public function assignToCompany(?Company $company, string $role = 'member'): void
    {
        $this->company_id = $company?->id;
        $this->company_role = $company ? $role : null;
        $this->save();

        $this->unsetRelation('company');
    }
}
