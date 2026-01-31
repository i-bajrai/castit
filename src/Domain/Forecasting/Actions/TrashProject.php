<?php

namespace Domain\Forecasting\Actions;

use App\Models\Project;

class TrashProject
{
    public function execute(Project $project): void
    {
        $project->delete();
    }
}
