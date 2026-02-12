<?php

namespace Domain\UserManagement\Actions;

use App\Models\User;
use DomainException;

class RestoreCompanyMember
{
    public function execute(User $user): User
    {
        if (! $user->isRemovedFromCompany()) {
            throw new DomainException('This user is not removed from the company.');
        }

        $user->update([
            'company_removed_at' => null,
        ]);

        return $user;
    }
}
