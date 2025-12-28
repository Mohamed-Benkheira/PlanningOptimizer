<?php

namespace App\Policies;

use App\Models\Inscription;
use App\Models\User;
use App\Policies\Concerns\HandlesRoles;

class InscriptionPolicy
{
    use HandlesRoles;

    public function viewAny(User $user): bool
    {
        return $this->isFullAdmin($user) || $user->isDepartmentHead() || $user->isDean();
    }

    public function view(User $user, Inscription $inscription): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isFullAdmin($user) || $user->isDepartmentHead();
    }

    public function update(User $user, Inscription $inscription): bool
    {
        if ($this->isFullAdmin($user))
            return true;

        if ($user->isDepartmentHead()) {
            // Check if student belongs to department
            return $inscription->student
                && $inscription->student->group
                && $inscription->student->group->specialty
                && $inscription->student->group->specialty->department_id === $user->department_id;
        }

        return false;
    }

    public function delete(User $user, Inscription $inscription): bool
    {
        return $this->update($user, $inscription);
    }
}
