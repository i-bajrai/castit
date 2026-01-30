<?php

namespace Domain\Forecasting\Actions;

use App\Models\ControlAccount;
use App\Models\Project;
use Domain\Forecasting\DataTransferObjects\ControlAccountData;

class CreateControlAccount
{
    public function execute(Project $project, ControlAccountData $data): ControlAccount
    {
        return $project->controlAccounts()->create([
            'phase' => $data->phase,
            'code' => $data->code,
            'description' => $data->description,
            'category' => $data->category,
            'baseline_budget' => $data->baselineBudget,
            'approved_budget' => $data->approvedBudget,
            'sort_order' => $data->sortOrder,
        ]);
    }
}
