<?php

namespace Zofe\Rapyd\Modules\Companies\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'companies';

    protected $fillable = [
        'parent_id',
        'owner_id',
        'business_name',
        'status',
        'name',
        'email',
        'vat',
        'phone',
        'mobile',
        'website',
        'registration_date',
        'activation_date',
        'note',
        'tier',
    ];

    protected $casts = [
        'registration_date' => 'datetime',
        'activation_date'   => 'datetime',
    ];

    public function users()
    {
        return $this->belongsToMany(config('auth.providers.users.model'), 'company_user')
            ->withTimestamps();
    }

    public function parent()
    {
        return $this->belongsTo(Company::class, 'parent_id');
    }

    public function parentCompany()
    {
        return $this->belongsTo(Company::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Company::class, 'parent_id');
    }
}
