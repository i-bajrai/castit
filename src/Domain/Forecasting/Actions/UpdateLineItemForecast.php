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
        float $periodQty,
        ?float $periodRate = null,
        ?float $fcacQty = null,
        ?float $fcacRate = null,
        ?string $comments = null,
    ): LineItemForecast {
        $origRate = (float) $lineItem->original_rate;
        $origQty = (float) $lineItem->original_qty;

        // Resolve rate: explicit > previous period's rate > original rate
        $rate = $periodRate ?? $this->getPreviousPeriodRate($lineItem, $period) ?? $origRate;

        return LineItemForecast::updateOrCreate(
            ['line_item_id' => $lineItem->id, 'forecast_period_id' => $period->id],
            [
                'period_qty' => $periodQty,
                'period_rate' => $rate,
                'fcac_qty' => $fcacQty ?? $origQty,
                'fcac_rate' => $fcacRate ?? $rate,
                'comments' => $comments,
            ],
        );
    }

    private function getPreviousPeriodRate(LineItem $lineItem, ForecastPeriod $currentPeriod): ?float
    {
        $previousPeriod = ForecastPeriod::where('project_id', $currentPeriod->project_id)
            ->where('period_date', '<', $currentPeriod->period_date)
            ->orderByDesc('period_date')
            ->first();

        if (! $previousPeriod) {
            return null;
        }

        $previousForecast = LineItemForecast::where('line_item_id', $lineItem->id)
            ->where('forecast_period_id', $previousPeriod->id)
            ->first();

        return $previousForecast?->period_rate ? (float) $previousForecast->period_rate : null;
    }
}
