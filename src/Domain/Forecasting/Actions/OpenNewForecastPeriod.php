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
                'previous_qty' => $oldForecast->fcac_qty,
                'previous_rate' => $oldForecast->fcac_rate,
            ]);
        }
    }
}
