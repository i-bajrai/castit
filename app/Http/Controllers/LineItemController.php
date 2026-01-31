<?php

namespace App\Http\Controllers;

use App\Models\CostPackage;
use App\Models\ForecastPeriod;
use App\Models\LineItem;
use App\Models\Project;
use Domain\Forecasting\Actions\CreateLineItem;
use Domain\Forecasting\Actions\DeleteLineItem;
use Domain\Forecasting\Actions\UpdateLineItem;
use Domain\Forecasting\DataTransferObjects\LineItemData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LineItemController extends Controller
{
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
