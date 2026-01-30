<?php

namespace Domain\Forecasting\Actions;

use App\Models\Project;
use App\Models\User;
use Domain\Forecasting\DataTransferObjects\ProjectData;

class CreateProject
{
    public function execute(User $user, ProjectData $data): Project
    {
        return $user->projects()->create([
            'name' => $data->name,
            'description' => $data->description,
            'project_number' => $data->projectNumber,
            'original_budget' => $data->originalBudget,
        ]);
    }
}
