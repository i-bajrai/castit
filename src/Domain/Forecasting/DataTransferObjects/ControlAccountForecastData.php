<?php

namespace Domain\Forecasting\DataTransferObjects;

readonly class ControlAccountForecastData
{
    public function __construct(
        public int $controlAccountId,
        public float $monthlyCost = 0,
        public float $costToDate = 0,
        public float $estimateToComplete = 0,
        public ?string $monthlyComments = null,
    ) {}
}
