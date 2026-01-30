<?php

namespace Domain\Forecasting\DataTransferObjects;

readonly class LineItemForecastData
{
    public function __construct(
        public int $lineItemId,
        public float $ctdQty = 0,
        public float $ctdRate = 0,
        public float $ctdAmount = 0,
        public float $ctcQty = 0,
        public float $ctcRate = 0,
        public float $ctcAmount = 0,
        public ?string $comments = null,
    ) {}
}
