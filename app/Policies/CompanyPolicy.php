<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function update(User $user, Company $company): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isViewer()) {
            return false;
        }

        return $user->id === $company->user_id;
    }
}
