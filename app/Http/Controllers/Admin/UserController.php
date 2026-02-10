<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Domain\UserManagement\Actions\CreateUser;
use Domain\UserManagement\Actions\DeleteUser;
use Domain\UserManagement\Actions\UpdateUser;
use Domain\UserManagement\DataTransferObjects\UserData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::orderBy('name')->get();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
        ]);
    }

    public function store(Request $request, CreateUser $action): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', new Enum(UserRole::class)],
        ]);

        $data = new UserData(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'],
            role: UserRole::from($validated['role']),
        );

        $action->execute($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user, UpdateUser $action): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', new Enum(UserRole::class)],
        ]);

        $data = new UserData(
            name: $validated['name'],
            email: $validated['email'],
            password: $validated['password'] ?? null,
            role: UserRole::from($validated['role']),
        );

        $action->execute($user, $data);

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
