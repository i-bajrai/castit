<?php

namespace Domain\UserManagement\Actions;

use App\Enums\CompanyRole;
use App\Models\User;

class UpdateCompanyMember
{
    public function execute(User $user, CompanyRole $companyRole): User
    {
        $user->update(['company_role' => $companyRole]);

        return $user;
    }
}
