<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\Project;

class GetEarnedValueReport
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

        $allPeriods = $project->forecastPeriods()->orderBy('period_date')->get();
        $totalPeriodCount = $allPeriods->count();

        // Determine how many periods have elapsed up to (and including) the selected period
        $elapsedPeriods = 0;
        if ($period) {
            foreach ($allPeriods as $p) {
                $elapsedPeriods++;
                if ($p->id === $period->id) {
                    break;
                }
            }
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

        $rows = [];
        $totals = [
            'bac' => 0.0,
            'pv' => 0.0,
            'ev' => 0.0,
            'ac' => 0.0,
            'sv' => 0.0,
            'cv' => 0.0,
            'eac' => 0.0,
            'vac' => 0.0,
        ];

        foreach ($accounts as $account) {
            $bac = 0.0; // Budget at Completion (sum of original amounts)
            $ac = 0.0;  // Actual Cost (cost to date)
            $totalOriginalQty = 0.0;
            $totalCtdQty = 0.0;

            foreach ($account->costPackages as $package) {
                foreach ($package->lineItems as $item) {
                    if ($period && ! $item->existedInPeriod($period)) {
                        continue;
                    }

                    $bac += (float) $item->original_amount;
                    $totalOriginalQty += (float) $item->original_qty;

                    $ac += (float) $item->forecasts->sum('period_amount');
                    $totalCtdQty += (float) $item->forecasts->sum('period_qty');
                }
            }

            // % Complete (weighted by qty)
            $pctComplete = $totalOriginalQty > 0
                ? $totalCtdQty / $totalOriginalQty
                : 0.0;

            // Planned Value = BAC * (elapsed periods / total periods)
            $pv = $totalPeriodCount > 0
                ? $bac * ($elapsedPeriods / $totalPeriodCount)
                : 0.0;

            // Earned Value = BAC * % complete
            $ev = $bac * $pctComplete;

            // Schedule Variance & Cost Variance
            $sv = $ev - $pv;
            $cv = $ev - $ac;

            // Performance indices
            $spi = $pv > 0 ? $ev / $pv : 0.0;
            $cpi = $ac > 0 ? $ev / $ac : 0.0;

            // Estimate at Completion = BAC / CPI
            $eac = $cpi > 0 ? $bac / $cpi : $bac;

            // Variance at Completion = BAC - EAC
            $vac = $bac - $eac;

            $rows[] = [
                'code' => $account->code,
                'description' => $account->description,
                'bac' => $bac,
                'pv' => $pv,
                'ev' => $ev,
                'ac' => $ac,
                'sv' => $sv,
                'cv' => $cv,
                'spi' => $spi,
                'cpi' => $cpi,
                'eac' => $eac,
                'vac' => $vac,
                'pct_complete' => $pctComplete * 100,
            ];

            $totals['bac'] += $bac;
            $totals['pv'] += $pv;
            $totals['ev'] += $ev;
            $totals['ac'] += $ac;
            $totals['sv'] += $sv;
            $totals['cv'] += $cv;
            $totals['eac'] += $eac;
            $totals['vac'] += $vac;
        }

        // Calculate total-level indices
        $totals['spi'] = $totals['pv'] > 0 ? $totals['ev'] / $totals['pv'] : 0.0;
        $totals['cpi'] = $totals['ac'] > 0 ? $totals['ev'] / $totals['ac'] : 0.0;

        return [
            'project' => $project,
            'period' => $period,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }
}
