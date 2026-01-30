<?php

namespace Domain\Forecasting\Actions;

use App\Models\CostPackage;
use App\Models\Project;
use Domain\Forecasting\DataTransferObjects\CostPackageData;

class CreateCostPackage
{
    public function execute(Project $project, CostPackageData $data): CostPackage
    {
        return $project->costPackages()->create([
            'item_no' => $data->itemNo,
            'name' => $data->name,
            'sort_order' => $data->sortOrder,
        ]);
    }
}
