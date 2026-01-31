<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="reports">
            Reports - {{ $project->name }}
        </x-project-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <a href="{{ route('projects.executive-summary', $project) }}"
                   class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition border border-transparent hover:border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Executive Summary</h3>
                    <p class="mt-2 text-sm text-gray-500">High-level overview of control account forecasts and variances.</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
