<?php

namespace Domain\UserManagement\Actions;

use App\Models\Company;
use App\Models\User;

class CreateCompany
{
    public function execute(string $name, User $owner): Company
    {
        return Company::create([
            'name' => $name,
            'user_id' => $owner->id,
        ]);
    }
}
