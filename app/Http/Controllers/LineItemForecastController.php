<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Domain\Forecasting\Actions\GetProjectForecastSummary;
use Domain\Forecasting\Actions\SaveLineItemForecasts;
use Domain\Forecasting\DataTransferObjects\LineItemForecastData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LineItemForecastController extends Controller
{
    public function index(Project $project, GetProjectForecastSummary $summary): View|RedirectResponse
    {
        Gate::authorize('update', $project);

        $period = $project->forecastPeriods()->where('is_current', true)->first();

        if (! $period) {
            return redirect()->route('projects.settings', $project)
                ->with('error', 'No current forecast period. Create one in Settings first.');
        }

        $data = $summary->execute($project, $period);

        return view('projects.data-entry.line-items', [
            'project' => $project,
            'period' => $period,
            'packages' => $data['packages'],
        ]);
    }

    public function store(Request $request, Project $project, SaveLineItemForecasts $action): RedirectResponse
    {
        Gate::authorize('update', $project);

        $period = $project->forecastPeriods()->where('is_current', true)->firstOrFail();
        abort_if($period->isLocked(), 403, 'Period is locked.');

        $validated = $request->validate([
            'forecasts' => 'required|array',
            'forecasts.*.line_item_id' => 'required|exists:line_items,id',
            'forecasts.*.ctd_qty' => 'required|numeric',
            'forecasts.*.ctd_rate' => 'required|numeric',
            'forecasts.*.ctd_amount' => 'required|numeric',
            'forecasts.*.ctc_qty' => 'required|numeric',
            'forecasts.*.ctc_rate' => 'required|numeric',
            'forecasts.*.ctc_amount' => 'required|numeric',
            'forecasts.*.comments' => 'nullable|string|max:2000',
        ]);

        $dtos = array_map(fn (array $f) => new LineItemForecastData(
            lineItemId: (int) $f['line_item_id'],
            ctdQty: (float) $f['ctd_qty'],
            ctdRate: (float) $f['ctd_rate'],
            ctdAmount: (float) $f['ctd_amount'],
            ctcQty: (float) $f['ctc_qty'],
            ctcRate: (float) $f['ctc_rate'],
            ctcAmount: (float) $f['ctc_amount'],
            comments: $f['comments'] ?? null,
        ), $validated['forecasts']);

        $action->execute($period, $dtos);

        return redirect()->route('projects.data-entry.line-items', $project)
            ->with('success', 'Line item forecasts saved.');
    }
}
