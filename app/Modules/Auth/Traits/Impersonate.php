<?php

namespace App\Modules\Auth\Traits;


trait Impersonate
{
    use \Lab404\Impersonate\Models\Impersonate;

    public function canImpersonate()
    {
        return $this->hasRole(['admin']);
    }

    public function canBeImpersonated()
    {
        //un admin puo' impersonale chi vuole ma non un altro admin
        if (auth()->user()->hasRole(['admin']) && ! $this->hasRole('admin')
        ) {
            return true;
        }

        return false;
    }

}
