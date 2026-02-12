<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\LineItem;
use App\Models\LineItemForecast;
use App\Models\Project;

class EnsureLineItemForecastsExist
{
    public function execute(Project $project, ForecastPeriod $period): void
    {
        $existingLineItemIds = LineItemForecast::where('forecast_period_id', $period->id)
            ->pluck('line_item_id');

        $lineItems = LineItem::whereHas('costPackage', function ($q) use ($project) {
            $q->where('project_id', $project->id);
        })->whereNotIn('id', $existingLineItemIds)->get();

        foreach ($lineItems as $item) {
            LineItemForecast::create([
                'line_item_id' => $item->id,
                'forecast_period_id' => $period->id,
            ]);
        }
    }
}
