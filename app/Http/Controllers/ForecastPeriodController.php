<?php

namespace App\Http\Controllers;

use App\Models\ForecastPeriod;
use App\Models\Project;
use Domain\Forecasting\Actions\LockForecastPeriod;
use Domain\Forecasting\Actions\OpenNewForecastPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class ForecastPeriodController extends Controller
{
    public function store(Request $request, Project $project, OpenNewForecastPeriod $action): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'period_date' => ['required', 'date'],
        ]);

        $periodDate = Carbon::parse($validated['period_date'])->startOfMonth();

        $exists = $project->forecastPeriods()
            ->whereDate('period_date', $periodDate)
            ->exists();

        if ($exists) {
            return back()->withErrors(['period_date' => 'A forecast period already exists for this date.']);
        }

        $action->execute($project, $periodDate);

        return redirect()->route('projects.settings', $project)
            ->with('success', 'New forecast period opened.');
    }

    public function lock(Project $project, ForecastPeriod $period, LockForecastPeriod $action): RedirectResponse
    {
        Gate::authorize('update', $project);

        $action->execute($period);

        return redirect()->route('projects.settings', $project)
            ->with('success', 'Period locked.');
    }
}
