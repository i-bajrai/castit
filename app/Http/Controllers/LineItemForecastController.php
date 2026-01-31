<?php

namespace App\Http\Controllers;

use App\Models\LineItemForecast;
use App\Models\Project;
use Domain\Forecasting\Actions\SaveLineItemForecasts;
use Domain\Forecasting\DataTransferObjects\LineItemForecastData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LineItemForecastController extends Controller
{
    public function store(Request $request, Project $project, SaveLineItemForecasts $action): RedirectResponse
    {
        Gate::authorize('update', $project);

        $period = $project->forecastPeriods()
            ->where('period_date', now()->startOfMonth())
            ->firstOrFail();

        abort_if(! $period->isEditable(), 403, 'Period is not editable.');

        $validated = $request->validate([
            'forecasts' => 'required|array',
            'forecasts.*.line_item_id' => 'required|exists:line_items,id',
            'forecasts.*.ctd_qty' => 'required|numeric',
            'forecasts.*.comments' => 'nullable|string|max:2000',
        ]);

        $dtos = array_map(fn (array $f) => new LineItemForecastData(
            lineItemId: (int) $f['line_item_id'],
            ctdQty: (float) $f['ctd_qty'],
            comments: $f['comments'] ?? null,
        ), $validated['forecasts']);

        $action->execute($period, $dtos);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Line item forecasts saved.');
    }

    public function updateCtdQty(Request $request, Project $project, LineItemForecast $forecast): JsonResponse
    {
        Gate::authorize('update', $project);

        abort_if(! $forecast->forecastPeriod->isEditable(), 403, 'Period is not editable.');

        $validated = $request->validate([
            'ctd_qty' => 'required|numeric',
        ]);

        $lineItem = $forecast->lineItem;
        $ctdQty = (float) $validated['ctd_qty'];
        $ctdRate = (float) $lineItem->original_rate;
        $ctdAmount = $ctdQty * $ctdRate;

        $ctcQty = max(0, (float) $lineItem->original_qty - $ctdQty);
        $ctcAmount = $ctcQty * $ctdRate;

        $fcacAmount = $ctdAmount + $ctcAmount;
        $totalQty = $ctdQty + $ctcQty;
        $fcacRate = $totalQty > 0 ? $fcacAmount / $totalQty : 0;

        $variance = (float) ($forecast->previous_amount ?? 0) - $fcacAmount;

        $forecast->update([
            'ctd_qty' => $ctdQty,
            'ctd_rate' => $ctdRate,
            'ctd_amount' => $ctdAmount,
            'ctc_qty' => $ctcQty,
            'ctc_rate' => $ctdRate,
            'ctc_amount' => $ctcAmount,
            'fcac_rate' => $fcacRate,
            'fcac_amount' => $fcacAmount,
            'variance' => $variance,
        ]);

        return response()->json(['status' => 'ok']);
    }

    public function updateComment(Request $request, Project $project, LineItemForecast $forecast): JsonResponse
    {
        Gate::authorize('update', $project);

        abort_if(! $forecast->forecastPeriod->isEditable(), 403, 'Period is not editable.');

        $validated = $request->validate([
            'comments' => 'nullable|string|max:2000',
        ]);

        $forecast->update(['comments' => $validated['comments']]);

        return response()->json(['status' => 'ok']);
    }
}
