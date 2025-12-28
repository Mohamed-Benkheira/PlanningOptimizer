<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use App\Policies\Concerns\HandlesRoles;

class DepartmentPolicy
{
    use HandlesRoles;

    public function viewAny(User $user): bool
    {
        // Allow Dean and Dept Head to see the list of departments
        return $this->isFullAdmin($user) || $user->isDean() || $user->isDepartmentHead();
    }

    public function view(User $user, Department $department): bool
    {
        if ($this->isFullAdmin($user) || $user->isDean()) {
            return true;
        }
        // Dept Head sees ONLY their own
        return $user->isDepartmentHead() && $user->department_id === $department->id;
    }

    public function create(User $user): bool
    {
        return $this->isFullAdmin($user);
    }

    public function update(User $user, Department $department): bool
    {
        return $this->isFullAdmin($user);
    }

    public function delete(User $user, Department $department): bool
    {
        return $this->isFullAdmin($user);
    }
}
