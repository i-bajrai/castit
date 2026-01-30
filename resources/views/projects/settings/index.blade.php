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
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8" x-data="{ tab: 'codes' }">
            {{-- Tab Navigation --}}
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex gap-6">
                    <button @click="tab = 'codes'" :class="tab === 'codes' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition">
                        Project Codes
                    </button>
                    <button @click="tab = 'periods'" :class="tab === 'periods' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition">
                        Forecast Periods
                    </button>
                </nav>
            </div>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            {{-- ==================== PROJECT CODES TAB ==================== --}}
            <div x-show="tab === 'codes'" x-transition>
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
                                            <tr class="hover:bg-gray-50" x-data="{ editing: false }">
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
            </div>

            {{-- ==================== PERIODS TAB ==================== --}}
            <div x-show="tab === 'periods'" x-transition>
                {{-- Open New Period --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Open New Period</h3>
                        <form method="POST" action="{{ route('projects.periods.store', $project) }}" class="flex items-end gap-4">
                            @csrf
                            <div>
                                <x-input-label for="period_date" value="Period Date" />
                                <x-text-input id="period_date" name="period_date" type="month" class="mt-1 block" required />
                                <x-input-error :messages="$errors->get('period_date')" class="mt-2" />
                            </div>
                            <x-primary-button>Open Period</x-primary-button>
                        </form>
                        <p class="text-xs text-gray-500 mt-2">Opening a new period will automatically lock the current period.</p>
                    </div>
                </div>

                {{-- Periods List --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Forecast Periods</h3>
                        @if($periods->isEmpty())
                            <p class="text-gray-500 text-sm py-4">No forecast periods. Open your first one above.</p>
                        @else
                            <div class="space-y-3">
                                @foreach($periods as $period)
                                    <div class="border border-gray-200 rounded-lg p-4" x-data="{ expanded: false }">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <span class="text-sm font-medium text-gray-900">{{ $period->period_date->format('F Y') }}</span>
                                                @if($period->is_current)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Current</span>
                                                @endif
                                                @if($period->isLocked())
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        Locked {{ $period->locked_at->format('M j, Y') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-2">
                                                @if(!$period->isLocked() && !$period->is_current)
                                                    <form method="POST" action="{{ route('projects.periods.lock', [$project, $period]) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="text-sm text-amber-600 hover:text-amber-800 font-medium">Lock</button>
                                                    </form>
                                                @endif
                                                @if($period->isLocked())
                                                    <button
                                                        x-on:click.prevent="$dispatch('open-modal', 'add-adjustment-{{ $period->id }}')"
                                                        class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                                                    >Add Adjustment</button>
                                                    @if($period->budget_adjustments_count > 0)
                                                        <button @click="expanded = !expanded" class="text-sm text-gray-500 hover:text-gray-700" x-text="expanded ? 'Hide Adjustments' : 'Show Adjustments ({{ $period->budget_adjustments_count }})'"></button>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Adjustments List --}}
                                        @if($period->isLocked())
                                            <div x-show="expanded" x-transition class="mt-4 border-t pt-4">
                                                @php
                                                    $adjustments = $period->budgetAdjustments()->with(['controlAccount', 'user'])->latest()->get();
                                                @endphp
                                                @if($adjustments->isEmpty())
                                                    <p class="text-sm text-gray-500">No adjustments for this period.</p>
                                                @else
                                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                                        <thead>
                                                            <tr class="bg-gray-50">
                                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">By</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-gray-100">
                                                            @foreach($adjustments as $adjustment)
                                                                <tr>
                                                                    <td class="px-3 py-2 text-gray-600">{{ $adjustment->created_at->format('M j, Y') }}</td>
                                                                    <td class="px-3 py-2 font-medium text-gray-900">{{ $adjustment->controlAccount->code }}</td>
                                                                    <td class="px-3 py-2 text-right {{ $adjustment->amount < 0 ? 'text-red-600' : 'text-green-600' }}">
                                                                        ${{ number_format($adjustment->amount, 0) }}
                                                                    </td>
                                                                    <td class="px-3 py-2 text-gray-600">{{ $adjustment->reason }}</td>
                                                                    <td class="px-3 py-2 text-gray-500">{{ $adjustment->user->name }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Adjustment Modal for this period --}}
                                    @if($period->isLocked())
                                        <x-modal name="add-adjustment-{{ $period->id }}" :show="false" maxWidth="lg">
                                            <form method="POST" action="{{ route('projects.budget-adjustments.store', $project) }}" class="p-6">
                                                @csrf
                                                <input type="hidden" name="forecast_period_id" value="{{ $period->id }}">

                                                <h2 class="text-lg font-medium text-gray-900 mb-4">
                                                    Budget Adjustment - {{ $period->period_date->format('F Y') }}
                                                </h2>

                                                <div class="space-y-4">
                                                    <div>
                                                        <x-input-label for="control_account_id_{{ $period->id }}" value="Control Account" />
                                                        <select name="control_account_id" id="control_account_id_{{ $period->id }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                                            <option value="">Select account...</option>
                                                            @foreach($controlAccounts as $account)
                                                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->description }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <x-input-label for="amount_{{ $period->id }}" value="Adjustment Amount" />
                                                        <x-text-input id="amount_{{ $period->id }}" name="amount" type="number" step="0.01" class="mt-1 block w-full" placeholder="Positive to increase, negative to decrease" required />
                                                    </div>

                                                    <div>
                                                        <x-input-label for="reason_{{ $period->id }}" value="Reason" />
                                                        <textarea name="reason" id="reason_{{ $period->id }}" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Explain why this adjustment is needed..." required></textarea>
                                                    </div>
                                                </div>

                                                <div class="mt-6 flex justify-end gap-3">
                                                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                                    <x-primary-button>Record Adjustment</x-primary-button>
                                                </div>
                                            </form>
                                        </x-modal>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
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
