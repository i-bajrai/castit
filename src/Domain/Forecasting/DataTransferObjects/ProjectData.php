<?php

namespace Domain\Forecasting\DataTransferObjects;

readonly class ProjectData
{
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $projectNumber,
        public float $originalBudget = 0,
        public ?string $startDate = null,
        public ?string $endDate = null,
    ) {}
}
