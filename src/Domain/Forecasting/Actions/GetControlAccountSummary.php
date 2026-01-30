<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class GetControlAccountSummary
{
    /**
     * @return array{
     *   accounts: Collection<int, \App\Models\ControlAccount>,
     *   period: ForecastPeriod|null
     * }
     */
    public function execute(Project $project, ?ForecastPeriod $period = null): array
    {
        if ($period === null) {
            $period = $project->forecastPeriods()
                ->where('period_date', now()->startOfMonth()->toDateString())
                ->first()
                ?? $project->forecastPeriods()->orderByDesc('period_date')->first();
        }

        $accounts = $project->controlAccounts()
            ->with(['forecasts' => function ($query) use ($period): void {
                if ($period) {
                    $query->where('forecast_period_id', $period->id);
                }
            }])
            ->orderBy('sort_order')
            ->get();

        return [
            'accounts' => $accounts,
            'period' => $period,
        ];
    }
}
