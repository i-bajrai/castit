<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use DomainException;

class LockForecastPeriod
{
    public function execute(ForecastPeriod $period): ForecastPeriod
    {
        if ($period->isLocked()) {
            throw new DomainException('Period is already locked.');
        }

        $period->update(['locked_at' => now()]);

        return $period;
    }
}
