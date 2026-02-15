<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\Project;

class GetLineItemProgressReport
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

        $groups = [];
        $totals = [
            'original_qty' => 0.0,
            'original_amount' => 0.0,
            'ctd_qty' => 0.0,
            'ctd_amount' => 0.0,
            'ctc_qty' => 0.0,
            'ctc_amount' => 0.0,
            'fcac_amount' => 0.0,
            'variance' => 0.0,
        ];

        foreach ($accounts as $account) {
            $packages = [];

            foreach ($account->costPackages as $package) {
                $items = [];

                foreach ($package->lineItems as $item) {
                    if ($period && ! $item->existedInPeriod($period)) {
                        continue;
                    }

                    $currentForecast = $period
                        ? $item->forecasts->firstWhere('forecast_period_id', $period->id)
                        : null;

                    $originalQty = (float) $item->original_qty;
                    $originalAmount = (float) $item->original_amount;
                    $ctdQty = (float) $item->forecasts->sum('period_qty');
                    $ctdAmount = (float) $item->forecasts->sum('period_amount');
                    $fcacAmount = $currentForecast ? (float) $currentForecast->fcac_amount : 0.0;
                    $fcacQty = $currentForecast ? (float) $currentForecast->fcac_qty : 0.0;
                    $ctcQty = $fcacQty - $ctdQty;
                    $ctcAmount = $fcacAmount - $ctdAmount;
                    $variance = $fcacAmount - $originalAmount;
                    $pctComplete = $originalQty > 0 ? ($ctdQty / $originalQty) * 100 : 0.0;

                    $items[] = [
                        'item_no' => $item->item_no,
                        'description' => $item->description,
                        'unit_of_measure' => $item->unit_of_measure,
                        'original_qty' => $originalQty,
                        'original_rate' => (float) $item->original_rate,
                        'original_amount' => $originalAmount,
                        'ctd_qty' => $ctdQty,
                        'ctd_amount' => $ctdAmount,
                        'ctc_qty' => $ctcQty,
                        'ctc_amount' => $ctcAmount,
                        'fcac_amount' => $fcacAmount,
                        'variance' => $variance,
                        'pct_complete' => $pctComplete,
                    ];

                    $totals['original_qty'] += $originalQty;
                    $totals['original_amount'] += $originalAmount;
                    $totals['ctd_qty'] += $ctdQty;
                    $totals['ctd_amount'] += $ctdAmount;
                    $totals['ctc_qty'] += $ctcQty;
                    $totals['ctc_amount'] += $ctcAmount;
                    $totals['fcac_amount'] += $fcacAmount;
                    $totals['variance'] += $variance;
                }

                if (! empty($items)) {
                    $packages[] = [
                        'name' => $package->name,
                        'items' => $items,
                    ];
                }
            }

            if (! empty($packages)) {
                $groups[] = [
                    'code' => $account->code,
                    'description' => $account->description,
                    'packages' => $packages,
                ];
            }
        }

        return [
            'project' => $project,
            'period' => $period,
            'groups' => $groups,
            'totals' => $totals,
        ];
    }
}
