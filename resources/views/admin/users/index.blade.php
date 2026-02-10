<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('User Management') }}
            </h2>
            <button
                x-data=""
                x-on:click="$dispatch('open-modal', 'create-user')"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
            >
                New User
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">System Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($users as $user)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $user->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $badgeClasses = match($user->role) {
                                                \App\Enums\UserRole::Admin => 'bg-red-100 text-red-800',
                                                \App\Enums\UserRole::User => 'bg-gray-100 text-gray-800',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses }}">
                                            {{ $user->role->label() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->company?->name ?? '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($user->company_role)
                                            @php
                                                $companyBadge = match($user->company_role) {
                                                    \App\Enums\CompanyRole::Admin => 'bg-purple-100 text-purple-800',
                                                    \App\Enums\CompanyRole::Engineer => 'bg-blue-100 text-blue-800',
                                                    \App\Enums\CompanyRole::Viewer => 'bg-gray-100 text-gray-800',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $companyBadge }}">
                                                {{ $user->company_role->label() }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <button
                                            x-data=""
                                            x-on:click="$dispatch('open-edit-user', {
                                                id: {{ $user->id }},
                                                name: '{{ addslashes($user->name) }}',
                                                email: '{{ addslashes($user->email) }}',
                                                role: '{{ $user->role->value }}',
                                                company_id: '{{ $user->company_id ?? '' }}',
                                                company_role: '{{ $user->company_role?->value ?? '' }}'
                                            })"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >Edit</button>

                                        @if($user->id !== Auth::id())
                                            <button
                                                x-data=""
                                                x-on:click="$dispatch('open-delete-user', {
                                                    id: {{ $user->id }},
                                                    name: '{{ addslashes($user->name) }}'
                                                })"
                                                class="text-red-600 hover:text-red-900"
                                            >Delete</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Create User Modal --}}
    <x-modal name="create-user" focusable>
        <form method="POST" action="{{ route('admin.users.store') }}" class="p-6">
            @csrf

            <h2 class="text-lg font-medium text-gray-900">Create New User</h2>
            <p class="mt-1 text-sm text-gray-600">Add a new user to the system.</p>

            <div class="mt-4">
                <x-input-label for="create-name" value="Name" />
                <x-text-input id="create-name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="create-email" value="Email" />
                <x-text-input id="create-email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="create-role" value="System Role" />
                <select id="create-role" name="role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}" {{ old('role') === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="create-company_id" value="Company" />
                <select id="create-company_id" name="company_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">— No Company —</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ old('company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('company_id')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="create-company_role" value="Company Role" />
                <select id="create-company_role" name="company_role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">— None —</option>
                    @foreach($companyRoles as $companyRole)
                        <option value="{{ $companyRole->value }}" {{ old('company_role') === $companyRole->value ? 'selected' : '' }}>{{ $companyRole->label() }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('company_role')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="create-password" value="Password" />
                <x-text-input id="create-password" name="password" type="password" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="create-password-confirmation" value="Confirm Password" />
                <x-text-input id="create-password-confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <x-primary-button>Create User</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Edit User Modal --}}
    <div x-data="{ userId: null, userName: '', userEmail: '', userRole: '', userCompanyId: '', userCompanyRole: '' }"
         x-on:open-edit-user.window="
             userId = $event.detail.id;
             userName = $event.detail.name;
             userEmail = $event.detail.email;
             userRole = $event.detail.role;
             userCompanyId = $event.detail.company_id;
             userCompanyRole = $event.detail.company_role;
             $dispatch('open-modal', 'edit-user')
         ">

        <x-modal name="edit-user" focusable>
            <form :action="'/admin/users/' + userId" method="POST" class="p-6">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-medium text-gray-900">Edit User</h2>
                <p class="mt-1 text-sm text-gray-600">Update user details and role.</p>

                <div class="mt-4">
                    <x-input-label for="edit-name" value="Name" />
                    <input id="edit-name" name="name" type="text" x-model="userName"
                           class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="edit-email" value="Email" />
                    <input id="edit-email" name="email" type="email" x-model="userEmail"
                           class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="edit-role" value="System Role" />
                    <select id="edit-role" name="role" x-model="userRole"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                        @foreach($roles as $role)
                            <option value="{{ $role->value }}">{{ $role->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="edit-company_id" value="Company" />
                    <select id="edit-company_id" name="company_id" x-model="userCompanyId"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">— No Company —</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('company_id')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="edit-company_role" value="Company Role" />
                    <select id="edit-company_role" name="company_role" x-model="userCompanyRole"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">— None —</option>
                        @foreach($companyRoles as $companyRole)
                            <option value="{{ $companyRole->value }}">{{ $companyRole->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('company_role')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="edit-password" value="New Password (leave blank to keep current)" />
                    <input id="edit-password" name="password" type="password"
                           class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="edit-password-confirmation" value="Confirm New Password" />
                    <input id="edit-password-confirmation" name="password_confirmation" type="password"
                           class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                    <x-primary-button>Update User</x-primary-button>
                </div>
            </form>
        </x-modal>
    </div>

    {{-- Delete User Confirmation Modal --}}
    <div x-data="{ userId: null, userName: '' }"
         x-on:open-delete-user.window="
             userId = $event.detail.id;
             userName = $event.detail.name;
             $dispatch('open-modal', 'confirm-delete-user')
         ">

        <x-modal name="confirm-delete-user" focusable>
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Delete User</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Are you sure you want to delete <span class="font-semibold" x-text="userName"></span>? This action cannot be undone and will remove all their data.
                </p>

                <form :action="'/admin/users/' + userId" method="POST" class="mt-6 flex justify-end gap-3">
                    @csrf
                    @method('DELETE')
                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                    <x-danger-button>Delete User</x-danger-button>
                </form>
            </div>
        </x-modal>
    </div>
</x-app-layout>
