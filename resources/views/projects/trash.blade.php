<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Deleted Projects') }}
            </h2>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if($trashedProjects->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900">No deleted projects</h3>
                        <p class="mt-2 text-gray-600">Projects you delete will appear here.</p>
                    </div>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="divide-y divide-gray-200">
                        @foreach($trashedProjects as $project)
                            <div class="p-6 flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $project->name }}</h3>
                                    @if($project->project_number)
                                        <p class="text-sm text-gray-500">{{ $project->project_number }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-gray-400">
                                        Deleted {{ $project->deleted_at->diffForHumans() }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <form method="POST" action="{{ route('projects.restore', $project) }}">
                                        @csrf
                                        <x-secondary-button type="submit">Restore</x-secondary-button>
                                    </form>
                                    <button
                                        x-data=""
                                        x-on:click="$dispatch('open-force-delete-modal', { id: {{ $project->id }}, name: @js($project->name) })"
                                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Delete Forever
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Force delete confirmation modal --}}
    <div x-data="{ projectId: null, projectName: '' }"
         x-on:open-force-delete-modal.window="projectId = $event.detail.id; projectName = $event.detail.name; $dispatch('open-modal', 'confirm-force-delete')">

        <x-modal name="confirm-force-delete" focusable>
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Permanently Delete Project</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Are you sure you want to permanently delete <span class="font-semibold" x-text="projectName"></span>? This action cannot be undone. All control accounts, cost packages, line items, and forecasts will be permanently removed.
                </p>

                <form :action="'/projects/' + projectId + '/force-delete'" method="POST" class="mt-6 flex justify-end gap-3">
                    @csrf
                    @method('DELETE')
                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                    <x-danger-button>Delete Forever</x-danger-button>
                </form>
            </div>
        </x-modal>
    </div>
</x-app-layout>
