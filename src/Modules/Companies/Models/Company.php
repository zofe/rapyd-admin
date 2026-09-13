<?php

namespace Zofe\Rapyd\Modules\Companies\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Zofe\Rapyd\Modules\Addresses\Traits\HasAddresses;
use Zofe\Rapyd\Modules\Log\LogOptions;
use Zofe\Rapyd\Modules\Log\Traits\LogsActivity;
use Zofe\Rapyd\Traits\ShortId;
use Zofe\Rapyd\Traits\SSearch;

class Company extends Model
{
    use HasUuids;
    use ShortId;
    use LogsActivity;
    use SSearch;

    public static array $searchableColumns = ['business_name', 'email', 'vat'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('company')
            ->logOnly(['business_name', 'email', 'vat', 'phone', 'status', 'tier', 'parent_id']);
    }
    use HasAddresses;
    use SoftDeletes;

    protected $table = 'companies';

    protected $fillable = [
        'parent_id',
        'owner_id',
        'business_name',
        'status',
        'name',
        'email',
        'vat',
        'vat_validated_at',
        'phone',
        'mobile',
        'website',
        'registration_date',
        'activation_date',
        'note',
        'tier',
    ];

    protected $casts = [
        'vat_validated_at' => 'datetime',
        'registration_date' => 'datetime',
        'activation_date' => 'datetime',
    ];

    public function users()
    {
        return $this->hasMany(config('auth.providers.users.model'), 'company_id');
    }

    public function owners()
    {
        return $this->users()->where('company_role', 'owner');
    }

    public function members()
    {
        return $this->users()->where('company_role', 'member');
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

    public function addUser($user, string $role = 'member', bool $asPrimary = false): void
    {
        $this->users()->syncWithoutDetaching([
            $user->id => ['role' => $role, 'is_primary' => $asPrimary],
        ]);

        if ($asPrimary) {
            $user->company_id = $this->id;
            $user->save();
        }
    }
}
