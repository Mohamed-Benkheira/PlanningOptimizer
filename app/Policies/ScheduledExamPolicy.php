<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ScheduledExam;
use Illuminate\Auth\Access\HandlesAuthorization;

class ScheduledExamPolicy
{
    use HandlesAuthorization;

    /**
     * SUPER ADMIN: Has total access to everything (IT/Maintenance role).
     */
    public function before(User $user, $ability)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
    }

    /**
     * VIEW ANY:
     * - Dean: Needs "Vue stratégique globale"
     * - Exam Admin: Needs "Génération automatique"
     * - Dept Head: Needs "Validation par département"
     */
    public function viewAny(User $user): bool
    {
        return $user->isExamAdmin()
            || $user->isDean()
            || $user->isDepartmentHead();
    }

    /**
     * VIEW SINGLE EXAM:
     * - Dept Head: STRICTLY restricted to their own department's modules.
     * - Dean/Admin: Can see all.
     */
    public function view(User $user, ScheduledExam $scheduledExam): bool
    {
        if ($user->isDepartmentHead()) {
            // Check if the exam's module belongs to the user's department
            return $scheduledExam->module->department_id === $user->department_id;
        }

        return $user->isDean() || $user->isExamAdmin();
    }

    /**
     * CREATE:
     * - PDF says: "Administrateur examens ... Génération automatique EDT"
     * - Dept Head/Dean do NOT create schedules manually.
     */
    public function create(User $user): bool
    {
        return $user->isExamAdmin();
    }

    /**
     * UPDATE:
     * - PDF says: "Administrateur examens ... Optimisation des ressources"
     * - Only Admin can move exams to solve conflicts.
     */
    public function update(User $user, ScheduledExam $scheduledExam): bool
    {
        return $user->isExamAdmin();
    }

    /**
     * DELETE:
     * - Only Admin can remove exams.
     */
    public function delete(User $user, ScheduledExam $scheduledExam): bool
    {
        return $user->isExamAdmin();
    }
}
