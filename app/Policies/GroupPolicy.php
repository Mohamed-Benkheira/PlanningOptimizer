<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use App\Policies\Concerns\HandlesRoles;

class GroupPolicy
{
    use HandlesRoles;

    public function viewAny(User $user): bool
    {
        return $this->isFullAdmin($user) || $user->isDepartmentHead() || $user->isDean();
    }

    public function view(User $user, Group $group): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isFullAdmin($user) || $user->isDepartmentHead();
    }

    public function update(User $user, Group $group): bool
    {
        if ($this->isFullAdmin($user))
            return true;

        if ($user->isDepartmentHead()) {
            // Check if group's specialty belongs to user's department
            return $group->specialty && $group->specialty->department_id === $user->department_id;
        }

        return false;
    }

    public function delete(User $user, Group $group): bool
    {
        return $this->update($user, $group);
    }
}
