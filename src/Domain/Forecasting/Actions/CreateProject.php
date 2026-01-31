<?php

namespace Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\Project;
use Domain\Forecasting\DataTransferObjects\ProjectData;

class CreateProject
{
    public function execute(Company $company, ProjectData $data): Project
    {
        return $company->projects()->create([
            'name' => $data->name,
            'description' => $data->description,
            'project_number' => $data->projectNumber,
            'original_budget' => $data->originalBudget,
            'start_date' => $data->startDate,
            'end_date' => $data->endDate,
        ]);
    }
}
