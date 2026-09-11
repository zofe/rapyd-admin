<?php

namespace Zofe\Rapyd\Tests\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Zofe\Rapyd\Modules\Auth\Traits\Authorize;
use Zofe\Rapyd\Modules\Auth\Traits\HasRoles;
use Zofe\Rapyd\Modules\Auth\Traits\Impersonate;
use Zofe\Rapyd\Modules\Auth\Traits\Limit;
use Zofe\Rapyd\Modules\Companies\Traits\HasCompanies;

class User extends Authenticatable
{
    use HasUuids, HasRoles, Authorize, Limit, Impersonate, HasCompanies;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];
}
