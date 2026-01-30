<?php

namespace Domain\Forecasting\DataTransferObjects;

readonly class CostPackageData
{
    public function __construct(
        public string $name,
        public ?string $itemNo = null,
        public int $sortOrder = 0,
        public ?int $controlAccountId = null,
    ) {}
}
