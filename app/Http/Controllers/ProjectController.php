<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Domain\Forecasting\Actions\GetControlAccountSummary;
use Domain\Forecasting\Actions\GetProjectForecastSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $projects = $request->user()->projects()
            ->withCount('costPackages')
            ->latest()
            ->get();

        return view('projects.index', compact('projects'));
    }

    public function show(Project $project, GetProjectForecastSummary $forecastSummary): View
    {
        Gate::authorize('view', $project);

        $summary = $forecastSummary->execute($project);

        return view('projects.show', [
            'project' => $summary['project'],
            'period' => $summary['period'],
            'packages' => $summary['packages'],
            'totals' => $summary['totals'],
        ]);
    }

    public function executiveSummary(
        Project $project,
        GetControlAccountSummary $controlAccountSummary,
    ): View {
        Gate::authorize('view', $project);

        $summary = $controlAccountSummary->execute($project);

        return view('projects.executive-summary', [
            'project' => $project,
            'accounts' => $summary['accounts'],
            'period' => $summary['period'],
        ]);
    }
}
