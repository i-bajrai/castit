<?php

namespace Domain\UserManagement\Actions;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use DomainException;

class DeleteUser
{
    public function execute(User $user, User $currentUser): void
    {
        if ($user->id === $currentUser->id) {
            throw new DomainException('You cannot delete your own account.');
        }

        if ($user->isAdmin()) {
            $otherAdmins = User::where('role', UserRole::Admin)
                ->where('id', '!=', $user->id)
                ->exists();

            if (! $otherAdmins) {
                throw new DomainException('Cannot delete the last super admin.');
            }
        }

        // Nullify company ownership if this user owns any companies
        Company::where('user_id', $user->id)->update(['user_id' => null]);

        $user->delete();
    }
}
