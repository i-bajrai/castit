<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\LineItem;
use App\Models\LineItemForecast;

class UpdateLineItemForecast
{
    public function execute(
        LineItem $lineItem,
        ForecastPeriod $period,
        float $ctdQty,
        ?string $comments = null,
    ): LineItemForecast {
        $origRate = (float) $lineItem->original_rate;
        $origQty = (float) $lineItem->original_qty;

        return LineItemForecast::updateOrCreate(
            ['line_item_id' => $lineItem->id, 'forecast_period_id' => $period->id],
            [
                'ctd_qty' => $ctdQty,
                'ctd_rate' => $origRate,
                'ctc_rate' => $origRate,
                'fcac_qty' => $origQty,
                'fcac_rate' => $origRate,
                'comments' => $comments,
            ],
        );
    }
}
