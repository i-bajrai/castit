<?php

namespace Domain\UserManagement\Actions;

use App\Models\Company;
use DomainException;

class DeleteCompany
{
    public function execute(Company $company): void
    {
        if ($company->projects()->exists()) {
            throw new DomainException('Cannot delete a company that has projects. Delete the projects first.');
        }

        $company->members()->update([
            'company_id' => null,
            'company_role' => null,
        ]);

        $company->delete();
    }
}
