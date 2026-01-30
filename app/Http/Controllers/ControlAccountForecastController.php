<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Domain\Forecasting\Actions\GetControlAccountSummary;
use Domain\Forecasting\Actions\SaveControlAccountForecasts;
use Domain\Forecasting\DataTransferObjects\ControlAccountForecastData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ControlAccountForecastController extends Controller
{
    public function index(Project $project, GetControlAccountSummary $summary): View|RedirectResponse
    {
        Gate::authorize('update', $project);

        $period = $project->forecastPeriods()->where('is_current', true)->first();

        if (! $period) {
            return redirect()->route('projects.settings', $project)
                ->with('error', 'No current forecast period. Create one in Settings first.');
        }

        $data = $summary->execute($project, $period);

        return view('projects.data-entry.control-accounts', [
            'project' => $project,
            'period' => $period,
            'accounts' => $data['accounts'],
        ]);
    }

    public function store(Request $request, Project $project, SaveControlAccountForecasts $action): RedirectResponse
    {
        Gate::authorize('update', $project);

        $period = $project->forecastPeriods()->where('is_current', true)->firstOrFail();
        abort_if($period->isLocked(), 403, 'Period is locked.');

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

        return redirect()->route('projects.data-entry.control-accounts', $project)
            ->with('success', 'Control account forecasts saved.');
    }
}
