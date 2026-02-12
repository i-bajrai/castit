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

            $origRate = (float) $lineItem->original_rate;
            $origQty = (float) $lineItem->original_qty;

            LineItemForecast::updateOrCreate(
                ['line_item_id' => $data->lineItemId, 'forecast_period_id' => $period->id],
                [
                    'ctd_qty' => $data->ctdQty,
                    'ctd_rate' => $origRate,
                    'ctc_rate' => $origRate,
                    'fcac_qty' => $origQty,
                    'fcac_rate' => $origRate,
                    'comments' => $data->comments,
                ],
            );
        }
    }
}
