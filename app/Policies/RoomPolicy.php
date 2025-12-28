<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;
use App\Policies\Concerns\HandlesRoles;

class RoomPolicy
{
    use HandlesRoles;

    public function viewAny(User $user): bool
    {
        return $this->isFullAdmin($user) || $user->isDepartmentHead() || $user->isDean();
    }

    public function view(User $user, Room $room): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->isFullAdmin($user);
    }

    public function update(User $user, Room $room): bool
    {
        return $this->isFullAdmin($user);
    }

    public function delete(User $user, Room $room): bool
    {
        return $this->isFullAdmin($user);
    }
}
