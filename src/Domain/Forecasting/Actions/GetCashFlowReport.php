<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\LineItem;
use App\Models\Project;

class GetCashFlowReport
{
    public function execute(Project $project): array
    {
        $allPeriods = $project->forecastPeriods()
            ->orderBy('period_date')
            ->get();

        $lineItems = LineItem::whereHas('costPackage', function ($q) use ($project) {
            $q->where('project_id', $project->id);
        })->get();

        $totalBudget = $lineItems->sum('original_amount');

        // ctd_amount is already cumulative (cost-to-date), so summing it per period
        // gives the total project CTD at that point. Period spend = current CTD - previous CTD.
        $periods = [];
        $previousCtd = 0.0;
        $periodCount = $allPeriods->count();

        foreach ($allPeriods as $index => $period) {
            $cumulativeCtd = (float) $period->lineItemForecasts()->sum('ctd_amount');
            $periodSpend = $cumulativeCtd - $previousCtd;

            $periodCtc = (float) $period->lineItemForecasts()->sum('ctc_amount');
            $periodFcac = (float) $period->lineItemForecasts()->sum('fcac_amount');

            // Planned spend: budget distributed evenly across periods (for S-curve baseline)
            $plannedCumulative = $periodCount > 0
                ? $totalBudget * (($index + 1) / $periodCount)
                : 0.0;

            $periods[] = [
                'period_date' => $period->period_date,
                'label' => $period->period_date->format('M Y'),
                'period_ctd' => $periodSpend,
                'cumulative_ctd' => $cumulativeCtd,
                'period_ctc' => $periodCtc,
                'period_fcac' => $periodFcac,
                'planned_cumulative' => $plannedCumulative,
            ];

            $previousCtd = $cumulativeCtd;
        }

        return [
            'project' => $project,
            'periods' => $periods,
            'totalBudget' => (float) $totalBudget,
            'totalFcac' => $periods ? end($periods)['period_fcac'] : 0.0,
        ];
    }
}
