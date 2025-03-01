<?php

namespace App\Modules\Auth\Livewire;



use App\Models\User;
use Livewire\Component;
use App\Modules\Auth\Traits\Authorize;


class UsersView extends Component
{
    use Authorize;
    public $user;

    public function booted()
    {
        $this->authorize('admin|edit users');
    }

    public function mount(User $user)
    {
        $this->user = $user;
    }

    public function render()
    {
        return view('auth::users_view')->layout('auth::admin');
    }
}
