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

        $lineItems = \App\Models\LineItem::whereHas('costPackage', fn ($q) => $q->where('project_id', $project->id))
            ->whereNotNull('item_no')
            ->orderBy('sort_order')
            ->get(['id', 'item_no', 'description']);

        $periods = $project->forecastPeriods()
            ->orderBy('period_date')
            ->pluck('period_date')
            ->map(fn ($d) => $d->format('Y-m'));

        return view('projects.settings.index', [
            'project' => $project,
            'controlAccounts' => $controlAccounts,
            'sampleLineItems' => $lineItems,
            'samplePeriods' => $periods,
        ]);
    }
}
