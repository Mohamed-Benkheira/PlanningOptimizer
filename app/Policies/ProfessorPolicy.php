<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Professor;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProfessorPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
    }

    public function viewAny(User $user): bool
    {
        return $user->isExamAdmin()
            || $user->isDean()
            || $user->isDepartmentHead();
    }

    public function view(User $user, Professor $professor): bool
    {
        // Dept Head sees only their own professors
        if ($user->isDepartmentHead()) {
            return $professor->department_id === $user->department_id;
        }
        return true;
    }

    // CREATE/UPDATE: Admin Examens manages resources ("Optimisation des ressources")
    public function create(User $user): bool
    {
        return $user->isExamAdmin();
    }

    public function update(User $user, Professor $professor): bool
    {
        return $user->isExamAdmin();
    }

    public function delete(User $user, Professor $professor): bool
    {
        return $user->isExamAdmin();
    }
}
