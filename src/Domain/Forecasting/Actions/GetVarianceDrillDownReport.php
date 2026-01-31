<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\Project;

class GetVarianceDrillDownReport
{
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

                    $forecast = $item->forecasts->first();
                    $originalAmount = (float) $item->original_amount;
                    $fcacAmount = $forecast ? (float) $forecast->fcac_amount : 0.0;
                    $variance = $forecast ? (float) $forecast->variance : 0.0;
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
