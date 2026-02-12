<?php

namespace App\Http\Controllers;

use App\Enums\CompanyRole;
use App\Http\Requests\Company\StoreMemberRequest;
use App\Http\Requests\Company\UpdateMemberRequest;
use App\Models\User;
use Domain\UserManagement\Actions\AddCompanyMember;
use Domain\UserManagement\Actions\RemoveCompanyMember;
use Domain\UserManagement\Actions\RestoreCompanyMember;
use Domain\UserManagement\Actions\UpdateCompanyMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CompanyMemberController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->company;

        abort_unless((bool) $company, 404, 'You are not assigned to a company.');

        Gate::authorize('manageMembers', $company);

        $members = $company->members()->orderBy('name')->paginate(25);
        $removedMembers = $company->removedMembers()->orderBy('company_removed_at', 'desc')->get();

        return view('company.members.index', [
            'company' => $company,
            'members' => $members,
            'removedMembers' => $removedMembers,
            'companyRoles' => CompanyRole::cases(),
        ]);
    }

    public function store(StoreMemberRequest $request, AddCompanyMember $action): RedirectResponse
    {
        $company = $request->user()->company;

        abort_unless((bool) $company, 404, 'You are not assigned to a company.');

        Gate::authorize('manageMembers', $company);

        $validated = $request->validated();

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

    public function update(UpdateMemberRequest $request, User $user, UpdateCompanyMember $action): RedirectResponse
    {
        $company = $request->user()->company;

        abort_unless((bool) $company, 404, 'You are not assigned to a company.');

        Gate::authorize('manageMembers', $company);

        abort_unless($user->belongsToCompany($company->id), 404);

        $validated = $request->validated();

        $action->execute($user, CompanyRole::from($validated['company_role']));

        return redirect()->route('company.members.index')
            ->with('success', 'Member role updated.');
    }

    public function destroy(Request $request, User $user, RemoveCompanyMember $action): RedirectResponse
    {
        $company = $request->user()->company;

        abort_unless((bool) $company, 404, 'You are not assigned to a company.');

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

    public function restore(Request $request, User $user, RestoreCompanyMember $action): RedirectResponse
    {
        $company = $request->user()->company;

        abort_unless((bool) $company, 404, 'You are not assigned to a company.');

        Gate::authorize('manageMembers', $company);

        abort_unless($user->company_id === $company->id && $user->isRemovedFromCompany(), 404);

        $action->execute($user);

        return redirect()->route('company.members.index')
            ->with('success', 'Member restored to company.');
    }
}
