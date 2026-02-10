<?php

namespace Domain\UserManagement\Actions;

use App\Models\Company;

class CreateCompany
{
    public function execute(string $name): Company
    {
        return Company::create(['name' => $name]);
    }
}
