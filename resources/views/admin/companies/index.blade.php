<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Company Management') }}
            </h2>
            <button
                x-data=""
                x-on:click="$dispatch('open-modal', 'create-company')"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
            >
                New Company
            </button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4"
                     x-data="{ show: true }" x-show="show" x-transition
                     x-init="setTimeout(() => show = false, 4000)">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4"
                     x-data="{ show: true }" x-show="show" x-transition
                     x-init="setTimeout(() => show = false, 4000)">
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Members</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Projects</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($companies as $company)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $company->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $company->members_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $company->projects_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $company->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <button
                                            x-data=""
                                            x-on:click="$dispatch('open-edit-company', {
                                                id: {{ $company->id }},
                                                name: '{{ addslashes($company->name) }}'
                                            })"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >Edit</button>

                                        <button
                                            x-data=""
                                            x-on:click="$dispatch('open-delete-company', {
                                                id: {{ $company->id }},
                                                name: '{{ addslashes($company->name) }}',
                                                members_count: {{ $company->members_count }},
                                                projects_count: {{ $company->projects_count }}
                                            })"
                                            class="text-red-600 hover:text-red-900"
                                        >Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Company Modal --}}
    <x-modal name="create-company" focusable>
        <form method="POST" action="{{ route('admin.companies.store') }}" class="p-6">
            @csrf

            <h2 class="text-lg font-medium text-gray-900">Create New Company</h2>
            <p class="mt-1 text-sm text-gray-600">Add a new company to the system.</p>

            <div class="mt-4">
                <x-input-label for="create-company-name" value="Company Name" />
                <x-text-input id="create-company-name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <x-primary-button>Create Company</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Edit Company Modal --}}
    <div x-data="{ companyId: null, companyName: '' }"
         x-on:open-edit-company.window="
             companyId = $event.detail.id;
             companyName = $event.detail.name;
             $dispatch('open-modal', 'edit-company')
         ">

        <x-modal name="edit-company" focusable>
            <form :action="'/admin/companies/' + companyId" method="POST" class="p-6">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-medium text-gray-900">Edit Company</h2>
                <p class="mt-1 text-sm text-gray-600">Update company details.</p>

                <div class="mt-4">
                    <x-input-label for="edit-company-name" value="Company Name" />
                    <input id="edit-company-name" name="name" type="text" x-model="companyName"
                           class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                    <x-primary-button>Update Company</x-primary-button>
                </div>
            </form>
        </x-modal>
    </div>

    {{-- Delete Company Confirmation Modal --}}
    <div x-data="{ companyId: null, companyName: '', membersCount: 0, projectsCount: 0 }"
         x-on:open-delete-company.window="
             companyId = $event.detail.id;
             companyName = $event.detail.name;
             membersCount = $event.detail.members_count;
             projectsCount = $event.detail.projects_count;
             $dispatch('open-modal', 'confirm-delete-company')
         ">

        <x-modal name="confirm-delete-company" focusable>
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Delete Company</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Are you sure you want to delete <span class="font-semibold" x-text="companyName"></span>?
                </p>
                <template x-if="membersCount > 0">
                    <p class="mt-1 text-sm text-amber-600">
                        This company has <span x-text="membersCount"></span> member(s) who will be unassigned.
                    </p>
                </template>
                <template x-if="projectsCount > 0">
                    <p class="mt-1 text-sm text-red-600">
                        This company has <span x-text="projectsCount"></span> project(s). Delete the projects first.
                    </p>
                </template>

                <form :action="'/admin/companies/' + companyId" method="POST" class="mt-6 flex justify-end gap-3">
                    @csrf
                    @method('DELETE')
                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                    <x-danger-button>Delete Company</x-danger-button>
                </form>
            </div>
        </x-modal>
    </div>
</x-app-layout>
