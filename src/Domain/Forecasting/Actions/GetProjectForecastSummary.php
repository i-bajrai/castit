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

        $accounts = $project->controlAccounts()
            ->with(['costPackages' => function ($query) use ($period): void {
                $query->orderBy('sort_order');
                $query->with(['lineItems' => function ($q) use ($period): void {
                    $q->orderBy('sort_order');
                    $q->with('createdInPeriod');
                    if ($period) {
                        $q->with(['forecasts' => function ($fq) use ($period): void {
                            $fq->where('forecast_period_id', $period->id);
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
                    $forecast = $item->forecasts->first();
                    if ($forecast) {
                        $totals['previous_fcac'] += (float) $forecast->previous_amount;
                        $totals['ctd'] += (float) $forecast->ctd_amount;
                        $totals['ctc'] += (float) $forecast->ctc_amount;
                        $totals['fcac'] += (float) $forecast->fcac_amount;
                        $totals['variance'] += (float) $forecast->variance;
                    }
                }
            }
        }

        return [
            'project' => $project,
            'period' => $period,
            'accounts' => $accounts,
            'totals' => $totals,
        ];
    }
}
