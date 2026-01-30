<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\LineItemForecast;
use Domain\Forecasting\DataTransferObjects\LineItemForecastData;

class SaveLineItemForecasts
{
    /**
     * @param  array<int, LineItemForecastData>  $forecasts
     */
    public function execute(ForecastPeriod $period, array $forecasts): void
    {
        foreach ($forecasts as $data) {
            $fcacAmount = $data->ctdAmount + $data->ctcAmount;

            $existing = LineItemForecast::where('line_item_id', $data->lineItemId)
                ->where('forecast_period_id', $period->id)
                ->first();

            $previousAmount = $existing->previous_amount ?? 0;
            $variance = (float) $previousAmount - $fcacAmount;

            $totalQty = $data->ctdQty + $data->ctcQty;
            $fcacRate = $totalQty > 0 ? $fcacAmount / $totalQty : 0;

            LineItemForecast::updateOrCreate(
                ['line_item_id' => $data->lineItemId, 'forecast_period_id' => $period->id],
                [
                    'ctd_qty' => $data->ctdQty,
                    'ctd_rate' => $data->ctdRate,
                    'ctd_amount' => $data->ctdAmount,
                    'ctc_qty' => $data->ctcQty,
                    'ctc_rate' => $data->ctcRate,
                    'ctc_amount' => $data->ctcAmount,
                    'fcac_rate' => $fcacRate,
                    'fcac_amount' => $fcacAmount,
                    'variance' => $variance,
                    'comments' => $data->comments,
                ],
            );
        }
    }
}
