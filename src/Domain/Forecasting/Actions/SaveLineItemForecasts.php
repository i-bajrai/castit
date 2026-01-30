<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\LineItem;
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
            $lineItem = LineItem::findOrFail($data->lineItemId);

            $ctdRate = (float) $lineItem->original_rate;
            $ctdAmount = $data->ctdQty * $ctdRate;

            $ctcQty = max(0, (float) $lineItem->original_qty - $data->ctdQty);
            $ctcRate = $ctdRate;
            $ctcAmount = $ctcQty * $ctcRate;

            $fcacAmount = $ctdAmount + $ctcAmount;
            $totalQty = $data->ctdQty + $ctcQty;
            $fcacRate = $totalQty > 0 ? $fcacAmount / $totalQty : 0;

            $existing = LineItemForecast::where('line_item_id', $data->lineItemId)
                ->where('forecast_period_id', $period->id)
                ->first();

            $previousAmount = $existing->previous_amount ?? 0;
            $variance = (float) $previousAmount - $fcacAmount;

            LineItemForecast::updateOrCreate(
                ['line_item_id' => $data->lineItemId, 'forecast_period_id' => $period->id],
                [
                    'ctd_qty' => $data->ctdQty,
                    'ctd_rate' => $ctdRate,
                    'ctd_amount' => $ctdAmount,
                    'ctc_qty' => $ctcQty,
                    'ctc_rate' => $ctcRate,
                    'ctc_amount' => $ctcAmount,
                    'fcac_rate' => $fcacRate,
                    'fcac_amount' => $fcacAmount,
                    'variance' => $variance,
                    'comments' => $data->comments,
                ],
            );
        }
    }
}
