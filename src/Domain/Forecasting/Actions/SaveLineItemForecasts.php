<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\LineItem;
use Domain\Forecasting\DataTransferObjects\LineItemForecastData;

class SaveLineItemForecasts
{
    public function __construct(
        private UpdateLineItemForecast $updateLineItemForecast,
    ) {}

    /**
     * @param  array<int, LineItemForecastData>  $forecasts
     */
    public function execute(ForecastPeriod $period, array $forecasts): void
    {
        foreach ($forecasts as $data) {
            $lineItem = LineItem::findOrFail($data->lineItemId);

            $this->updateLineItemForecast->execute(
                $lineItem,
                $period,
                $data->ctdQty,
                $data->comments,
            );
        }
    }
}
