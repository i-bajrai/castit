<?php

namespace Domain\UserManagement\Actions;

use App\Models\User;
use Domain\UserManagement\DataTransferObjects\UserData;

class UpdateUser
{
    public function execute(User $user, UserData $data): User
    {
        $attributes = [
            'name' => $data->name,
            'email' => $data->email,
            'role' => $data->role,
            'company_id' => $data->companyId,
            'company_role' => $data->companyRole,
        ];

        if ($data->password !== null) {
            $attributes['password'] = $data->password;
        }

        $user->update($attributes);

        return $user;
    }
}
