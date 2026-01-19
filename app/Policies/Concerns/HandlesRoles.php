<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait HandlesRoles
{
    public function isFullAdmin(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isExamAdmin();
    }

    public function before(User $user, $ability)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
    }
}
