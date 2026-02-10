<?php

namespace Domain\UserManagement\Actions;

use App\Models\Company;

class UpdateCompany
{
    public function execute(Company $company, string $name): Company
    {
        $company->update(['name' => $name]);

        return $company;
    }
}
