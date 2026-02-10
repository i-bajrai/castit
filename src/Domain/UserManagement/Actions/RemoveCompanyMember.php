<?php

namespace Domain\UserManagement\Actions;

use App\Enums\CompanyRole;
use App\Models\User;
use DomainException;

class RemoveCompanyMember
{
    public function execute(User $user): User
    {
        if ($user->isCompanyAdmin()) {
            $otherAdmins = User::where('company_id', $user->company_id)
                ->where('id', '!=', $user->id)
                ->where('company_role', CompanyRole::Admin)
                ->exists();

            if (! $otherAdmins) {
                throw new DomainException('Cannot remove the last company admin.');
            }
        }

        $user->update([
            'company_id' => null,
            'company_role' => null,
        ]);

        return $user;
    }
}
