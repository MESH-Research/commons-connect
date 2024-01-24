<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AdminUsersTable extends Component
{
    public Collection $users;
    public Collection $roles;

    #[Computed]
    public function userRoles() {
        $user_roles = [];
        foreach ( $this->users as $user ) {
            $user_roles[$user->id] = $user->getRoleNames()->first();
        }
        return $user_roles;
    }

    protected $rules = [
        'users.*.approved' => 'boolean',
    ];

    public function mount() {
        $this->users = User::all();
        $this->roles = Role::all();
    }
    
    public function render() {
        $a = $this->userRoles;
        return view('livewire.admin-users-table');
    }

    public function submit() {
        $this->validate();
    }
}
