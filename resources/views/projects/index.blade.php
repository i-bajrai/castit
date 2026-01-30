<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Projects') }}
            </h2>
            <button
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
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900">No projects yet</h3>
                        <p class="mt-2 text-gray-600">Get started by creating your first construction project.</p>
                        <button
                            x-data=""
                            x-on:click="$dispatch('open-modal', 'create-project')"
                            class="mt-6 inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            Create Project
                        </button>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($projects as $project)
                        <a href="{{ route('projects.show', $project) }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition">
                            <div class="p-6">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $project->name }}</h3>
                                        @if($project->project_number)
                                            <p class="mt-1 text-sm text-gray-500">{{ $project->project_number }}</p>
                                        @endif
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $project->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
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
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <x-modal name="create-project" focusable>
        <form method="POST" action="{{ route('projects.store') }}" class="p-6">
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

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <x-primary-button>Create Project</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
