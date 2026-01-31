<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ForecastPeriod;
use App\Models\Project;
use Domain\Forecasting\Actions\CreateProject;
use Domain\Forecasting\Actions\GetProjectForecastSummary;
use Domain\Forecasting\Actions\SyncForecastPeriods;
use Domain\Forecasting\DataTransferObjects\ProjectData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $companies = $request->user()->companies()->get();
        $companyIds = $companies->pluck('id');

        $projects = Project::whereIn('company_id', $companyIds)
            ->withCount('controlAccounts')
            ->latest()
            ->get();

        return view('projects.index', compact('projects', 'companies'));
    }

    public function store(Request $request, CreateProject $action): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'project_number' => ['nullable', 'string', 'max:255'],
            'original_budget' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $company = Company::findOrFail($validated['company_id']);
        Gate::authorize('update', $company);

        $data = new ProjectData(
            name: $validated['name'],
            description: $validated['description'] ?? null,
            projectNumber: $validated['project_number'] ?? null,
            originalBudget: (float) $validated['original_budget'],
            startDate: $validated['start_date'] ?? null,
            endDate: $validated['end_date'] ?? null,
        );

        $project = $action->execute($company, $data);

        return redirect()->route('projects.setup', $project);
    }

    public function update(Request $request, Project $project, SyncForecastPeriods $syncPeriods): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'project_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'original_budget' => ['required', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $project->update($validated);
        $syncPeriods->execute($project);

        return redirect()->route('projects.settings', $project)
            ->with('success', 'Project details updated.');
    }

    public function show(
        Request $request,
        Project $project,
        GetProjectForecastSummary $forecastSummary,
    ): View {
        Gate::authorize('view', $project);

        $period = null;
        if ($request->has('period')) {
            $period = ForecastPeriod::where('id', $request->query('period'))
                ->where('project_id', $project->id)
                ->first();
        }

        $summary = $forecastSummary->execute($project, $period);

        $allPeriods = $project->forecastPeriods()
            ->orderByDesc('period_date')
            ->get();

        $isEditable = $summary['period'] ? $summary['period']->isEditable() : false;

        return view('projects.show', [
            'project' => $summary['project'],
            'period' => $summary['period'],
            'accounts' => $summary['accounts'],
            'totals' => $summary['totals'],
            'allPeriods' => $allPeriods,
            'isEditable' => $isEditable,
        ]);
    }

    public function setup(Project $project): View
    {
        Gate::authorize('view', $project);

        $controlAccounts = $project->controlAccounts()->orderBy('sort_order')->get();

        return view('projects.setup', [
            'project' => $project,
            'controlAccounts' => $controlAccounts,
        ]);
    }

    public function reports(Project $project): View
    {
        Gate::authorize('view', $project);

        return view('projects.reports', [
            'project' => $project,
        ]);
    }

    public function executiveSummary(
        Project $project,
        GetProjectForecastSummary $forecastSummary,
    ): View {
        Gate::authorize('view', $project);

        $summary = $forecastSummary->execute($project);

        return view('projects.executive-summary', [
            'project' => $project,
            'accounts' => $summary['accounts'],
            'totals' => $summary['totals'],
            'period' => $summary['period'],
        ]);
    }
}
