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

        $controlAccounts = $project->controlAccounts()->where('code', '!=', 'UNASSIGNED')->orderBy('sort_order')->get();

        $unassignedCount = 0;
        $unassignedCa = $project->controlAccounts()->where('code', 'UNASSIGNED')->first();
        if ($unassignedCa) {
            $unassignedCount = \App\Models\LineItem::whereHas('costPackage', fn ($q) => $q->where('control_account_id', $unassignedCa->id))->count();
        }

        $lineItems = \App\Models\LineItem::whereHas('costPackage', fn ($q) => $q->where('project_id', $project->id))
            ->whereNotNull('item_no')
            ->orderBy('sort_order')
            ->get(['id', 'item_no', 'description']);

        $periods = $project->forecastPeriods()
            ->where('period_date', '<', now()->startOfMonth())
            ->orderBy('period_date')
            ->pluck('period_date')
            ->map(fn ($d) => $d->format('Y-m'));

        return view('projects.settings.index', [
            'project' => $project,
            'controlAccounts' => $controlAccounts,
            'sampleLineItems' => $lineItems,
            'samplePeriods' => $periods,
            'unassignedCount' => $unassignedCount,
        ]);
    }
}
