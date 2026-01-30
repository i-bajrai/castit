<?php

namespace Domain\Forecasting\Actions;

use App\Models\LineItem;

class DeleteLineItem
{
    public function execute(LineItem $lineItem): void
    {
        $lineItem->delete();
    }
}
