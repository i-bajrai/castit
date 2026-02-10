<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Domain\UserManagement\Actions\CreateCompany;
use Domain\UserManagement\Actions\DeleteCompany;
use Domain\UserManagement\Actions\UpdateCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $companies = Company::withCount('members', 'projects')->orderBy('name')->get();

        return view('admin.companies.index', [
            'companies' => $companies,
        ]);
    }

    public function store(Request $request, CreateCompany $action): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $action->execute($validated['name'], $request->user());

        return redirect()->route('admin.companies.index')
            ->with('success', 'Company created successfully.');
    }

    public function update(Request $request, Company $company, UpdateCompany $action): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $action->execute($company, $validated['name']);

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
