<?php

namespace Domain\UserManagement\Actions;

use App\Enums\CompanyRole;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;

class AddCompanyMember
{
    public function execute(Company $company, string $name, string $email, string $password, CompanyRole $companyRole): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => UserRole::User,
            'company_id' => $company->id,
            'company_role' => $companyRole,
            'email_verified_at' => now(),
        ]);
    }
}
