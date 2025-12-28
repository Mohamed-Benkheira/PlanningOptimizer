<?php

namespace App\Policies;

use App\Models\Specialty;
use App\Models\User;
use App\Policies\Concerns\HandlesRoles;

class SpecialtyPolicy
{
    use HandlesRoles;

    public function viewAny(User $user): bool
    {
        return $this->isFullAdmin($user) || $user->isDepartmentHead() || $user->isDean();
    }

    public function view(User $user, Specialty $specialty): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        // Head can create specialties in their own department (if UI allows selecting dept_id automatically)
        return $this->isFullAdmin($user) || $user->isDepartmentHead();
    }

    public function update(User $user, Specialty $specialty): bool
    {
        if ($this->isFullAdmin($user))
            return true;

        if ($user->isDepartmentHead()) {
            return $specialty->department_id === $user->department_id;
        }

        return false;
    }

    public function delete(User $user, Specialty $specialty): bool
    {
        return $this->update($user, $specialty);
    }
}
