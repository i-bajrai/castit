<?php

namespace App\Http\Controllers;

use App\Models\ControlAccount;
use App\Models\ForecastPeriod;
use App\Models\Project;
use Domain\Forecasting\Actions\CreateBudgetAdjustment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BudgetAdjustmentController extends Controller
{
    public function store(Request $request, Project $project, CreateBudgetAdjustment $action): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'control_account_id' => [
                'required',
                Rule::exists('control_accounts', 'id')->where('project_id', $project->id),
            ],
            'forecast_period_id' => [
                'required',
                Rule::exists('forecast_periods', 'id')->where('project_id', $project->id),
            ],
            'amount' => 'required|numeric|not_in:0',
            'reason' => 'required|string|max:1000',
        ]);

        $controlAccount = ControlAccount::findOrFail($validated['control_account_id']);
        $period = ForecastPeriod::findOrFail($validated['forecast_period_id']);

        $action->execute(
            $controlAccount,
            $period,
            $request->user(),
            (float) $validated['amount'],
            $validated['reason'],
        );

        return redirect()->route('projects.settings', $project)
            ->with('success', 'Budget adjustment recorded.');
    }
}
