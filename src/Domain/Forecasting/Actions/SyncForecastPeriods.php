<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\LineItemForecast;
use App\Models\Project;

class SyncForecastPeriods
{
    public function execute(Project $project): void
    {
        if (! $project->start_date || ! $project->end_date) {
            return;
        }

        $start = $project->start_date->copy()->startOfMonth();
        $end = $project->end_date->copy()->startOfMonth();

        $current = $start->copy();

        $previousPeriod = null;

        while ($current->lte($end)) {
            $period = ForecastPeriod::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'period_date' => $current->copy(),
                ],
                [
                    'is_current' => false,
                ],
            );

            if ($period->wasRecentlyCreated && $previousPeriod) {
                $this->carryForwardLineItemForecasts($previousPeriod, $period);
            }

            $previousPeriod = $period;

            $current->addMonth();
        }
    }

    private function carryForwardLineItemForecasts(ForecastPeriod $oldPeriod, ForecastPeriod $newPeriod): void
    {
        $oldForecasts = LineItemForecast::where('forecast_period_id', $oldPeriod->id)->get();

        foreach ($oldForecasts as $oldForecast) {
            LineItemForecast::create([
                'line_item_id' => $oldForecast->line_item_id,
                'forecast_period_id' => $newPeriod->id,
                'previous_qty' => $oldForecast->ctd_qty + $oldForecast->ctc_qty,
                'previous_rate' => $oldForecast->fcac_rate,
                'previous_amount' => $oldForecast->fcac_amount,
                'ctd_qty' => 0,
                'ctd_rate' => 0,
                'ctd_amount' => 0,
                'ctc_qty' => 0,
                'ctc_rate' => 0,
                'ctc_amount' => 0,
                'fcac_rate' => 0,
                'fcac_amount' => 0,
                'variance' => 0,
            ]);
        }
    }
}
