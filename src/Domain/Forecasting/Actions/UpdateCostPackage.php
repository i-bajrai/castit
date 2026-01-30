<?php

namespace Domain\Forecasting\Actions;

use App\Models\CostPackage;
use Domain\Forecasting\DataTransferObjects\CostPackageData;

class UpdateCostPackage
{
    public function execute(CostPackage $costPackage, CostPackageData $data): CostPackage
    {
        $costPackage->update([
            'item_no' => $data->itemNo,
            'name' => $data->name,
            'sort_order' => $data->sortOrder,
        ]);

        return $costPackage;
    }
}
