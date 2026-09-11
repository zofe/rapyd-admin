<?php

namespace Zofe\Rapyd\Modules\Companies\Traits;

use Zofe\Rapyd\Modules\Companies\Models\Company;

trait HasCompanies
{
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user')
            ->withPivot('role', 'is_primary')
            ->withTimestamps();
    }

    // Quick accessor via denormalized company_id column — stays in sync when attaching
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function primaryCompany(): ?Company
    {
        return $this->companies()->wherePivot('is_primary', true)->first();
    }

    public function companiesAsOwner()
    {
        return $this->companies()->wherePivot('role', 'owner');
    }

    public function roleInCompany(Company $company): ?string
    {
        $pivot = $this->companies()
            ->where('companies.id', $company->id)
            ->first()?->pivot;

        return $pivot?->role;
    }

    public function isOwnerOf(Company $company): bool
    {
        return $this->roleInCompany($company) === 'owner';
    }

    public function attachToCompany(Company $company, string $role = 'member', bool $asPrimary = false): void
    {
        $this->companies()->syncWithoutDetaching([
            $company->id => ['role' => $role, 'is_primary' => $asPrimary],
        ]);

        if ($asPrimary) {
            $this->company_id = $company->id;
            $this->save();
        }
    }
}
