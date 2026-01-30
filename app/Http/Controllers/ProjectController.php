<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Project;
use Domain\Forecasting\Actions\CreateProject;
use Domain\Forecasting\Actions\GetControlAccountSummary;
use Domain\Forecasting\Actions\GetProjectForecastSummary;
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
            ->withCount('costPackages')
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
        ]);

        $company = Company::findOrFail($validated['company_id']);
        Gate::authorize('update', $company);

        $data = new ProjectData(
            name: $validated['name'],
            description: $validated['description'] ?? null,
            projectNumber: $validated['project_number'] ?? null,
            originalBudget: (float) $validated['original_budget'],
        );

        $project = $action->execute($company, $data);

        return redirect()->route('projects.show', $project);
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
