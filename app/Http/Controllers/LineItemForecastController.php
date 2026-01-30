<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Domain\Forecasting\Actions\SaveLineItemForecasts;
use Domain\Forecasting\DataTransferObjects\LineItemForecastData;
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
}
