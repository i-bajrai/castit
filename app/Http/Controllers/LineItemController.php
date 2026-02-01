<?php

namespace App\Http\Controllers;

use App\Models\ControlAccount;
use App\Models\CostPackage;
use App\Models\ForecastPeriod;
use App\Models\LineItem;
use App\Models\Project;
use Domain\Forecasting\Actions\CreateLineItem;
use Domain\Forecasting\Actions\DeleteLineItem;
use Domain\Forecasting\Actions\ReassignLineItems;
use Domain\Forecasting\Actions\UpdateLineItem;
use Domain\Forecasting\DataTransferObjects\LineItemData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LineItemController extends Controller
{
    public function unassigned(Project $project): View|RedirectResponse
    {
        Gate::authorize('update', $project);

        $unassignedCa = $project->controlAccounts()
            ->where('code', 'UNASSIGNED')
            ->first();

        $unassignedItems = collect();

        if ($unassignedCa) {
            $unassignedItems = LineItem::whereHas('costPackage', function ($q) use ($unassignedCa) {
                $q->where('control_account_id', $unassignedCa->id);
            })->with('forecasts')->get();
        }

        if ($unassignedItems->isEmpty()) {
            return redirect()->route('projects.settings', $project)
                ->with('success', 'All items are assigned.');
        }

        // All cost packages grouped by control account (excluding unassigned)
        $controlAccounts = $project->controlAccounts()
            ->where('code', '!=', 'UNASSIGNED')
            ->with(['costPackages.lineItems'])
            ->orderBy('sort_order')
            ->get();

        return view('projects.unassigned', [
            'project' => $project,
            'unassignedItems' => $unassignedItems,
            'controlAccounts' => $controlAccounts,
        ]);
    }

    public function bulkReassign(
        Request $request,
        Project $project,
        ReassignLineItems $action,
    ): RedirectResponse {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'operations' => 'required|array|min:1',
            'operations.*.line_item_id' => 'required|integer|exists:line_items,id',
            'operations.*.action' => 'required|in:move,merge',
            'operations.*.target_package_id' => 'required_if:operations.*.action,move|nullable|integer|exists:cost_packages,id',
            'operations.*.merge_into_id' => 'required_if:operations.*.action,merge|nullable|integer|exists:line_items,id',
        ]);

        $operations = collect($validated['operations'])->map(function ($op) {
            return [
                'line_item_id' => $op['line_item_id'],
                'target_package_id' => $op['action'] === 'move' ? $op['target_package_id'] : null,
                'merge_into_id' => $op['action'] === 'merge' ? $op['merge_into_id'] : null,
            ];
        })->all();

        $result = $action->execute($operations);

        // Clean up empty unassigned CA/package
        $unassignedCa = $project->controlAccounts()->where('code', 'UNASSIGNED')->first();
        if ($unassignedCa) {
            $remainingItems = LineItem::whereHas('costPackage', function ($q) use ($unassignedCa) {
                $q->where('control_account_id', $unassignedCa->id);
            })->count();

            if ($remainingItems === 0) {
                $unassignedCa->costPackages()->delete();
                $unassignedCa->delete();

                return redirect()->route('projects.settings', $project)
                    ->with('success', $result->summary().' All items assigned.');
            }
        }

        return redirect()->route('projects.unassigned', $project)
            ->with('success', $result->summary());
    }

    public function store(
        Request $request,
        Project $project,
        CostPackage $costPackage,
        CreateLineItem $action,
    ): RedirectResponse {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'item_no' => 'nullable|string|max:255',
            'description' => 'required|string|max:255',
            'unit_of_measure' => 'nullable|string|max:255',
            'original_qty' => 'required|numeric|min:0',
            'original_rate' => 'required|numeric|min:0',
            'original_amount' => 'required|numeric|min:0',
            'sort_order' => 'required|integer|min:0',
        ]);

        $data = new LineItemData(
            description: $validated['description'],
            itemNo: $validated['item_no'] ?? null,
            unitOfMeasure: $validated['unit_of_measure'] ?? null,
            originalQty: (float) $validated['original_qty'],
            originalRate: (float) $validated['original_rate'],
            originalAmount: (float) $validated['original_amount'],
            sortOrder: (int) $validated['sort_order'],
        );

        $currentPeriod = ForecastPeriod::where('project_id', $project->id)
            ->where('period_date', now()->startOfMonth())
            ->first();

        $action->execute($costPackage, $data, $currentPeriod);

        return redirect()->back()
            ->with('success', 'Line item created.');
    }

    public function update(
        Request $request,
        Project $project,
        CostPackage $costPackage,
        LineItem $lineItem,
        UpdateLineItem $action,
    ): RedirectResponse {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'item_no' => 'nullable|string|max:255',
            'description' => 'required|string|max:255',
            'unit_of_measure' => 'nullable|string|max:255',
            'original_qty' => 'required|numeric|min:0',
            'original_rate' => 'required|numeric|min:0',
            'original_amount' => 'required|numeric|min:0',
            'sort_order' => 'required|integer|min:0',
        ]);

        $data = new LineItemData(
            description: $validated['description'],
            itemNo: $validated['item_no'] ?? null,
            unitOfMeasure: $validated['unit_of_measure'] ?? null,
            originalQty: (float) $validated['original_qty'],
            originalRate: (float) $validated['original_rate'],
            originalAmount: (float) $validated['original_amount'],
            sortOrder: (int) $validated['sort_order'],
        );

        $action->execute($lineItem, $data);

        return redirect()->back()
            ->with('success', 'Line item updated.');
    }

    public function destroy(
        Project $project,
        CostPackage $costPackage,
        LineItem $lineItem,
        DeleteLineItem $action,
    ): RedirectResponse {
        Gate::authorize('update', $project);

        $action->execute($lineItem);

        return redirect()->back()
            ->with('success', 'Line item deleted.');
    }
}
