<?php

namespace App\Http\Controllers;

use App\Models\ControlAccount;
use App\Models\CostPackage;
use App\Models\Project;
use Domain\Forecasting\Actions\CreateCostPackage;
use Domain\Forecasting\Actions\DeleteCostPackage;
use Domain\Forecasting\Actions\UpdateCostPackage;
use Domain\Forecasting\DataTransferObjects\CostPackageData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CostPackageController extends Controller
{
    public function store(Request $request, Project $project, CreateCostPackage $action): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'control_account_id' => 'required|exists:control_accounts,id',
            'item_no' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'sort_order' => 'required|integer|min:0',
        ]);

        $controlAccount = ControlAccount::where('id', $validated['control_account_id'])
            ->where('project_id', $project->id)
            ->firstOrFail();

        $data = new CostPackageData(
            name: $validated['name'],
            itemNo: $validated['item_no'] ?? null,
            sortOrder: (int) $validated['sort_order'],
            controlAccountId: $controlAccount->id,
        );

        $action->execute($controlAccount, $data);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Cost package created.');
    }

    public function update(
        Request $request,
        Project $project,
        CostPackage $costPackage,
        UpdateCostPackage $action,
    ): RedirectResponse {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'item_no' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'sort_order' => 'required|integer|min:0',
        ]);

        $data = new CostPackageData(
            name: $validated['name'],
            itemNo: $validated['item_no'] ?? null,
            sortOrder: (int) $validated['sort_order'],
        );

        $action->execute($costPackage, $data);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Cost package updated.');
    }

    public function destroy(
        Project $project,
        CostPackage $costPackage,
        DeleteCostPackage $action,
    ): RedirectResponse {
        Gate::authorize('update', $project);

        $action->execute($costPackage);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Cost package deleted.');
    }
}
