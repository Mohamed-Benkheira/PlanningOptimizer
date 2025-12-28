<?php

namespace App\Policies;

use App\Models\ExamSession;
use App\Models\User;
use App\Policies\Concerns\HandlesRoles;

class ExamSessionPolicy
{
    use HandlesRoles; // Ensure this trait is used

    public function viewAny(User $user): bool
    {
        // Everyone (Dean, Dept Head, Admins) needs to see the schedule list
        return $this->isFullAdmin($user) || $user->isDepartmentHead() || $user->isDean();
    }

    public function view(User $user, ExamSession $session): bool
    {
        if ($this->isFullAdmin($user) || $user->isDean()) {
            return true;
        }

        if ($user->isDepartmentHead()) {
            // Check if the session belongs to a module in their department
            return $session->module
                && $session->module->specialty
                && $session->module->specialty->department_id === $user->department_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Only Exam Admin creates schedules
        return $user->isExamAdmin() || $user->isSuperAdmin();
    }

    public function update(User $user, ExamSession $session): bool
    {
        // Only Exam Admin edits dates/rooms
        return $user->isExamAdmin() || $user->isSuperAdmin();
    }

    public function delete(User $user, ExamSession $session): bool
    {
        return $user->isExamAdmin() || $user->isSuperAdmin();
    }
}
