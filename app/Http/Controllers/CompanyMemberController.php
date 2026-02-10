<?php

namespace App\Http\Controllers;

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\User;
use Domain\UserManagement\Actions\AddCompanyMember;
use Domain\UserManagement\Actions\RemoveCompanyMember;
use Domain\UserManagement\Actions\UpdateCompanyMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class CompanyMemberController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->company;

        Gate::authorize('manageMembers', $company);

        $members = $company->members()->orderBy('name')->get();

        return view('company.members.index', [
            'company' => $company,
            'members' => $members,
            'companyRoles' => CompanyRole::cases(),
        ]);
    }

    public function store(Request $request, AddCompanyMember $action): RedirectResponse
    {
        $company = $request->user()->company;

        Gate::authorize('manageMembers', $company);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company_role' => ['required', new Enum(CompanyRole::class)],
        ]);

        $action->execute(
            $company,
            $validated['name'],
            $validated['email'],
            $validated['password'],
            CompanyRole::from($validated['company_role']),
        );

        return redirect()->route('company.members.index')
            ->with('success', 'Member added successfully.');
    }

    public function update(Request $request, User $user, UpdateCompanyMember $action): RedirectResponse
    {
        $company = $request->user()->company;

        Gate::authorize('manageMembers', $company);

        abort_unless($user->belongsToCompany($company->id), 404);

        $validated = $request->validate([
            'company_role' => ['required', new Enum(CompanyRole::class)],
        ]);

        $action->execute($user, CompanyRole::from($validated['company_role']));

        return redirect()->route('company.members.index')
            ->with('success', 'Member role updated.');
    }

    public function destroy(Request $request, User $user, RemoveCompanyMember $action): RedirectResponse
    {
        $company = $request->user()->company;

        Gate::authorize('manageMembers', $company);

        abort_unless($user->belongsToCompany($company->id), 404);
        abort_if($user->id === $request->user()->id, 403, 'You cannot remove yourself.');

        try {
            $action->execute($user);
        } catch (\DomainException $e) {
            return redirect()->route('company.members.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('company.members.index')
            ->with('success', 'Member removed from company.');
    }
}
