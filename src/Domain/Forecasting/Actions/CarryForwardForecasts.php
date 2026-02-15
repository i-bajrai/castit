<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\LineItemForecast;

class CarryForwardForecasts
{
    public function execute(ForecastPeriod $oldPeriod, ForecastPeriod $newPeriod): void
    {
        $oldForecasts = LineItemForecast::where('forecast_period_id', $oldPeriod->id)->get();

        foreach ($oldForecasts as $oldForecast) {
            LineItemForecast::create([
                'line_item_id' => $oldForecast->line_item_id,
                'forecast_period_id' => $newPeriod->id,
                'period_rate' => $oldForecast->period_rate,
                'fcac_qty' => $oldForecast->fcac_qty,
                'fcac_rate' => $oldForecast->fcac_rate,
            ]);
        }
    }
}
