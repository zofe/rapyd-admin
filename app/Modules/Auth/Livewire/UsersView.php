<?php

namespace App\Modules\Auth\Livewire;

use App\Modules\Auth\Traits\Authorize;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class UsersView extends Component
{
    use Authorize;

    public $user;

    public function booted()
    {
        $this->authorize('admin|edit users');
    }

    public function mount($user)
    {
        $userModel = config('auth.providers.users.model');
        $this->user = $user instanceof Model ? $user : $userModel::findOrFail($user);
    }

    public function hasCompanies(): bool
    {
        return config('rapyd.companies.enabled') && method_exists($this->user, 'company');
    }

    public function render()
    {
        return view('auth::users_view')->layout('auth::admin');
    }
}
