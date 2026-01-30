<?php

namespace Domain\Forecasting\Actions;

use App\Models\ControlAccount;
use App\Models\CostPackage;
use Domain\Forecasting\DataTransferObjects\CostPackageData;

class CreateCostPackage
{
    public function execute(ControlAccount $controlAccount, CostPackageData $data): CostPackage
    {
        return $controlAccount->costPackages()->create([
            'project_id' => $controlAccount->project_id,
            'item_no' => $data->itemNo,
            'name' => $data->name,
            'sort_order' => $data->sortOrder,
        ]);
    }
}
