<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\Project;

class GetProjectForecastSummary
{
    /**
     * @return array{
     *   project: Project,
     *   period: ForecastPeriod|null,
     *   packages: \Illuminate\Database\Eloquent\Collection<int, \App\Models\CostPackage>,
     *   totals: array{original_budget: float, previous_fcac: float, ctd: float, ctc: float, fcac: float, variance: float}
     * }
     */
    public function execute(Project $project, ?ForecastPeriod $period = null): array
    {
        if ($period === null) {
            $period = $project->forecastPeriods()->where('is_current', true)->first();
        }

        $packages = $project->costPackages()
            ->with(['lineItems' => function ($query) use ($period): void {
                $query->orderBy('sort_order');
                if ($period) {
                    $query->with(['forecasts' => function ($q) use ($period): void {
                        $q->where('forecast_period_id', $period->id);
                    }]);
                }
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

        foreach ($packages as $package) {
            foreach ($package->lineItems as $item) {
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

        return [
            'project' => $project,
            'period' => $period,
            'packages' => $packages,
            'totals' => $totals,
        ];
    }
}
