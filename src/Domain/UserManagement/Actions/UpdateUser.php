<?php

namespace Domain\UserManagement\Actions;

use App\Enums\UserRole;
use App\Models\User;
use Domain\UserManagement\DataTransferObjects\UserData;
use DomainException;

class UpdateUser
{
    public function execute(User $user, UserData $data): User
    {
        if ($user->isAdmin() && $data->role !== UserRole::Admin) {
            $otherAdmins = User::where('role', UserRole::Admin)
                ->where('id', '!=', $user->id)
                ->exists();

            if (! $otherAdmins) {
                throw new DomainException('Cannot demote the last super admin.');
            }
        }

        $attributes = [
            'name' => $data->name,
            'email' => $data->email,
            'role' => $data->role,
            'company_id' => $data->companyId,
            'company_role' => $data->companyId ? $data->companyRole : null,
        ];

        if ($data->password !== null) {
            $attributes['password'] = $data->password;
        }

        $user->update($attributes);

        return $user;
    }
}
