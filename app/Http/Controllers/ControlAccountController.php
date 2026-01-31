<?php

namespace App\Http\Controllers;

use App\Models\ControlAccount;
use App\Models\Project;
use Domain\Forecasting\Actions\CreateControlAccount;
use Domain\Forecasting\Actions\DeleteControlAccount;
use Domain\Forecasting\Actions\UpdateControlAccount;
use Domain\Forecasting\DataTransferObjects\ControlAccountData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ControlAccountController extends Controller
{
    public function bulkStore(Request $request, Project $project, CreateControlAccount $action): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'accounts' => 'required|array|min:1',
            'accounts.*.code' => 'required|string|max:255',
            'accounts.*.description' => 'required|string|max:255',
        ]);

        $existingCount = $project->controlAccounts()->count();

        foreach ($validated['accounts'] as $index => $account) {
            $data = new ControlAccountData(
                phase: '',
                code: $account['code'],
                description: $account['description'],
                category: null,
                sortOrder: $existingCount + $index,
            );

            $action->execute($project, $data);
        }

        return redirect()->route('projects.show', $project);
    }

    public function store(Request $request, Project $project, CreateControlAccount $action): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'phase' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'baseline_budget' => 'required|numeric|min:0',
            'approved_budget' => 'required|numeric|min:0',
            'sort_order' => 'required|integer|min:0',
        ]);

        $data = new ControlAccountData(
            phase: $validated['phase'],
            code: $validated['code'],
            description: $validated['description'],
            category: $validated['category'] ?? null,
            baselineBudget: (float) $validated['baseline_budget'],
            approvedBudget: (float) $validated['approved_budget'],
            sortOrder: (int) $validated['sort_order'],
        );

        $action->execute($project, $data);

        return redirect()->route('projects.settings', $project)
            ->with('success', 'Control account created.');
    }

    public function update(
        Request $request,
        Project $project,
        ControlAccount $controlAccount,
        UpdateControlAccount $action,
    ): RedirectResponse {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'phase' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'baseline_budget' => 'required|numeric|min:0',
            'approved_budget' => 'required|numeric|min:0',
            'sort_order' => 'required|integer|min:0',
        ]);

        $data = new ControlAccountData(
            phase: $validated['phase'],
            code: $validated['code'],
            description: $validated['description'],
            category: $validated['category'] ?? null,
            baselineBudget: (float) $validated['baseline_budget'],
            approvedBudget: (float) $validated['approved_budget'],
            sortOrder: (int) $validated['sort_order'],
        );

        $action->execute($controlAccount, $data);

        return redirect()->route('projects.settings', $project)
            ->with('success', 'Control account updated.');
    }

    public function destroy(
        Project $project,
        ControlAccount $controlAccount,
        DeleteControlAccount $action,
    ): RedirectResponse {
        Gate::authorize('update', $project);

        $action->execute($controlAccount);

        return redirect()->route('projects.settings', $project)
            ->with('success', 'Control account deleted.');
    }
}
