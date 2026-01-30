<?php

namespace Domain\Forecasting\DataTransferObjects;

readonly class LineItemForecastData
{
    public function __construct(
        public int $lineItemId,
        public float $ctdQty = 0,
        public ?string $comments = null,
    ) {}
}
