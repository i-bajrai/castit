<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ForecastPeriod;
use App\Models\Project;
use Domain\Forecasting\Actions\CreateProject;
use Domain\Forecasting\Actions\ForceDeleteProject;
use Domain\Forecasting\Actions\GetCashFlowReport;
use Domain\Forecasting\Actions\GetCostAnalysisReport;
use Domain\Forecasting\Actions\GetEarnedValueReport;
use Domain\Forecasting\Actions\GetLineItemProgressReport;
use Domain\Forecasting\Actions\GetPeriodMovementReport;
use Domain\Forecasting\Actions\GetProjectForecastSummary;
use Domain\Forecasting\Actions\GetVarianceDrillDownReport;
use Domain\Forecasting\Actions\RestoreProject;
use Domain\Forecasting\Actions\StoreBudgetSetup;
use Domain\Forecasting\Actions\SyncForecastPeriods;
use Domain\Forecasting\Actions\TrashProject;
use Domain\Forecasting\DataTransferObjects\ProjectData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $projects = Project::withCount('controlAccounts')->latest()->paginate(24);
            $companies = Company::all();
        } else {
            $projects = Project::where('company_id', $user->company_id)
                ->withCount('controlAccounts')
                ->latest()
                ->paginate(24);
            $companies = collect([$user->company]);
        }

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

    public function budget(Project $project): View
    {
        Gate::authorize('view', $project);

        $controlAccounts = $project->controlAccounts()->orderBy('sort_order')->get();

        return view('projects.budget', [
            'project' => $project,
            'controlAccounts' => $controlAccounts,
        ]);
    }

    public function storeBudget(Request $request, Project $project, StoreBudgetSetup $action): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'accounts' => 'required|array|min:1',
            'accounts.*.control_account_id' => 'required|integer|exists:control_accounts,id',
            'accounts.*.baseline_budget' => 'required|numeric|min:0',
            'accounts.*.approved_budget' => 'required|numeric|min:0',
            'accounts.*.packages_json' => 'nullable|string',
        ]);

        // Decode packages from JSON to avoid PHP max_input_vars limit
        foreach ($validated['accounts'] as &$account) {
            $account['packages'] = ! empty($account['packages_json'])
                ? json_decode($account['packages_json'], true)
                : [];
            unset($account['packages_json']);
        }
        unset($account);

        $action->execute($project, $validated['accounts']);

        return redirect()->route('projects.show', $project);
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

    public function costAnalysis(
        Request $request,
        Project $project,
        GetCostAnalysisReport $report,
    ): View {
        Gate::authorize('view', $project);

        $period = null;
        if ($request->has('period')) {
            $period = ForecastPeriod::where('id', $request->query('period'))
                ->where('project_id', $project->id)
                ->first();
        }

        $data = $report->execute($project, $period);

        $allPeriods = $project->forecastPeriods()
            ->orderByDesc('period_date')
            ->get();

        return view('projects.cost-analysis', [
            'project' => $data['project'],
            'period' => $data['period'],
            'previousPeriod' => $data['previousPeriod'],
            'rows' => $data['rows'],
            'totals' => $data['totals'],
            'allPeriods' => $allPeriods,
        ]);
    }

    public function lineItemProgress(
        Request $request,
        Project $project,
        GetLineItemProgressReport $report,
    ): View {
        Gate::authorize('view', $project);

        $period = null;
        if ($request->has('period')) {
            $period = ForecastPeriod::where('id', $request->query('period'))
                ->where('project_id', $project->id)
                ->first();
        }

        $data = $report->execute($project, $period);

        $allPeriods = $project->forecastPeriods()
            ->orderByDesc('period_date')
            ->get();

        return view('projects.reports.line-item-progress', [
            'project' => $data['project'],
            'period' => $data['period'],
            'groups' => $data['groups'],
            'totals' => $data['totals'],
            'allPeriods' => $allPeriods,
        ]);
    }

    public function varianceDrillDown(
        Request $request,
        Project $project,
        GetVarianceDrillDownReport $report,
    ): View {
        Gate::authorize('view', $project);

        $period = null;
        if ($request->has('period')) {
            $period = ForecastPeriod::where('id', $request->query('period'))
                ->where('project_id', $project->id)
                ->first();
        }

        $data = $report->execute($project, $period);

        $allPeriods = $project->forecastPeriods()
            ->orderByDesc('period_date')
            ->get();

        return view('projects.reports.variance-drill-down', [
            'project' => $data['project'],
            'period' => $data['period'],
            'items' => $data['items'],
            'totals' => $data['totals'],
            'allPeriods' => $allPeriods,
        ]);
    }

    public function periodMovement(
        Request $request,
        Project $project,
        GetPeriodMovementReport $report,
    ): View {
        Gate::authorize('view', $project);

        $period = null;
        if ($request->has('period')) {
            $period = ForecastPeriod::where('id', $request->query('period'))
                ->where('project_id', $project->id)
                ->first();
        }

        $data = $report->execute($project, $period);

        $allPeriods = $project->forecastPeriods()
            ->orderByDesc('period_date')
            ->get();

        return view('projects.reports.period-movement', [
            'project' => $data['project'],
            'period' => $data['period'],
            'previousPeriod' => $data['previousPeriod'],
            'rows' => $data['rows'],
            'totals' => $data['totals'],
            'allPeriods' => $allPeriods,
        ]);
    }

    public function earnedValue(
        Request $request,
        Project $project,
        GetEarnedValueReport $report,
    ): View {
        Gate::authorize('view', $project);

        $period = null;
        if ($request->has('period')) {
            $period = ForecastPeriod::where('id', $request->query('period'))
                ->where('project_id', $project->id)
                ->first();
        }

        $data = $report->execute($project, $period);

        $allPeriods = $project->forecastPeriods()
            ->orderByDesc('period_date')
            ->get();

        return view('projects.reports.earned-value', [
            'project' => $data['project'],
            'period' => $data['period'],
            'rows' => $data['rows'],
            'totals' => $data['totals'],
            'allPeriods' => $allPeriods,
        ]);
    }

    public function cashFlow(
        Project $project,
        GetCashFlowReport $report,
    ): View {
        Gate::authorize('view', $project);

        $data = $report->execute($project);

        return view('projects.reports.cash-flow', [
            'project' => $data['project'],
            'periods' => $data['periods'],
            'totalBudget' => $data['totalBudget'],
            'totalFcac' => $data['totalFcac'],
        ]);
    }

    public function destroy(Project $project, TrashProject $action): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $action->execute($project);

        return redirect()->route('dashboard')
            ->with('success', 'Project moved to trash.');
    }

    public function trash(Request $request): View
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $trashedProjects = Project::onlyTrashed()->latest('deleted_at')->get();
        } else {
            $trashedProjects = Project::onlyTrashed()
                ->where('company_id', $user->company_id)
                ->latest('deleted_at')
                ->get();
        }

        return view('projects.trash', compact('trashedProjects'));
    }

    public function restore(Project $project, RestoreProject $action): RedirectResponse
    {
        Gate::authorize('restore', $project);

        $action->execute($project);

        return redirect()->route('projects.trash')
            ->with('success', 'Project restored successfully.');
    }

    public function forceDelete(Project $project, ForceDeleteProject $action): RedirectResponse
    {
        Gate::authorize('forceDelete', $project);

        $action->execute($project);

        return redirect()->route('projects.trash')
            ->with('success', 'Project permanently deleted.');
    }
}
