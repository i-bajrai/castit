<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Project Settings - {{ $project->name }}
                </h2>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('projects.show', $project) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Cost Detail
                </a>
                <a href="{{ route('projects.executive-summary', $project) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Executive Summary
                </a>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Back to Projects
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            {{-- ==================== PROJECT DETAILS ==================== --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Project Details</h3>
                    <form method="POST" action="{{ route('projects.update', $project) }}">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="name" value="Project Name" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $project->name)" required />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="project_number" value="Project Number" />
                                <x-text-input id="project_number" name="project_number" type="text" class="mt-1 block w-full" :value="old('project_number', $project->project_number)" />
                                <x-input-error :messages="$errors->get('project_number')" class="mt-2" />
                            </div>
                            <div class="md:col-span-2">
                                <x-input-label for="description" value="Description" />
                                <textarea id="description" name="description" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $project->description) }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="original_budget" value="Original Budget" />
                                <x-text-input id="original_budget" name="original_budget" type="number" step="0.01" class="mt-1 block w-full" :value="old('original_budget', $project->original_budget)" required />
                                <x-input-error :messages="$errors->get('original_budget')" class="mt-2" />
                            </div>
                            <div></div>
                            <div>
                                <x-input-label for="start_date" value="Start Date" />
                                <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date', $project->start_date?->format('Y-m-d'))" />
                                <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="end_date" value="End Date" />
                                <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date', $project->end_date?->format('Y-m-d'))" />
                                <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 mt-3">Setting start and end dates will automatically generate forecast periods for each month.</p>

                        <div class="mt-4 flex justify-end">
                            <x-primary-button>Save Project Details</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ==================== CONTROL ACCOUNTS ==================== --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Control Accounts</h3>
                        <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-control-account')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-indigo-700 transition">
                            Add Control Account
                        </button>
                    </div>

                    @if($controlAccounts->isEmpty())
                        <p class="text-gray-500 text-sm py-4">No control accounts configured. Add your first one above.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phase</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Baseline Budget</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Approved Budget</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($controlAccounts as $account)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-600">{{ $account->sort_order }}</td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $account->code }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $account->description }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">{{ $account->phase }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600">{{ $account->category }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($account->baseline_budget, 0) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($account->approved_budget, 0) }}</td>
                                            <td class="px-4 py-3 text-sm text-right">
                                                <div class="flex justify-end gap-2">
                                                    <button
                                                        x-data=""
                                                        x-on:click.prevent="$dispatch('open-modal', 'edit-control-account-{{ $account->id }}')"
                                                        class="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                                                    >Edit</button>
                                                    <button
                                                        x-data=""
                                                        x-on:click.prevent="$dispatch('open-modal', 'delete-control-account-{{ $account->id }}')"
                                                        class="text-red-600 hover:text-red-800 text-sm font-medium"
                                                    >Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ==================== MODALS ==================== --}}

            {{-- Create Control Account Modal --}}
            <x-modal name="create-control-account" :show="false" maxWidth="lg">
                <form method="POST" action="{{ route('projects.control-accounts.store', $project) }}" class="p-6">
                    @csrf
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Add Control Account</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="create_phase" value="Phase" />
                            <x-text-input id="create_phase" name="phase" type="text" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="create_code" value="Code" />
                            <x-text-input id="create_code" name="code" type="text" class="mt-1 block w-full" required />
                        </div>
                        <div class="col-span-2">
                            <x-input-label for="create_description" value="Description" />
                            <x-text-input id="create_description" name="description" type="text" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="create_category" value="Category" />
                            <x-text-input id="create_category" name="category" type="text" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="create_sort_order" value="Sort Order" />
                            <x-text-input id="create_sort_order" name="sort_order" type="number" class="mt-1 block w-full" value="0" required />
                        </div>
                        <div>
                            <x-input-label for="create_baseline_budget" value="Baseline Budget" />
                            <x-text-input id="create_baseline_budget" name="baseline_budget" type="number" step="0.01" class="mt-1 block w-full" value="0" required />
                        </div>
                        <div>
                            <x-input-label for="create_approved_budget" value="Approved Budget" />
                            <x-text-input id="create_approved_budget" name="approved_budget" type="number" step="0.01" class="mt-1 block w-full" value="0" required />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                        <x-primary-button>Create</x-primary-button>
                    </div>
                </form>
            </x-modal>

            {{-- Edit / Delete Modals per account --}}
            @foreach($controlAccounts as $account)
                <x-modal name="edit-control-account-{{ $account->id }}" :show="false" maxWidth="lg">
                    <form method="POST" action="{{ route('projects.control-accounts.update', [$project, $account]) }}" class="p-6">
                        @csrf
                        @method('PUT')
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Edit Control Account - {{ $account->code }}</h2>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="edit_phase_{{ $account->id }}" value="Phase" />
                                <x-text-input id="edit_phase_{{ $account->id }}" name="phase" type="text" class="mt-1 block w-full" :value="$account->phase" required />
                            </div>
                            <div>
                                <x-input-label for="edit_code_{{ $account->id }}" value="Code" />
                                <x-text-input id="edit_code_{{ $account->id }}" name="code" type="text" class="mt-1 block w-full" :value="$account->code" required />
                            </div>
                            <div class="col-span-2">
                                <x-input-label for="edit_description_{{ $account->id }}" value="Description" />
                                <x-text-input id="edit_description_{{ $account->id }}" name="description" type="text" class="mt-1 block w-full" :value="$account->description" required />
                            </div>
                            <div>
                                <x-input-label for="edit_category_{{ $account->id }}" value="Category" />
                                <x-text-input id="edit_category_{{ $account->id }}" name="category" type="text" class="mt-1 block w-full" :value="$account->category" />
                            </div>
                            <div>
                                <x-input-label for="edit_sort_order_{{ $account->id }}" value="Sort Order" />
                                <x-text-input id="edit_sort_order_{{ $account->id }}" name="sort_order" type="number" class="mt-1 block w-full" :value="$account->sort_order" required />
                            </div>
                            <div>
                                <x-input-label for="edit_baseline_budget_{{ $account->id }}" value="Baseline Budget" />
                                <x-text-input id="edit_baseline_budget_{{ $account->id }}" name="baseline_budget" type="number" step="0.01" class="mt-1 block w-full" :value="$account->baseline_budget" required />
                            </div>
                            <div>
                                <x-input-label for="edit_approved_budget_{{ $account->id }}" value="Approved Budget" />
                                <x-text-input id="edit_approved_budget_{{ $account->id }}" name="approved_budget" type="number" step="0.01" class="mt-1 block w-full" :value="$account->approved_budget" required />
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                            <x-primary-button>Save Changes</x-primary-button>
                        </div>
                    </form>
                </x-modal>

                <x-modal name="delete-control-account-{{ $account->id }}" :show="false">
                    <form method="POST" action="{{ route('projects.control-accounts.destroy', [$project, $account]) }}" class="p-6">
                        @csrf
                        @method('DELETE')
                        <h2 class="text-lg font-medium text-gray-900">Delete Control Account</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Are you sure you want to delete <strong>{{ $account->code }}</strong> ({{ $account->description }})?
                            This will also delete all associated forecasts and cannot be undone.
                        </p>
                        <div class="mt-6 flex justify-end gap-3">
                            <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                            <x-danger-button>Delete</x-danger-button>
                        </div>
                    </form>
                </x-modal>
            @endforeach
        </div>
    </div>
</x-app-layout>
