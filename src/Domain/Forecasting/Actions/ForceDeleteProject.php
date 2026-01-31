<?php

namespace Domain\Forecasting\Actions;

use App\Models\Project;

class ForceDeleteProject
{
    public function execute(Project $project): void
    {
        $project->forceDelete();
    }
}
