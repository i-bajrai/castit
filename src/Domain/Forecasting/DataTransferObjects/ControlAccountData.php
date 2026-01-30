<?php

namespace Domain\Forecasting\DataTransferObjects;

readonly class ControlAccountData
{
    public function __construct(
        public string $phase,
        public string $code,
        public string $description,
        public ?string $category,
        public float $baselineBudget = 0,
        public float $approvedBudget = 0,
        public int $sortOrder = 0,
    ) {}
}
