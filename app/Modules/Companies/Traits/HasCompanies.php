<?php

namespace App\Modules\Companies\Traits;

use Zofe\Rapyd\Modules\Companies\Models\Company;

trait HasCompanies
{
    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_user')->withTimestamps();
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
