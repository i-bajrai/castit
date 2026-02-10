<?php

namespace Domain\UserManagement\DataTransferObjects;

use App\Enums\UserRole;

readonly class UserData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password,
        public UserRole $role,
    ) {}
}
