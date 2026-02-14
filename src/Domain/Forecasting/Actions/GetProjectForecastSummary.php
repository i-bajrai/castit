<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class GetProjectForecastSummary
{
    /**
     * @return array{
     *   project: Project,
     *   period: ForecastPeriod|null,
     *   accounts: Collection<int, \App\Models\ControlAccount>,
     *   totals: array{original_budget: float, previous_fcac: float, ctd: float, ctc: float, fcac: float, variance: float}
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

        // Get all period IDs up to the selected period (for summing CTD)
        $periodIdsUpTo = collect();
        $previousPeriod = null;
        if ($period) {
            $periodsUpTo = $project->forecastPeriods()
                ->where('period_date', '<=', $period->period_date)
                ->orderBy('period_date')
                ->get();
            $periodIdsUpTo = $periodsUpTo->pluck('id');

            $previousPeriod = $project->forecastPeriods()
                ->where('period_date', '<', $period->period_date)
                ->orderByDesc('period_date')
                ->first();
        }

        $accounts = $project->controlAccounts()
            ->with(['costPackages' => function ($query) use ($periodIdsUpTo): void {
                $query->orderBy('sort_order');
                $query->with(['lineItems' => function ($q) use ($periodIdsUpTo): void {
                    $q->orderBy('sort_order');
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

        $totals = [
            'original_budget' => 0.0,
            'previous_fcac' => 0.0,
            'ctd' => 0.0,
            'ctc' => 0.0,
            'fcac' => 0.0,
            'variance' => 0.0,
        ];

        foreach ($accounts as $account) {
            foreach ($account->costPackages as $package) {
                foreach ($package->lineItems as $item) {
                    if ($period && ! $item->existedInPeriod($period)) {
                        continue;
                    }
                    $totals['original_budget'] += (float) $item->original_amount;

                    // CTD = sum of all period amounts up to selected period
                    $ctdAmount = $item->forecasts->sum('period_amount');

                    // Current period forecast (for FCAC)
                    $currentForecast = $period
                        ? $item->forecasts->firstWhere('forecast_period_id', $period->id)
                        : null;

                    $fcacAmount = $currentForecast ? (float) $currentForecast->fcac_amount : 0.0;
                    $ctcAmount = $fcacAmount - $ctdAmount;

                    // Previous FCAC from the prior period's forecast
                    $previousFcac = 0.0;
                    if ($previousPeriod) {
                        $prevForecast = $item->forecasts->firstWhere('forecast_period_id', $previousPeriod->id);
                        $previousFcac = $prevForecast ? (float) $prevForecast->fcac_amount : 0.0;
                    }

                    $variance = $fcacAmount - $previousFcac;

                    $totals['previous_fcac'] += $previousFcac;
                    $totals['ctd'] += $ctdAmount;
                    $totals['ctc'] += $ctcAmount;
                    $totals['fcac'] += $fcacAmount;
                    $totals['variance'] += $variance;
                }
            }
        }

        return [
            'project' => $project,
            'period' => $period,
            'previousPeriod' => $previousPeriod,
            'accounts' => $accounts,
            'totals' => $totals,
        ];
    }
}
