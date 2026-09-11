<?php

namespace App\Modules\Log\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Carbon\Carbon;
use Livewire\Component;
use Zofe\Rapyd\Modules\Log\Models\Activity;
use Zofe\Rapyd\Traits\WithDataTable;

class LogActivityTable extends Component
{
    use Authorize, WithDataTable;

    public string $search = '';

    public array $log_name = [];

    public array $user = [];

    public ?string $date_from = null;

    public ?string $date_to = null;

    public bool $only_users = false;

    public array $users = [];

    public array $log_names = [];

    public function booted(): void
    {
        $this->authorize('admin|view everything|view logs');
    }

    public function mount(): void
    {
        $this->sortField = 'created_at';
        $this->sortAsc = false;
        $this->perPage = (int) config('rapyd.log.activity.per_page', 50);
        $userModel = config('auth.providers.users.model');
        $this->users = $userModel::orderBy('name')->pluck('name', 'id')->toArray();
        $names = Activity::query()->distinct()->orderBy('log_name')->pluck('log_name')->filter()->all();
        $this->log_names = array_combine($names, $names);
    }

    public function updated(string $property): void
    {
        $this->resetPage();
    }

    protected function getDataSet()
    {
        $query = Activity::query()->with(['causer', 'subject']);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhere('properties', 'like', '%' . $this->search . '%')
                  ->orWhere('log_name', 'like', '%' . $this->search . '%');
            });
        }
        if ($this->log_name) {
            $query->whereIn('log_name', $this->log_name);
        }
        if ($this->user) {
            $query->whereIn('causer_id', $this->user);
        }
        if ($this->only_users) {
            $query->whereNotNull('causer_id');
        }
        if ($this->date_from) {
            $query->where('created_at', '>=', Carbon::parse($this->date_from)->startOfDay());
        }
        if ($this->date_to) {
            $query->where('created_at', '<=', Carbon::parse($this->date_to)->endOfDay());
        }

        return $query->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')->paginate($this->perPage, ['*'], $this->pageName());
    }

    public function render()
    {
        return view('log::log_activity_table', ['items' => $this->getDataSet()])->layout('log::admin');
    }
}
