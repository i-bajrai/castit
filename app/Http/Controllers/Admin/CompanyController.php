<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Http\Requests\Admin\UpdateCompanyRequest;
use App\Models\Company;
use Domain\UserManagement\Actions\CreateCompany;
use Domain\UserManagement\Actions\DeleteCompany;
use Domain\UserManagement\Actions\UpdateCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::withCount('members', 'projects')
            ->orderBy('name')
            ->paginate(25);

        return view('admin.companies.index', [
            'companies' => $companies,
        ]);
    }

    public function store(StoreCompanyRequest $request, CreateCompany $action): RedirectResponse
    {
        $action->execute($request->validated()['name'], $request->user());

        return redirect()->route('admin.companies.index')
            ->with('success', 'Company created successfully.');
    }

    public function update(UpdateCompanyRequest $request, Company $company, UpdateCompany $action): RedirectResponse
    {
        $action->execute($company, $request->validated()['name']);

        return redirect()->route('admin.companies.index')
            ->with('success', 'Company updated successfully.');
    }

    public function destroy(Company $company, DeleteCompany $action): RedirectResponse
    {
        try {
            $action->execute($company);
        } catch (\DomainException $e) {
            return redirect()->route('admin.companies.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.companies.index')
            ->with('success', 'Company deleted successfully.');
    }
}
