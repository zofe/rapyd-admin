<?php

namespace Zofe\Rapyd\Tests\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Zofe\Rapyd\Modules\Workflow\Traits\WorkflowTrait;

class Ticket extends Model
{
    use HasUuids, WorkflowTrait;

    protected $guarded = [];
}
