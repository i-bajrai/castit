<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\LineItemForecast;
use App\Models\Project;
use Illuminate\Support\Carbon;

class OpenNewForecastPeriod
{
    public function execute(Project $project, Carbon $periodDate): ForecastPeriod
    {
        $currentPeriod = $project->forecastPeriods()
            ->where('is_current', true)
            ->first();

        if ($currentPeriod) {
            $currentPeriod->update([
                'is_current' => false,
                'locked_at' => now(),
            ]);
        }

        $newPeriod = $project->forecastPeriods()->create([
            'period_date' => $periodDate->startOfMonth(),
            'is_current' => true,
        ]);

        if ($currentPeriod) {
            $this->carryForwardLineItemForecasts($currentPeriod, $newPeriod);
        }

        return $newPeriod;
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
