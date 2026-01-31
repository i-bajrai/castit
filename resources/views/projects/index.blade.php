<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Projects') }}
            </h2>
            <button
                data-testid="new-project-button"
                x-data=""
                x-on:click="$dispatch('open-modal', 'create-project')"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
            >
                New Project
            </button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($projects->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div data-testid="empty-state" class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900">No projects yet</h3>
                        <p class="mt-2 text-gray-600">Get started by creating your first construction project.</p>
                        <button
                            data-testid="empty-state-create-button"
                            x-data=""
                            x-on:click="$dispatch('open-modal', 'create-project')"
                            class="mt-6 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Create Project
                        </button>
                    </div>
                </div>
            @else
                <div class="mb-4 flex justify-end">
                    <a href="{{ route('projects.trash') }}" class="inline-flex items-center text-sm text-red-600 hover:text-red-800 transition">
                        View deleted projects
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                <div data-testid="projects-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($projects as $project)
                        <div data-testid="project-card" class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition relative group">
                            <button
                                x-data=""
                                x-on:click="$dispatch('open-trash-modal', { id: {{ $project->id }}, name: '{{ addslashes($project->name) }}' })"
                                class="absolute top-4 right-4 z-10 p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition"
                                title="Delete project"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                            <a href="{{ route('projects.show', $project) }}" class="block p-6">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $project->name }}</h3>
                                        @if($project->project_number)
                                            <p class="mt-1 text-sm text-gray-500">{{ $project->project_number }}</p>
                                        @endif
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $project->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }} mr-6">
                                        {{ ucfirst($project->status) }}
                                    </span>
                                </div>

                                @if($project->description)
                                    <p class="mt-3 text-sm text-gray-600 line-clamp-2">{{ $project->description }}</p>
                                @endif

                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Original Budget</span>
                                        <span class="font-semibold text-gray-900">${{ number_format($project->original_budget, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm mt-1">
                                        <span class="text-gray-500">Control Accounts</span>
                                        <span class="font-medium text-gray-700">{{ $project->control_accounts_count }}</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Trash confirmation modal --}}
    <div x-data="{ projectId: null, projectName: '' }"
         x-on:open-trash-modal.window="projectId = $event.detail.id; projectName = $event.detail.name; $dispatch('open-modal', 'confirm-trash-project')">

        <x-modal name="confirm-trash-project" focusable>
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Delete Project</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Are you sure you want to delete <span class="font-semibold" x-text="projectName"></span>? You can restore it later from deleted projects.
                </p>

                <form :action="'/projects/' + projectId" method="POST" class="mt-6 flex justify-end gap-3">
                    @csrf
                    @method('DELETE')
                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                    <x-danger-button>Delete</x-danger-button>
                </form>
            </div>
        </x-modal>
    </div>

    <x-modal name="create-project" focusable>
        <form data-testid="create-project-form" method="POST" action="{{ route('projects.store') }}" class="p-6">
            @csrf

            <h2 class="text-lg font-medium text-gray-900">Create New Project</h2>
            <p class="mt-1 text-sm text-gray-600">Add a new construction project to track.</p>

            @if($companies->count() > 1)
                <div class="mt-4">
                    <x-input-label for="company_id" value="Company" />
                    <select id="company_id" name="company_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('company_id')" class="mt-2" />
                </div>
            @else
                <input type="hidden" name="company_id" value="{{ $companies->first()?->id }}" />
            @endif

            <div class="mt-4">
                <x-input-label for="name" value="Project Name" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="project_number" value="Project Number" />
                <x-text-input id="project_number" name="project_number" type="text" class="mt-1 block w-full" :value="old('project_number')" />
                <x-input-error :messages="$errors->get('project_number')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="description" value="Description" />
                <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="original_budget" value="Original Budget ($)" />
                <x-text-input id="original_budget" name="original_budget" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('original_budget')" required />
                <x-input-error :messages="$errors->get('original_budget')" class="mt-2" />
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="start_date" value="Start Date" />
                    <x-text-input data-testid="start-date-input" id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date')" />
                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="end_date" value="End Date" />
                    <x-text-input data-testid="end-date-input" id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date')" />
                    <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <x-primary-button data-testid="submit-create-project">Create Project</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
