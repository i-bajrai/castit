<?php

namespace Domain\Forecasting\Actions;

use App\Models\CostPackage;
use App\Models\LineItem;
use Domain\Forecasting\DataTransferObjects\LineItemData;

class CreateLineItem
{
    public function execute(CostPackage $costPackage, LineItemData $data): LineItem
    {
        return $costPackage->lineItems()->create([
            'item_no' => $data->itemNo,
            'description' => $data->description,
            'unit_of_measure' => $data->unitOfMeasure,
            'original_qty' => $data->originalQty,
            'original_rate' => $data->originalRate,
            'original_amount' => $data->originalAmount,
            'sort_order' => $data->sortOrder,
        ]);
    }
}
