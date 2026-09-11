<?php

namespace Zofe\Rapyd\Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Zofe\Rapyd\Modules\Companies\Models\Company;
use Zofe\Rapyd\Traits\ShortId;

class WorkflowStep extends Model
{
    use ShortId;

    protected $fillable = [
        'user_id', 'company_id', 'workflowable_type', 'workflowable_id',
        'places', 'places_from', 'last_transition', 'transition_date', 'scheduled_date', 'meta',
    ];

    protected $casts = [
        'places' => 'array',
        'places_from' => 'array',
        'meta' => 'array',
        'transition_date' => 'datetime',
        'scheduled_date' => 'datetime',
    ];

    public function workflowable(): MorphTo
    {
        return $this->morphTo();
    }

    public function involved()
    {
        return $this->hasMany(WorkflowStepInvolved::class);
    }

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
