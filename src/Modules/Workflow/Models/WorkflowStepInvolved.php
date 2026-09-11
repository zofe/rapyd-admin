<?php

namespace Zofe\Rapyd\Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowStepInvolved extends Model
{
    protected $table = 'workflow_step_involved';

    protected $fillable = ['workflow_step_id', 'involved_type', 'involved_id'];

    protected $casts = ['involved_id' => 'string'];

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function involved(): MorphTo
    {
        return $this->morphTo();
    }
}
