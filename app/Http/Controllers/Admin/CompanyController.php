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
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $query = Company::withCount('members', 'projects');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $companies = $query->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.companies.index', [
            'companies' => $companies,
        ]);
    }

    public function show(Company $company): View
    {
        $company->loadCount('members', 'projects');
        $members = $company->members()->orderBy('name')->get();
        $projects = $company->projects()->orderBy('name')->get();

        return view('admin.companies.show', [
            'company' => $company,
            'members' => $members,
            'projects' => $projects,
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
