<?php

namespace Domain\Forecasting\Actions;

use App\Models\CostPackage;

class DeleteCostPackage
{
    public function execute(CostPackage $costPackage): void
    {
        $costPackage->delete();
    }
}
