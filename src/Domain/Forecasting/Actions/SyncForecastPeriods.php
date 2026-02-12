<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\Project;

class SyncForecastPeriods
{
    public function __construct(
        private CarryForwardForecasts $carryForwardForecasts,
        private EnsureLineItemForecastsExist $ensureLineItemForecastsExist,
    ) {}

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
                $this->carryForwardForecasts->execute($previousPeriod, $period);
            }

            $this->ensureLineItemForecastsExist->execute($project, $period);

            $previousPeriod = $period;

            $current->addMonth();
        }
    }
}
