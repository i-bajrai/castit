<?php

namespace Domain\Forecasting\Actions;

use App\Models\LineItem;
use App\Models\Project;

class GetCashFlowReport
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Project $project): array
    {
        $allPeriods = $project->forecastPeriods()
            ->orderBy('period_date')
            ->get();

        $lineItems = LineItem::whereHas('costPackage', function ($q) use ($project) {
            $q->where('project_id', $project->id);
        })->get();

        $totalBudget = $lineItems->sum('original_amount');

        // With incremental model: period_amount = this month's spend.
        // Cumulative CTD is a running sum of period_amount across periods.
        $periods = [];
        $cumulativeCtd = 0.0;
        $periodCount = $allPeriods->count();

        foreach ($allPeriods as $index => $period) {
            $periodSpend = (float) $period->lineItemForecasts()->sum('period_amount');
            $cumulativeCtd += $periodSpend;

            $periodFcac = (float) $period->lineItemForecasts()->sum('fcac_amount');
            $periodCtc = $periodFcac - $cumulativeCtd;

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
        }

        return [
            'project' => $project,
            'periods' => $periods,
            'totalBudget' => (float) $totalBudget,
            'totalFcac' => $periods ? end($periods)['period_fcac'] : 0.0,
        ];
    }
}
