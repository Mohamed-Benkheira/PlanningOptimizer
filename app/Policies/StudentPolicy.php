<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use App\Policies\Concerns\HandlesRoles;

class StudentPolicy
{
    use HandlesRoles;

    public function viewAny(User $user): bool
    {
        return $this->isFullAdmin($user) || $user->isDepartmentHead() || $user->isDean();
    }

    public function view(User $user, Student $student): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isFullAdmin($user) || $user->isDepartmentHead();
    }

    public function update(User $user, Student $student): bool
    {
        if ($this->isFullAdmin($user))
            return true;

        if ($user->isDepartmentHead()) {
            // Check via group -> specialty -> department
            return $student->group
                && $student->group->specialty
                && $student->group->specialty->department_id === $user->department_id;
        }

        return false;
    }

    public function delete(User $user, Student $student): bool
    {
        return $this->update($user, $student);
    }
}
