<?php

namespace Domain\Forecasting\Actions;

use App\Models\ControlAccountForecast;
use App\Models\ForecastPeriod;
use Domain\Forecasting\DataTransferObjects\ControlAccountForecastData;

class SaveControlAccountForecasts
{
    /**
     * @param  array<int, ControlAccountForecastData>  $forecasts
     */
    public function execute(ForecastPeriod $period, array $forecasts): void
    {
        foreach ($forecasts as $data) {
            $estimatedFinalCost = $data->costToDate + $data->estimateToComplete;

            $existing = ControlAccountForecast::where('control_account_id', $data->controlAccountId)
                ->where('forecast_period_id', $period->id)
                ->first();

            $lastMonthEfc = $existing->last_month_efc ?? 0;
            $efcMovement = $estimatedFinalCost - (float) $lastMonthEfc;

            ControlAccountForecast::updateOrCreate(
                ['control_account_id' => $data->controlAccountId, 'forecast_period_id' => $period->id],
                [
                    'monthly_cost' => $data->monthlyCost,
                    'cost_to_date' => $data->costToDate,
                    'estimate_to_complete' => $data->estimateToComplete,
                    'estimated_final_cost' => $estimatedFinalCost,
                    'efc_movement' => $efcMovement,
                    'monthly_comments' => $data->monthlyComments,
                ],
            );
        }
    }
}
