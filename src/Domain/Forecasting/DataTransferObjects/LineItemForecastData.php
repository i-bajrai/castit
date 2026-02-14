<?php

namespace Domain\Forecasting\DataTransferObjects;

readonly class LineItemForecastData
{
    public function __construct(
        public int $lineItemId,
        public float $periodQty = 0,
        public ?float $periodRate = null,
        public ?string $comments = null,
    ) {}
}
