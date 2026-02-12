<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->belongsToCompany($project->company_id);
    }

    public function update(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->belongsToCompany($project->company_id)) {
            return false;
        }

        return ! $user->isCompanyViewer();
    }

    public function delete(User $user, Project $project): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->belongsToCompany($project->company_id)) {
            return false;
        }

        return $user->isCompanyAdmin();
    }

    public function restore(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $this->delete($user, $project);
    }
}
