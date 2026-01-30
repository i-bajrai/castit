<?php

namespace Domain\Forecasting\DataTransferObjects;

readonly class LineItemData
{
    public function __construct(
        public string $description,
        public ?string $itemNo = null,
        public ?string $unitOfMeasure = null,
        public float $originalQty = 0,
        public float $originalRate = 0,
        public float $originalAmount = 0,
        public int $sortOrder = 0,
    ) {}
}
