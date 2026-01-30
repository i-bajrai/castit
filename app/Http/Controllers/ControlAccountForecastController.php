<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Domain\Forecasting\Actions\SaveControlAccountForecasts;
use Domain\Forecasting\DataTransferObjects\ControlAccountForecastData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ControlAccountForecastController extends Controller
{
    public function store(Request $request, Project $project, SaveControlAccountForecasts $action): RedirectResponse
    {
        Gate::authorize('update', $project);

        $period = $project->forecastPeriods()
            ->where('period_date', now()->startOfMonth())
            ->firstOrFail();

        abort_if(! $period->isEditable(), 403, 'Period is not editable.');

        $validated = $request->validate([
            'forecasts' => 'required|array',
            'forecasts.*.control_account_id' => 'required|exists:control_accounts,id',
            'forecasts.*.monthly_cost' => 'required|numeric',
            'forecasts.*.cost_to_date' => 'required|numeric',
            'forecasts.*.estimate_to_complete' => 'required|numeric',
            'forecasts.*.monthly_comments' => 'nullable|string|max:5000',
        ]);

        $dtos = array_map(fn (array $f) => new ControlAccountForecastData(
            controlAccountId: (int) $f['control_account_id'],
            monthlyCost: (float) $f['monthly_cost'],
            costToDate: (float) $f['cost_to_date'],
            estimateToComplete: (float) $f['estimate_to_complete'],
            monthlyComments: $f['monthly_comments'] ?? null,
        ), $validated['forecasts']);

        $action->execute($period, $dtos);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Control account forecasts saved.');
    }
}
