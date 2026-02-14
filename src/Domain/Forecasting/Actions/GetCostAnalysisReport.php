<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\Project;

class GetCostAnalysisReport
{
    /**
     * @return array{
     *   project: Project,
     *   period: ForecastPeriod|null,
     *   previousPeriod: ForecastPeriod|null,
     *   rows: array<int, array<string, mixed>>,
     *   totals: array<string, float>
     * }
     */
    public function execute(Project $project, ?ForecastPeriod $period = null): array
    {
        if ($period === null) {
            $period = $project->forecastPeriods()
                ->where('period_date', now()->startOfMonth())
                ->first()
                ?? $project->forecastPeriods()->orderByDesc('period_date')->first();
        }

        $previousPeriod = null;
        if ($period) {
            $previousPeriod = $project->forecastPeriods()
                ->where('period_date', '<', $period->period_date)
                ->orderByDesc('period_date')
                ->first();
        }

        // Load all forecasts up to the current period for CTD summation
        $periodIdsUpTo = collect();
        if ($period) {
            $periodIdsUpTo = $project->forecastPeriods()
                ->where('period_date', '<=', $period->period_date)
                ->pluck('id');
        }

        $accounts = $project->controlAccounts()
            ->with(['costPackages' => function ($query) use ($periodIdsUpTo): void {
                $query->with(['lineItems' => function ($q) use ($periodIdsUpTo): void {
                    $q->with('createdInPeriod');
                    if ($periodIdsUpTo->isNotEmpty()) {
                        $q->with(['forecasts' => function ($fq) use ($periodIdsUpTo): void {
                            $fq->whereIn('forecast_period_id', $periodIdsUpTo);
                        }]);
                    }
                }]);
            }])
            ->orderBy('sort_order')
            ->get();

        $rows = [];
        $totals = [
            'baseline_budget' => 0.0,
            'approved_budget' => 0.0,
            'last_month_approved_budget' => 0.0,
            'month_budget_movement' => 0.0,
            'monthly_cost' => 0.0,
            'cost_to_date' => 0.0,
            'estimate_to_complete' => 0.0,
            'estimated_final_cost' => 0.0,
            'last_month_efc' => 0.0,
            'monthly_efc_movement' => 0.0,
        ];

        foreach ($accounts as $account) {
            $ctd = 0.0;
            $ctc = 0.0;
            $fcac = 0.0;
            $prevFcac = 0.0;
            $monthlyCostTotal = 0.0;
            $comments = [];

            foreach ($account->costPackages as $pkg) {
                foreach ($pkg->lineItems as $item) {
                    if ($period && ! $item->existedInPeriod($period)) {
                        continue;
                    }

                    // CTD = sum of all period amounts up to selected
                    $itemCtd = (float) $item->forecasts->sum('period_amount');
                    $ctd += $itemCtd;

                    $currentForecast = $period
                        ? $item->forecasts->firstWhere('forecast_period_id', $period->id)
                        : null;

                    if ($currentForecast) {
                        $itemFcac = (float) $currentForecast->fcac_amount;
                        $fcac += $itemFcac;
                        $ctc += $itemFcac - $itemCtd;
                        // Monthly cost = this period's amount
                        $monthlyCostTotal += (float) $currentForecast->period_amount;

                        if ($currentForecast->comments) {
                            $comments[] = $currentForecast->comments;
                        }
                    }

                    if ($previousPeriod) {
                        $previousForecast = $item->forecasts->firstWhere('forecast_period_id', $previousPeriod->id);
                        if ($previousForecast) {
                            $prevFcac += (float) $previousForecast->fcac_amount;
                        }
                    }
                }
            }

            $baselineBudget = (float) $account->baseline_budget;
            $approvedBudget = (float) $account->approved_budget;
            // Last month approved budget: for now use same approved_budget
            // since BudgetAdjustments track changes but current approved_budget is the latest
            $lastMonthApprovedBudget = $approvedBudget;
            if ($previousPeriod) {
                $lastAdjustment = $account->budgetAdjustments()
                    ->where('forecast_period_id', $period?->id)
                    ->orderByDesc('id')
                    ->first();

                if ($lastAdjustment) {
                    $lastMonthApprovedBudget = (float) $lastAdjustment->previous_approved_budget;
                }
            }

            $monthBudgetMovement = $approvedBudget - $lastMonthApprovedBudget;
            $monthlyCost = $monthlyCostTotal;
            $lastMonthEfc = $prevFcac;
            $monthlyEfcMovement = $fcac - $prevFcac;

            $row = [
                'phase' => $account->phase,
                'code' => $account->code,
                'description' => $account->description,
                'category' => $account->category,
                'baseline_budget' => $baselineBudget,
                'approved_budget' => $approvedBudget,
                'last_month_approved_budget' => $lastMonthApprovedBudget,
                'month_budget_movement' => $monthBudgetMovement,
                'monthly_cost' => $monthlyCost,
                'cost_to_date' => $ctd,
                'estimate_to_complete' => $ctc,
                'estimated_final_cost' => $fcac,
                'last_month_efc' => $lastMonthEfc,
                'monthly_efc_movement' => $monthlyEfcMovement,
                'monthly_comments' => implode("\n", $comments),
            ];

            $rows[] = $row;

            foreach (['baseline_budget', 'approved_budget', 'last_month_approved_budget', 'month_budget_movement', 'monthly_cost', 'cost_to_date', 'estimate_to_complete', 'estimated_final_cost', 'last_month_efc', 'monthly_efc_movement'] as $key) {
                $totals[$key] += $row[$key];
            }
        }

        return [
            'project' => $project,
            'period' => $period,
            'previousPeriod' => $previousPeriod,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
}
