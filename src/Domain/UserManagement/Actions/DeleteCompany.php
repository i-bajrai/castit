<?php

namespace Domain\UserManagement\Actions;

use App\Models\Company;
use App\Models\User;
use DomainException;

class DeleteCompany
{
    public function execute(Company $company): void
    {
        if ($company->projects()->withTrashed()->exists()) {
            throw new DomainException('Cannot delete a company that has projects. Delete the projects first.');
        }

        // Clear all users associated with this company (active + removed)
        User::where('company_id', $company->id)->update([
            'company_id' => null,
            'company_role' => null,
            'company_removed_at' => null,
        ]);

        $company->delete();
    }
}
