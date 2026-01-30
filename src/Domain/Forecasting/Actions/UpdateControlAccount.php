<?php

namespace Domain\Forecasting\Actions;

use App\Models\ControlAccount;
use Domain\Forecasting\DataTransferObjects\ControlAccountData;

class UpdateControlAccount
{
    public function execute(ControlAccount $controlAccount, ControlAccountData $data): ControlAccount
    {
        $controlAccount->update([
            'phase' => $data->phase,
            'code' => $data->code,
            'description' => $data->description,
            'category' => $data->category,
            'baseline_budget' => $data->baselineBudget,
            'approved_budget' => $data->approvedBudget,
            'sort_order' => $data->sortOrder,
        ]);

        return $controlAccount;
    }
}
