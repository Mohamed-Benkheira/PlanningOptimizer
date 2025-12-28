<?php

namespace App\Policies;

use App\Models\Professor;
use App\Models\User;
use App\Policies\Concerns\HandlesRoles;

class ProfessorPolicy
{
    use HandlesRoles;

    public function viewAny(User $user): bool
    {
        return $this->isFullAdmin($user) || $user->isDepartmentHead() || $user->isDean();
    }

    public function view(User $user, Professor $professor): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isFullAdmin($user) || $user->isDepartmentHead();
    }

    public function update(User $user, Professor $professor): bool
    {
        if ($this->isFullAdmin($user))
            return true;

        if ($user->isDepartmentHead()) {
            return $professor->department_id === $user->department_id;
        }

        return false;
    }

    public function delete(User $user, Professor $professor): bool
    {
        return $this->update($user, $professor);
    }
}
