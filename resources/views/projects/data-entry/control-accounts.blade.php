<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Data Entry - {{ $project->name }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Forecast Period: {{ $period->period_date->format('F Y') }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('projects.data-entry.line-items', $project) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-indigo-700 transition">
                    Line Items
                </a>
                <a href="{{ route('projects.executive-summary', $project) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Executive Summary
                </a>
                <a href="{{ route('projects.settings', $project) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Settings
                </a>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Back to Projects
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Sub Navigation --}}
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex gap-6">
                    <a href="{{ route('projects.data-entry.line-items', $project) }}" class="whitespace-nowrap py-3 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm transition">
                        Line Items
                    </a>
                    <span class="whitespace-nowrap py-3 px-1 border-b-2 border-indigo-500 text-indigo-600 font-medium text-sm">
                        Control Accounts
                    </span>
                </nav>
            </div>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            @if($accounts->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                    <p class="text-gray-500">No control accounts configured. <a href="{{ route('projects.settings', $project) }}" class="text-indigo-600 hover:underline">Add them in Settings</a>.</p>
                </div>
            @else
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form method="POST" action="{{ route('projects.data-entry.control-accounts.store', $project) }}">
                        @csrf
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28 bg-gray-100">Approved Budget</th>
                                        <th class="px-3 py-3 text-right text-xs font-medium text-blue-600 uppercase w-28 bg-blue-50">Last Month EFC</th>
                                        <th class="px-3 py-3 text-right text-xs font-medium text-green-600 uppercase w-28 bg-green-50">Monthly Cost</th>
                                        <th class="px-3 py-3 text-right text-xs font-medium text-green-600 uppercase w-28 bg-green-50">Cost to Date</th>
                                        <th class="px-3 py-3 text-right text-xs font-medium text-amber-600 uppercase w-28 bg-amber-50">Est. to Complete</th>
                                        <th class="px-3 py-3 text-right text-xs font-medium text-indigo-600 uppercase w-28 bg-indigo-50">EFC</th>
                                        <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">EFC Movement</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-48">Comments</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($accounts as $index => $account)
                                        @php
                                            $forecast = $account->forecasts->first();
                                            $prefix = "forecasts[{$index}]";
                                        @endphp
                                        <tr class="hover:bg-gray-50"
                                            x-data="{
                                                monthlyCost: {{ $forecast->monthly_cost ?? 0 }},
                                                costToDate: {{ $forecast->cost_to_date ?? 0 }},
                                                estimateToComplete: {{ $forecast->estimate_to_complete ?? 0 }},
                                                lastMonthEfc: {{ $forecast->last_month_efc ?? 0 }},
                                                get efc() { return this.costToDate + this.estimateToComplete },
                                                get efcMovement() { return this.efc - this.lastMonthEfc },
                                            }">
                                            <input type="hidden" name="{{ $prefix }}[control_account_id]" value="{{ $account->id }}">

                                            <td class="px-3 py-2 text-sm font-medium text-gray-900">{{ $account->code }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900">{{ $account->description }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900 text-right bg-gray-50">${{ number_format($account->approved_budget, 0) }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-900 text-right bg-blue-50/50">${{ number_format($forecast->last_month_efc ?? 0, 0) }}</td>

                                            {{-- Editable inputs --}}
                                            <td class="px-1 py-1 bg-green-50/30">
                                                <input type="number" step="0.01" name="{{ $prefix }}[monthly_cost]"
                                                    x-model.number="monthlyCost"
                                                    class="w-full text-sm text-right border-gray-300 rounded px-2 py-1 focus:border-green-500 focus:ring-green-500">
                                            </td>
                                            <td class="px-1 py-1 bg-green-50/30">
                                                <input type="number" step="0.01" name="{{ $prefix }}[cost_to_date]"
                                                    x-model.number="costToDate"
                                                    class="w-full text-sm text-right border-gray-300 rounded px-2 py-1 focus:border-green-500 focus:ring-green-500">
                                            </td>
                                            <td class="px-1 py-1 bg-amber-50/30">
                                                <input type="number" step="0.01" name="{{ $prefix }}[estimate_to_complete]"
                                                    x-model.number="estimateToComplete"
                                                    class="w-full text-sm text-right border-gray-300 rounded px-2 py-1 focus:border-amber-500 focus:ring-amber-500">
                                            </td>

                                            {{-- Calculated --}}
                                            <td class="px-3 py-2 text-sm font-medium text-gray-900 text-right bg-indigo-50/50" x-text="'$' + efc.toFixed(0)"></td>
                                            <td class="px-3 py-2 text-sm text-right"
                                                :class="efcMovement > 0 ? 'text-red-600 font-medium' : efcMovement < 0 ? 'text-green-600 font-medium' : 'text-gray-900'"
                                                x-text="efcMovement !== 0 ? '$' + efcMovement.toFixed(0) : '-'"></td>

                                            {{-- Comments --}}
                                            <td class="px-1 py-1">
                                                <input type="text" name="{{ $prefix }}[monthly_comments]"
                                                    value="{{ $forecast->monthly_comments ?? '' }}"
                                                    class="w-full text-sm border-gray-300 rounded px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500"
                                                    placeholder="Notes...">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="p-4 bg-gray-50 border-t flex justify-end">
                            <x-primary-button>Save Control Account Forecasts</x-primary-button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
