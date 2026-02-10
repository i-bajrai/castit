<?php

namespace Domain\UserManagement\Actions;

use App\Models\User;
use DomainException;

class DeleteUser
{
    public function execute(User $user, User $currentUser): void
    {
        if ($user->id === $currentUser->id) {
            throw new DomainException('You cannot delete your own account.');
        }

        $user->delete();
    }
}
