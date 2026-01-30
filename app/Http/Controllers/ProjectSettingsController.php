<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProjectSettingsController extends Controller
{
    public function index(Project $project): View
    {
        Gate::authorize('update', $project);

        $controlAccounts = $project->controlAccounts()->orderBy('sort_order')->get();
        $periods = $project->forecastPeriods()
            ->withCount('budgetAdjustments')
            ->orderByDesc('period_date')
            ->get();

        $lockedPeriods = $periods->filter(fn ($p) => $p->isLocked());

        $costPackages = $project->costPackages()
            ->withCount('lineItems')
            ->with(['lineItems' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('projects.settings.index', [
            'project' => $project,
            'controlAccounts' => $controlAccounts,
            'periods' => $periods,
            'lockedPeriods' => $lockedPeriods,
            'costPackages' => $costPackages,
        ]);
    }
}
