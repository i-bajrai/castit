<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function impersonate(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot impersonate yourself.');
        }

        if ($user->isAdmin()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot impersonate another admin.');
        }

        session()->put('impersonating_from', $request->user()->id);

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function stopImpersonating(): RedirectResponse
    {
        $adminId = session()->pull('impersonating_from');

        if (! $adminId) {
            return redirect()->route('dashboard');
        }

        $admin = User::find($adminId);

        if ($admin) {
            Auth::login($admin);
        }

        return redirect()->route('admin.users.index');
    }
}
