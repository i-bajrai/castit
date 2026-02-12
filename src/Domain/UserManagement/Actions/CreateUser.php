<?php

namespace Domain\UserManagement\Actions;

use App\Models\User;
use Domain\UserManagement\DataTransferObjects\UserData;

class CreateUser
{
    public function execute(UserData $data): User
    {
        return User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
            'role' => $data->role,
            'company_id' => $data->companyId,
            'company_role' => $data->companyRole,
            'email_verified_at' => now(),
        ]);
    }
}
