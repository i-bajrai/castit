<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProjectSettingsController extends Controller
{
    public function index(Project $project): View
    {
        Gate::authorize('update', $project);

        $controlAccounts = $project->controlAccounts()->orderBy('sort_order')->get();

        return view('projects.settings.index', [
            'project' => $project,
            'controlAccounts' => $controlAccounts,
        ]);
    }
}
