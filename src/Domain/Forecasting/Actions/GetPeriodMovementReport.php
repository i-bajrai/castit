<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\Project;

class GetPeriodMovementReport
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

        $previousPeriod = null;
        if ($period) {
            $previousPeriod = $project->forecastPeriods()
                ->where('period_date', '<', $period->period_date)
                ->orderByDesc('period_date')
                ->first();
        }

        $periodIdsUpTo = collect();
        $previousPeriodIdsUpTo = collect();
        if ($period) {
            $periodIdsUpTo = $project->forecastPeriods()
                ->where('period_date', '<=', $period->period_date)
                ->pluck('id');
        }
        if ($previousPeriod) {
            $previousPeriodIdsUpTo = $project->forecastPeriods()
                ->where('period_date', '<=', $previousPeriod->period_date)
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

        $rows = [];
        $totals = [
            'prev_ctd_amount' => 0.0,
            'prev_ctc_amount' => 0.0,
            'prev_fcac_amount' => 0.0,
            'curr_ctd_amount' => 0.0,
            'curr_ctc_amount' => 0.0,
            'curr_fcac_amount' => 0.0,
            'ctd_delta' => 0.0,
            'ctc_delta' => 0.0,
            'fcac_delta' => 0.0,
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
                    $previousForecast = $previousPeriod
                        ? $item->forecasts->firstWhere('forecast_period_id', $previousPeriod->id)
                        : null;

                    // Current cumulative CTD = sum all periods up to current
                    $currCtd = (float) $item->forecasts->sum('period_amount');
                    $currFcac = $currentForecast ? (float) $currentForecast->fcac_amount : 0.0;
                    $currCtc = $currFcac - $currCtd;

                    // Previous cumulative CTD = sum all periods up to previous
                    $prevCtd = (float) $item->forecasts
                        ->filter(fn ($f) => $previousPeriodIdsUpTo->contains($f->forecast_period_id))
                        ->sum('period_amount');
                    $prevFcac = $previousForecast ? (float) $previousForecast->fcac_amount : 0.0;
                    $prevCtc = $prevFcac - $prevCtd;

                    $ctdDelta = $currCtd - $prevCtd;
                    $ctcDelta = $currCtc - $prevCtc;
                    $fcacDelta = $currFcac - $prevFcac;

                    // Only include items where something changed
                    if ($ctdDelta == 0 && $ctcDelta == 0 && $fcacDelta == 0) {
                        continue;
                    }

                    $rows[] = [
                        'ca_code' => $account->code,
                        'description' => $item->description,
                        'item_no' => $item->item_no,
                        'prev_ctd_amount' => $prevCtd,
                        'prev_ctc_amount' => $prevCtc,
                        'prev_fcac_amount' => $prevFcac,
                        'curr_ctd_amount' => $currCtd,
                        'curr_ctc_amount' => $currCtc,
                        'curr_fcac_amount' => $currFcac,
                        'ctd_delta' => $ctdDelta,
                        'ctc_delta' => $ctcDelta,
                        'fcac_delta' => $fcacDelta,
                    ];

                    $totals['prev_ctd_amount'] += $prevCtd;
                    $totals['prev_ctc_amount'] += $prevCtc;
                    $totals['prev_fcac_amount'] += $prevFcac;
                    $totals['curr_ctd_amount'] += $currCtd;
                    $totals['curr_ctc_amount'] += $currCtc;
                    $totals['curr_fcac_amount'] += $currFcac;
                    $totals['ctd_delta'] += $ctdDelta;
                    $totals['ctc_delta'] += $ctcDelta;
                    $totals['fcac_delta'] += $fcacDelta;
                }
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
