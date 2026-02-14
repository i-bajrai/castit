<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\Project;

class GetVarianceDrillDownReport
{
    /**
     * @return array<string, mixed>
     */
    public function execute(Project $project, ?ForecastPeriod $period = null): array
    {
        if ($period === null) {
            $period = $project->forecastPeriods()
                ->where('period_date', now()->startOfMonth())
                ->first()
                ?? $project->forecastPeriods()->orderByDesc('period_date')->first();
        }

        $periodIdsUpTo = collect();
        if ($period) {
            $periodIdsUpTo = $project->forecastPeriods()
                ->where('period_date', '<=', $period->period_date)
                ->pluck('id');
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

        $items = [];
        $totals = [
            'original_amount' => 0.0,
            'fcac_amount' => 0.0,
            'variance' => 0.0,
        ];

        foreach ($accounts as $account) {
            foreach ($account->costPackages as $package) {
                foreach ($package->lineItems as $item) {
                    if ($period && ! $item->existedInPeriod($period)) {
                        continue;
                    }

                    $currentForecast = $period
                        ? $item->forecasts->firstWhere('forecast_period_id', $period->id)
                        : null;
                    $originalAmount = (float) $item->original_amount;
                    $fcacAmount = $currentForecast ? (float) $currentForecast->fcac_amount : 0.0;
                    $variance = $fcacAmount - $originalAmount;
                    $variancePct = $originalAmount > 0
                        ? ($variance / $originalAmount) * 100
                        : 0.0;

                    $items[] = [
                        'ca_code' => $account->code,
                        'ca_description' => $account->description,
                        'package_name' => $package->name,
                        'description' => $item->description,
                        'item_no' => $item->item_no,
                        'original_amount' => $originalAmount,
                        'fcac_amount' => $fcacAmount,
                        'variance' => $variance,
                        'variance_pct' => $variancePct,
                    ];

                    $totals['original_amount'] += $originalAmount;
                    $totals['fcac_amount'] += $fcacAmount;
                    $totals['variance'] += $variance;
                }
            }
        }

        // Sort by absolute variance descending
        usort($items, fn ($a, $b) => abs($b['variance']) <=> abs($a['variance']));

        return [
            'project' => $project,
            'period' => $period,
            'items' => $items,
            'totals' => $totals,
        ];
    }
}
