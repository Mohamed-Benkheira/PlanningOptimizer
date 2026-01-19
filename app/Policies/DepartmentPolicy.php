<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Department;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
    }

    // VIEW: Needed for "Vue stratégique" (Dean) and filtering (Admin/Dept Head)
    public function viewAny(User $user): bool
    {
        return $user->isExamAdmin()
            || $user->isDean()
            || $user->isDepartmentHead();
    }

    // CREATE/UPDATE: Not in PDF functionalities for these actors. 
    // Usually reserved for Super Admin (IT Support).
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Department $department): bool
    {
        return false;
    }

    public function delete(User $user, Department $department): bool
    {
        return false;
    }
}
