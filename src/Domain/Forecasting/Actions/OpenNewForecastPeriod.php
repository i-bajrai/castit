<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\Project;
use Illuminate\Support\Carbon;

class OpenNewForecastPeriod
{
    public function __construct(
        private CarryForwardForecasts $carryForwardForecasts,
    ) {}

    public function execute(Project $project, Carbon $periodDate): ForecastPeriod
    {
        $currentPeriod = $project->forecastPeriods()
            ->where('is_current', true)
            ->first();

        if ($currentPeriod) {
            $currentPeriod->update([
                'is_current' => false,
                'locked_at' => now(),
            ]);
        }

        $newPeriod = $project->forecastPeriods()->create([
            'period_date' => $periodDate->startOfMonth(),
            'is_current' => true,
        ]);

        if ($currentPeriod) {
            $this->carryForwardForecasts->execute($currentPeriod, $newPeriod);
        }

        return $newPeriod;
    }
}
