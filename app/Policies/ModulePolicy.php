<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\User;
use App\Policies\Concerns\HandlesRoles;

class ModulePolicy
{
    use HandlesRoles;

    public function viewAny(User $user): bool
    {
        return $this->isFullAdmin($user) || $user->isDepartmentHead() || $user->isDean();
    }

    public function view(User $user, Module $module): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isFullAdmin($user) || $user->isDepartmentHead();
    }

    public function update(User $user, Module $module): bool
    {
        if ($this->isFullAdmin($user))
            return true;

        if ($user->isDepartmentHead()) {
            return $module->specialty
                && $module->specialty->department_id === $user->department_id;
        }

        return false;
    }

    public function delete(User $user, Module $module): bool
    {
        return $this->update($user, $module);
    }
}
