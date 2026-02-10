<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyRole;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Company;
use App\Models\User;
use Domain\UserManagement\Actions\CreateUser;
use Domain\UserManagement\Actions\DeleteUser;
use Domain\UserManagement\Actions\UpdateUser;
use Domain\UserManagement\DataTransferObjects\UserData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with('company')->orderBy('name')->paginate(25);
        $companies = Company::orderBy('name')->get();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
            'companyRoles' => CompanyRole::cases(),
            'companies' => $companies,
        ]);
    }

    public function store(StoreUserRequest $request, CreateUser $action): RedirectResponse
    {
        $validated = $request->validated();

        $data = new UserData(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
            role: UserRole::from($validated['role']),
            companyId: ! empty($validated['company_id']) ? (int) $validated['company_id'] : null,
            companyRole: ! empty($validated['company_role']) ? CompanyRole::from($validated['company_role']) : null,
        );

        $action->execute($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUser $action): RedirectResponse
    {
        $validated = $request->validated();

        $data = new UserData(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'] ?? null,
            role: UserRole::from($validated['role']),
            companyId: ! empty($validated['company_id']) ? (int) $validated['company_id'] : null,
            companyRole: ! empty($validated['company_role']) ? CompanyRole::from($validated['company_role']) : null,
        );

        try {
            $action->execute($user, $data);
        } catch (\DomainException $e) {
            return redirect()->route('admin.users.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user, DeleteUser $action): RedirectResponse
    {
        try {
            $action->execute($user, $request->user());
        } catch (\DomainException $e) {
            return redirect()->route('admin.users.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
