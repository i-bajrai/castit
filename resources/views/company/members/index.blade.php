<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Team Members') }} &mdash; {{ $company->name }}
            </h2>
            <button
                x-data=""
                x-on:click="$dispatch('open-modal', 'create-member')"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
            >
                Add Member
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($members as $member)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $member->name }}
                                        @if($member->id === Auth::id())
                                            <span class="text-xs text-gray-400">(you)</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $member->email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $badgeClasses = match($member->company_role) {
                                                \App\Enums\CompanyRole::Admin => 'bg-purple-100 text-purple-800',
                                                \App\Enums\CompanyRole::Engineer => 'bg-blue-100 text-blue-800',
                                                \App\Enums\CompanyRole::Viewer => 'bg-gray-100 text-gray-800',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses }}">
                                            {{ $member->company_role->label() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $member->created_at->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <button
                                            x-data=""
                                            x-on:click="$dispatch('open-edit-member', {
                                                id: {{ $member->id }},
                                                name: @js($member->name),
                                                company_role: '{{ $member->company_role->value }}'
                                            })"
                                            class="text-indigo-600 hover:text-indigo-900"
                                        >Edit Role</button>

                                        @if($member->id !== Auth::id())
                                            <button
                                                x-data=""
                                                x-on:click="$dispatch('open-remove-member', {
                                                    id: {{ $member->id }},
                                                    name: @js($member->name)
                                                })"
                                                class="text-red-600 hover:text-red-900"
                                            >Remove</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $members->links() }}
            </div>

            @if($removedMembers->isNotEmpty())
                <div class="mt-8">
                    <h3 class="text-lg font-medium text-gray-700 mb-4">Removed Members</h3>
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Previous Role</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Removed</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($removedMembers as $removed)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-500">
                                                {{ $removed->name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                                {{ $removed->email }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $removedBadge = match($removed->company_role) {
                                                        \App\Enums\CompanyRole::Admin => 'bg-purple-50 text-purple-400',
                                                        \App\Enums\CompanyRole::Engineer => 'bg-blue-50 text-blue-400',
                                                        \App\Enums\CompanyRole::Viewer => 'bg-gray-50 text-gray-400',
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $removedBadge }}">
                                                    {{ $removed->company_role->label() }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                                                {{ $removed->company_removed_at->format('M d, Y') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button
                                                    x-data=""
                                                    x-on:click="$dispatch('open-restore-member', {
                                                        id: {{ $removed->id }},
                                                        name: @js($removed->name)
                                                    })"
                                                    class="text-green-600 hover:text-green-900"
                                                >Restore</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Add Member Modal --}}
    <x-modal name="create-member" focusable>
        <form method="POST" action="{{ route('company.members.store') }}" class="p-6">
            @csrf

            <h2 class="text-lg font-medium text-gray-900">Add Team Member</h2>
            <p class="mt-1 text-sm text-gray-600">Create a new user account and add them to your company.</p>

            <div class="mt-4">
                <x-input-label for="member-name" value="Name" />
                <x-text-input id="member-name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="member-email" value="Email" />
                <x-text-input id="member-email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="member-company_role" value="Role" />
                <select id="member-company_role" name="company_role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                    @foreach($companyRoles as $companyRole)
                        <option value="{{ $companyRole->value }}" {{ old('company_role') === $companyRole->value ? 'selected' : '' }}>{{ $companyRole->label() }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('company_role')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="member-password" value="Password" />
                <x-text-input id="member-password" name="password" type="password" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="member-password-confirmation" value="Confirm Password" />
                <x-text-input id="member-password-confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <x-primary-button>Add Member</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Edit Member Role Modal --}}
    <div x-data="{ memberId: null, memberName: '', memberRole: '' }"
         x-on:open-edit-member.window="
             memberId = $event.detail.id;
             memberName = $event.detail.name;
             memberRole = $event.detail.company_role;
             $dispatch('open-modal', 'edit-member')
         ">

        <x-modal name="edit-member" focusable>
            <form :action="'{{ url('company/members') }}/' + memberId" method="POST" class="p-6">
                @csrf
                @method('PUT')

                <h2 class="text-lg font-medium text-gray-900">Edit Member Role</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Change the role for <span class="font-semibold" x-text="memberName"></span>.
                </p>

                <div class="mt-4">
                    <x-input-label for="edit-member-role" value="Role" />
                    <select id="edit-member-role" name="company_role" x-model="memberRole"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                        @foreach($companyRoles as $companyRole)
                            <option value="{{ $companyRole->value }}">{{ $companyRole->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('company_role')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                    <x-primary-button>Update Role</x-primary-button>
                </div>
            </form>
        </x-modal>
    </div>

    {{-- Remove Member Confirmation Modal --}}
    <div x-data="{ memberId: null, memberName: '' }"
         x-on:open-remove-member.window="
             memberId = $event.detail.id;
             memberName = $event.detail.name;
             $dispatch('open-modal', 'confirm-remove-member')
         ">

        <x-modal name="confirm-remove-member" focusable>
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Remove Member</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Are you sure you want to remove <span class="font-semibold" x-text="memberName"></span> from the company? They will lose access to all company projects.
                </p>

                <form :action="'{{ url('company/members') }}/' + memberId" method="POST" class="mt-6 flex justify-end gap-3">
                    @csrf
                    @method('DELETE')
                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                    <x-danger-button>Remove Member</x-danger-button>
                </form>
            </div>
        </x-modal>
    </div>

    {{-- Restore Member Confirmation Modal --}}
    <div x-data="{ memberId: null, memberName: '' }"
         x-on:open-restore-member.window="
             memberId = $event.detail.id;
             memberName = $event.detail.name;
             $dispatch('open-modal', 'confirm-restore-member')
         ">

        <x-modal name="confirm-restore-member" focusable>
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Restore Member</h2>
                <p class="mt-2 text-sm text-gray-600">
                    Are you sure you want to restore <span class="font-semibold" x-text="memberName"></span> to the company? They will regain access with their previous role.
                </p>

                <form :action="'{{ url('company/members') }}/' + memberId + '/restore'" method="POST" class="mt-6 flex justify-end gap-3">
                    @csrf
                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                    <x-primary-button>Restore Member</x-primary-button>
                </form>
            </div>
        </x-modal>
    </div>
</x-app-layout>
