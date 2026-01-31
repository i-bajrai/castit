<?php

namespace Domain\Forecasting\Actions;

use App\Models\Project;

class RestoreProject
{
    public function execute(Project $project): void
    {
        $project->restore();
    }
}
