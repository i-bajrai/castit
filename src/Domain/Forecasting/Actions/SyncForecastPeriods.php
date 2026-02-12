<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\LineItem;
use App\Models\LineItemForecast;
use App\Models\Project;

class SyncForecastPeriods
{
    public function execute(Project $project): void
    {
        if (! $project->start_date || ! $project->end_date) {
            return;
        }

        $start = $project->start_date->copy()->startOfMonth();
        $end = $project->end_date->copy()->startOfMonth();

        $current = $start->copy();

        $previousPeriod = null;

        while ($current->lte($end)) {
            $period = ForecastPeriod::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'period_date' => $current->copy(),
                ],
                [
                    'is_current' => false,
                ],
            );

            if ($period->wasRecentlyCreated && $previousPeriod) {
                $this->carryForwardLineItemForecasts($previousPeriod, $period);
            }

            $this->ensureAllLineItemsHaveForecasts($project, $period);

            $previousPeriod = $period;

            $current->addMonth();
        }
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

    private function ensureAllLineItemsHaveForecasts(Project $project, ForecastPeriod $period): void
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
