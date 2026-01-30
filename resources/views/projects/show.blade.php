@php $packages = $accounts->flatMap->costPackages; @endphp
<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="cost-detail" :subtitle="$period ? 'Forecast Period: ' . $period->period_date->format('F Y') : null">
            {{ $project->name }}
        </x-project-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            {{-- ==================== PERIOD NAVIGATION ==================== --}}
            @if($allPeriods->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="px-6 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <label for="period-selector" class="text-sm font-medium text-gray-700">Period:</label>
                            <select id="period-selector" onchange="window.location.href = this.value" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($allPeriods as $p)
                                    <option value="{{ route('projects.show', ['project' => $project, 'period' => $p->id]) }}"
                                        {{ $period && $period->id === $p->id ? 'selected' : '' }}>
                                        {{ $p->period_date->format('F Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            @if($isEditable)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Current Month &mdash; Editable
                                </span>
                            @elseif($period)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ $period->period_date->format('M Y') }} &mdash; Read Only
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif(!$project->start_date || !$project->end_date)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-12 text-center">
                    <p class="text-gray-500">No forecast periods yet. <a href="{{ route('projects.settings', $project) }}" class="text-indigo-600 hover:underline">Set project start and end dates in Settings</a> to auto-generate periods.</p>
                </div>
            @endif

            {{-- ==================== PROJECT SUMMARY CARDS ==================== --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Project Summary</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-xs font-medium text-gray-500 uppercase">Original Budget</p>
                            <p class="mt-1 text-lg font-bold text-gray-900">${{ number_format($totals['original_budget'], 2) }}</p>
                        </div>
                        <div class="bg-blue-50 rounded-lg p-4">
                            <p class="text-xs font-medium text-blue-600 uppercase">Previous FCAC</p>
                            <p class="mt-1 text-lg font-bold text-gray-900">${{ number_format($totals['previous_fcac'], 2) }}</p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <p class="text-xs font-medium text-green-600 uppercase">Cost to Date</p>
                            <p class="mt-1 text-lg font-bold text-gray-900">${{ number_format($totals['ctd'], 2) }}</p>
                        </div>
                        <div class="bg-amber-50 rounded-lg p-4">
                            <p class="text-xs font-medium text-amber-600 uppercase">Cost to Complete</p>
                            <p class="mt-1 text-lg font-bold text-gray-900">${{ number_format($totals['ctc'], 2) }}</p>
                        </div>
                        <div class="bg-indigo-50 rounded-lg p-4">
                            <p class="text-xs font-medium text-indigo-600 uppercase">FCAC</p>
                            <p class="mt-1 text-lg font-bold text-gray-900">${{ number_format($totals['fcac'], 2) }}</p>
                        </div>
                        <div class="rounded-lg p-4 {{ $totals['variance'] < 0 ? 'bg-red-50' : 'bg-green-50' }}">
                            <p class="text-xs font-medium {{ $totals['variance'] < 0 ? 'text-red-600' : 'text-green-600' }} uppercase">Variance</p>
                            <p class="mt-1 text-lg font-bold {{ $totals['variance'] < 0 ? 'text-red-700' : 'text-green-700' }}">
                                ${{ number_format($totals['variance'], 2) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ==================== COST PACKAGES & LINE ITEMS ==================== --}}
            @if($isEditable)
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Cost Packages</h3>
                    <button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-cost-package')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-indigo-700 transition">
                        Add Cost Package
                    </button>
                </div>
            @endif

            @foreach($packages as $package)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6" x-data="{ open: true }">
                    <div class="border-b border-gray-200">
                        <div class="w-full px-6 py-4 flex items-center justify-between">
                            <button @click="open = !open" class="flex items-center gap-3 hover:text-gray-600 transition">
                                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                <div class="text-left">
                                    <span class="font-semibold text-gray-900">{{ $package->name }}</span>
                                    @if($package->item_no)
                                        <span class="ml-2 text-sm text-gray-500">({{ $package->item_no }})</span>
                                    @endif
                                </div>
                            </button>
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-gray-500">{{ $package->lineItems->count() }} items</span>
                                @if($isEditable)
                                    <button x-on:click.prevent="$dispatch('open-modal', 'add-line-item-{{ $package->id }}')" class="text-sm text-green-600 hover:text-green-800 font-medium">Add Item</button>
                                    <button x-on:click.prevent="$dispatch('open-modal', 'edit-cost-package-{{ $package->id }}')" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                                    <button x-on:click.prevent="$dispatch('open-modal', 'delete-cost-package-{{ $package->id }}')" class="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div x-show="open" x-transition>
                        @if($package->lineItems->isEmpty())
                            <div class="p-6 text-center text-gray-500 text-sm">No line items in this package.
                                @if($isEditable)
                                    <button x-on:click.prevent="$dispatch('open-modal', 'add-line-item-{{ $package->id }}')" class="text-indigo-600 hover:underline">Add one</button>.
                                @endif
                            </div>
                        @else
                            @if($isEditable)
                                {{-- EDITABLE MODE: Inline form with inputs --}}
                                <form method="POST" action="{{ route('projects.data-entry.line-items.store', $project) }}">
                                    @csrf
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-16">Item</th>
                                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase w-14">UoM</th>
                                                    <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-24 bg-gray-100">Original</th>
                                                    <th class="px-3 py-3 text-right text-xs font-medium text-blue-600 uppercase w-24 bg-blue-50">Prev FCAC</th>
                                                    <th class="px-3 py-3 text-right text-xs font-medium text-green-600 uppercase w-24 bg-green-50">CTD Qty</th>
                                                    <th class="px-3 py-3 text-right text-xs font-medium text-green-600 uppercase w-24 bg-green-50">CTD Rate</th>
                                                    <th class="px-3 py-3 text-right text-xs font-medium text-green-600 uppercase w-28 bg-green-50">CTD Amount</th>
                                                    <th class="px-3 py-3 text-right text-xs font-medium text-amber-600 uppercase w-24 bg-amber-50">CTC Qty</th>
                                                    <th class="px-3 py-3 text-right text-xs font-medium text-amber-600 uppercase w-24 bg-amber-50">CTC Rate</th>
                                                    <th class="px-3 py-3 text-right text-xs font-medium text-amber-600 uppercase w-28 bg-amber-50">CTC Amount</th>
                                                    <th class="px-3 py-3 text-right text-xs font-medium text-indigo-600 uppercase w-28 bg-indigo-50">FCAC</th>
                                                    <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Variance</th>
                                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-40">Comments</th>
                                                    <th class="px-3 py-3 w-16"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach($package->lineItems as $index => $item)
                                                    @php
                                                        $forecast = $item->forecasts->first();
                                                        $prefix = "forecasts[{$index}]";
                                                    @endphp
                                                    <tr class="hover:bg-gray-50"
                                                        x-data="{
                                                            ctdQty: {{ $forecast->ctd_qty ?? 0 }},
                                                            ctdRate: {{ $forecast->ctd_rate ?? 0 }},
                                                            ctdAmount: {{ $forecast->ctd_amount ?? 0 }},
                                                            ctcQty: {{ $forecast->ctc_qty ?? 0 }},
                                                            ctcRate: {{ $forecast->ctc_rate ?? 0 }},
                                                            ctcAmount: {{ $forecast->ctc_amount ?? 0 }},
                                                            previousAmount: {{ $forecast->previous_amount ?? 0 }},
                                                            get fcac() { return this.ctdAmount + this.ctcAmount },
                                                            get variance() { return this.previousAmount - this.fcac },
                                                            calcCtd() { this.ctdAmount = Math.round(this.ctdQty * this.ctdRate * 100) / 100 },
                                                            calcCtc() { this.ctcAmount = Math.round(this.ctcQty * this.ctcRate * 100) / 100 },
                                                        }">
                                                        <input type="hidden" name="{{ $prefix }}[line_item_id]" value="{{ $item->id }}">
                                                        <td class="px-3 py-2 text-sm text-gray-600">{{ $item->item_no }}</td>
                                                        <td class="px-3 py-2 text-sm text-gray-900">{{ $item->description }}</td>
                                                        <td class="px-3 py-2 text-sm text-gray-500 text-center">{{ $item->unit_of_measure }}</td>
                                                        <td class="px-3 py-2 text-sm text-gray-900 text-right bg-gray-50">${{ number_format($item->original_amount, 2) }}</td>
                                                        <td class="px-3 py-2 text-sm text-gray-900 text-right bg-blue-50/50">${{ number_format($forecast->previous_amount ?? 0, 2) }}</td>

                                                        <td class="px-1 py-1 bg-green-50/30">
                                                            <input type="number" step="0.01" name="{{ $prefix }}[ctd_qty]"
                                                                x-model.number="ctdQty" @input="calcCtd()"
                                                                class="w-full text-sm text-right border-gray-300 rounded px-2 py-1 focus:border-green-500 focus:ring-green-500">
                                                        </td>
                                                        <td class="px-1 py-1 bg-green-50/30">
                                                            <input type="number" step="0.01" name="{{ $prefix }}[ctd_rate]"
                                                                x-model.number="ctdRate" @input="calcCtd()"
                                                                class="w-full text-sm text-right border-gray-300 rounded px-2 py-1 focus:border-green-500 focus:ring-green-500">
                                                        </td>
                                                        <td class="px-1 py-1 bg-green-50/30">
                                                            <input type="number" step="0.01" name="{{ $prefix }}[ctd_amount]"
                                                                x-model.number="ctdAmount"
                                                                class="w-full text-sm text-right border-gray-300 rounded px-2 py-1 focus:border-green-500 focus:ring-green-500">
                                                        </td>

                                                        <td class="px-1 py-1 bg-amber-50/30">
                                                            <input type="number" step="0.01" name="{{ $prefix }}[ctc_qty]"
                                                                x-model.number="ctcQty" @input="calcCtc()"
                                                                class="w-full text-sm text-right border-gray-300 rounded px-2 py-1 focus:border-amber-500 focus:ring-amber-500">
                                                        </td>
                                                        <td class="px-1 py-1 bg-amber-50/30">
                                                            <input type="number" step="0.01" name="{{ $prefix }}[ctc_rate]"
                                                                x-model.number="ctcRate" @input="calcCtc()"
                                                                class="w-full text-sm text-right border-gray-300 rounded px-2 py-1 focus:border-amber-500 focus:ring-amber-500">
                                                        </td>
                                                        <td class="px-1 py-1 bg-amber-50/30">
                                                            <input type="number" step="0.01" name="{{ $prefix }}[ctc_amount]"
                                                                x-model.number="ctcAmount"
                                                                class="w-full text-sm text-right border-gray-300 rounded px-2 py-1 focus:border-amber-500 focus:ring-amber-500">
                                                        </td>

                                                        <td class="px-3 py-2 text-sm font-medium text-gray-900 text-right bg-indigo-50/50" x-text="'$' + fcac.toFixed(2)"></td>
                                                        <td class="px-3 py-2 text-sm text-right" :class="variance < 0 ? 'text-red-600 font-medium' : 'text-gray-900'" x-text="variance !== 0 ? '$' + variance.toFixed(2) : '-'"></td>

                                                        <td class="px-1 py-1">
                                                            <input type="text" name="{{ $prefix }}[comments]"
                                                                value="{{ $forecast->comments ?? '' }}"
                                                                class="w-full text-sm border-gray-300 rounded px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500"
                                                                placeholder="Notes...">
                                                        </td>
                                                        <td class="px-2 py-2">
                                                            <div class="flex gap-1">
                                                                <button type="button" x-on:click.prevent="$dispatch('open-modal', 'edit-line-item-{{ $item->id }}')" class="text-indigo-500 hover:text-indigo-700" title="Edit">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                                </button>
                                                                <button type="button" x-on:click.prevent="$dispatch('open-modal', 'delete-line-item-{{ $item->id }}')" class="text-red-500 hover:text-red-700" title="Delete">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="p-4 bg-gray-50 border-t flex justify-end">
                                        <x-primary-button>Save {{ $package->name }}</x-primary-button>
                                    </div>
                                </form>
                            @else
                                {{-- READ-ONLY MODE --}}
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-16">Item</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-16">UoM</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-20">Qty</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-24">Rate</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28 bg-gray-100">Original Budget</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-blue-600 uppercase w-28 bg-blue-50">Prev FCAC</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-green-600 uppercase w-28 bg-green-50">CTD</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-amber-600 uppercase w-28 bg-amber-50">CTC</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-indigo-600 uppercase w-28 bg-indigo-50">FCAC</th>
                                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Variance</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comments</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @php
                                                $pkgOriginal = 0;
                                                $pkgPrevious = 0;
                                                $pkgCtd = 0;
                                                $pkgCtc = 0;
                                                $pkgFcac = 0;
                                                $pkgVariance = 0;
                                            @endphp
                                            @foreach($package->lineItems as $item)
                                                @php
                                                    $forecast = $item->forecasts->first();
                                                    $pkgOriginal += $item->original_amount;
                                                    if ($forecast) {
                                                        $pkgPrevious += $forecast->previous_amount;
                                                        $pkgCtd += $forecast->ctd_amount;
                                                        $pkgCtc += $forecast->ctc_amount;
                                                        $pkgFcac += $forecast->fcac_amount;
                                                        $pkgVariance += $forecast->variance;
                                                    }
                                                @endphp
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-2 text-sm text-gray-600">{{ $item->item_no }}</td>
                                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $item->description }}</td>
                                                    <td class="px-4 py-2 text-sm text-gray-500 text-center">{{ $item->unit_of_measure }}</td>
                                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ number_format($item->original_qty, 1) }}</td>
                                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">${{ number_format($item->original_rate, 2) }}</td>
                                                    <td class="px-4 py-2 text-sm font-medium text-gray-900 text-right bg-gray-50">${{ number_format($item->original_amount, 2) }}</td>
                                                    @if($forecast)
                                                        <td class="px-4 py-2 text-sm text-gray-900 text-right bg-blue-50/50">${{ number_format($forecast->previous_amount, 2) }}</td>
                                                        <td class="px-4 py-2 text-sm text-gray-900 text-right bg-green-50/50">${{ number_format($forecast->ctd_amount, 2) }}</td>
                                                        <td class="px-4 py-2 text-sm text-gray-900 text-right bg-amber-50/50">${{ number_format($forecast->ctc_amount, 2) }}</td>
                                                        <td class="px-4 py-2 text-sm font-medium text-gray-900 text-right bg-indigo-50/50">${{ number_format($forecast->fcac_amount, 2) }}</td>
                                                        <td class="px-4 py-2 text-sm text-right {{ $forecast->variance < 0 ? 'text-red-600 font-medium' : 'text-gray-900' }}">
                                                            @if($forecast->variance != 0)
                                                                ${{ number_format($forecast->variance, 2) }}
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2 text-sm text-gray-500 max-w-xs truncate">{{ $forecast->comments }}</td>
                                                    @else
                                                        <td colspan="6" class="px-4 py-2 text-sm text-gray-400 text-center">No forecast data</td>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-gray-100">
                                            <tr class="font-semibold">
                                                <td colspan="5" class="px-4 py-3 text-sm text-gray-700 text-right">Package Total</td>
                                                <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($pkgOriginal, 2) }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($pkgPrevious, 2) }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($pkgCtd, 2) }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($pkgCtc, 2) }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($pkgFcac, 2) }}</td>
                                                <td class="px-4 py-3 text-sm text-right {{ $pkgVariance < 0 ? 'text-red-600' : 'text-gray-900' }}">${{ number_format($pkgVariance, 2) }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- ==================== CONTROL ACCOUNTS SECTION ==================== --}}
            @if($accounts->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6" x-data="{ open: true }">
                    <div class="border-b border-gray-200">
                        <button @click="open = !open" class="w-full px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                <span class="font-semibold text-gray-900">Control Accounts</span>
                            </div>
                            <span class="text-sm text-gray-500">{{ $accounts->count() }} accounts</span>
                        </button>
                    </div>

                    <div x-show="open" x-transition>
                        @if($isEditable)
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

                                                    <td class="px-3 py-2 text-sm font-medium text-gray-900 text-right bg-indigo-50/50" x-text="'$' + efc.toFixed(0)"></td>
                                                    <td class="px-3 py-2 text-sm text-right"
                                                        :class="efcMovement > 0 ? 'text-red-600 font-medium' : efcMovement < 0 ? 'text-green-600 font-medium' : 'text-gray-900'"
                                                        x-text="efcMovement !== 0 ? '$' + efcMovement.toFixed(0) : '-'"></td>

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
                        @else
                            {{-- READ-ONLY CA view --}}
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
                                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($accounts as $account)
                                            @php $forecast = $account->forecasts->first(); @endphp
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 py-2 text-sm font-medium text-gray-900">{{ $account->code }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-900">{{ $account->description }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-900 text-right bg-gray-50">${{ number_format($account->approved_budget, 0) }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-900 text-right bg-blue-50/50">${{ number_format($forecast->last_month_efc ?? 0, 0) }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-900 text-right bg-green-50/50">${{ number_format($forecast->monthly_cost ?? 0, 0) }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-900 text-right bg-green-50/50">${{ number_format($forecast->cost_to_date ?? 0, 0) }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-900 text-right bg-amber-50/50">${{ number_format($forecast->estimate_to_complete ?? 0, 0) }}</td>
                                                <td class="px-3 py-2 text-sm font-medium text-gray-900 text-right bg-indigo-50/50">${{ number_format($forecast->estimated_final_cost ?? 0, 0) }}</td>
                                                <td class="px-3 py-2 text-sm text-right {{ ($forecast->efc_movement ?? 0) > 0 ? 'text-red-600 font-medium' : (($forecast->efc_movement ?? 0) < 0 ? 'text-green-600 font-medium' : 'text-gray-900') }}">
                                                    @if(($forecast->efc_movement ?? 0) != 0)
                                                        ${{ number_format($forecast->efc_movement, 0) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-sm text-gray-500">{{ $forecast->monthly_comments ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- ==================== MODALS (only when editable) ==================== --}}
            @if($isEditable)
                {{-- Create Cost Package Modal --}}
                <x-modal name="create-cost-package" :show="false" maxWidth="lg">
                    <form method="POST" action="{{ route('projects.cost-packages.store', $project) }}" class="p-6">
                        @csrf
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Add Cost Package</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="create_pkg_item_no" value="Item No" />
                                <x-text-input id="create_pkg_item_no" name="item_no" type="text" class="mt-1 block w-full" />
                            </div>
                            <div>
                                <x-input-label for="create_pkg_sort_order" value="Sort Order" />
                                <x-text-input id="create_pkg_sort_order" name="sort_order" type="number" class="mt-1 block w-full" value="0" required />
                            </div>
                            <div class="col-span-2">
                                <x-input-label for="create_pkg_name" value="Name" />
                                <x-text-input id="create_pkg_name" name="name" type="text" class="mt-1 block w-full" required />
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end gap-3">
                            <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                            <x-primary-button>Create</x-primary-button>
                        </div>
                    </form>
                </x-modal>

                {{-- Edit / Delete Cost Package + Line Item Modals --}}
                @foreach($packages as $package)
                    <x-modal name="edit-cost-package-{{ $package->id }}" :show="false" maxWidth="lg">
                        <form method="POST" action="{{ route('projects.cost-packages.update', [$project, $package]) }}" class="p-6">
                            @csrf
                            @method('PUT')
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Edit Cost Package - {{ $package->name }}</h2>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="edit_pkg_item_no_{{ $package->id }}" value="Item No" />
                                    <x-text-input id="edit_pkg_item_no_{{ $package->id }}" name="item_no" type="text" class="mt-1 block w-full" :value="$package->item_no" />
                                </div>
                                <div>
                                    <x-input-label for="edit_pkg_sort_order_{{ $package->id }}" value="Sort Order" />
                                    <x-text-input id="edit_pkg_sort_order_{{ $package->id }}" name="sort_order" type="number" class="mt-1 block w-full" :value="$package->sort_order" required />
                                </div>
                                <div class="col-span-2">
                                    <x-input-label for="edit_pkg_name_{{ $package->id }}" value="Name" />
                                    <x-text-input id="edit_pkg_name_{{ $package->id }}" name="name" type="text" class="mt-1 block w-full" :value="$package->name" required />
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3">
                                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                <x-primary-button>Save Changes</x-primary-button>
                            </div>
                        </form>
                    </x-modal>

                    <x-modal name="delete-cost-package-{{ $package->id }}" :show="false">
                        <form method="POST" action="{{ route('projects.cost-packages.destroy', [$project, $package]) }}" class="p-6">
                            @csrf
                            @method('DELETE')
                            <h2 class="text-lg font-medium text-gray-900">Delete Cost Package</h2>
                            <p class="mt-2 text-sm text-gray-600">Are you sure you want to delete <strong>{{ $package->name }}</strong>? This will also delete all line items and their forecasts.</p>
                            <div class="mt-6 flex justify-end gap-3">
                                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                <x-danger-button>Delete</x-danger-button>
                            </div>
                        </form>
                    </x-modal>

                    <x-modal name="add-line-item-{{ $package->id }}" :show="false" maxWidth="lg">
                        <form method="POST" action="{{ route('projects.line-items.store', [$project, $package]) }}" class="p-6">
                            @csrf
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Add Line Item to {{ $package->name }}</h2>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="create_li_item_no_{{ $package->id }}" value="Item No" />
                                    <x-text-input id="create_li_item_no_{{ $package->id }}" name="item_no" type="text" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <x-input-label for="create_li_sort_order_{{ $package->id }}" value="Sort Order" />
                                    <x-text-input id="create_li_sort_order_{{ $package->id }}" name="sort_order" type="number" class="mt-1 block w-full" value="0" required />
                                </div>
                                <div class="col-span-2">
                                    <x-input-label for="create_li_description_{{ $package->id }}" value="Description" />
                                    <x-text-input id="create_li_description_{{ $package->id }}" name="description" type="text" class="mt-1 block w-full" required />
                                </div>
                                <div>
                                    <x-input-label for="create_li_uom_{{ $package->id }}" value="Unit of Measure" />
                                    <x-text-input id="create_li_uom_{{ $package->id }}" name="unit_of_measure" type="text" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <x-input-label for="create_li_qty_{{ $package->id }}" value="Original Qty" />
                                    <x-text-input id="create_li_qty_{{ $package->id }}" name="original_qty" type="number" step="0.01" class="mt-1 block w-full" value="0" required />
                                </div>
                                <div>
                                    <x-input-label for="create_li_rate_{{ $package->id }}" value="Original Rate" />
                                    <x-text-input id="create_li_rate_{{ $package->id }}" name="original_rate" type="number" step="0.01" class="mt-1 block w-full" value="0" required />
                                </div>
                                <div>
                                    <x-input-label for="create_li_amount_{{ $package->id }}" value="Original Amount" />
                                    <x-text-input id="create_li_amount_{{ $package->id }}" name="original_amount" type="number" step="0.01" class="mt-1 block w-full" value="0" required />
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3">
                                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                <x-primary-button>Create</x-primary-button>
                            </div>
                        </form>
                    </x-modal>

                    @foreach($package->lineItems as $item)
                        <x-modal name="edit-line-item-{{ $item->id }}" :show="false" maxWidth="lg">
                            <form method="POST" action="{{ route('projects.line-items.update', [$project, $package, $item]) }}" class="p-6">
                                @csrf
                                @method('PUT')
                                <h2 class="text-lg font-medium text-gray-900 mb-4">Edit Line Item - {{ $item->description }}</h2>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="edit_li_item_no_{{ $item->id }}" value="Item No" />
                                        <x-text-input id="edit_li_item_no_{{ $item->id }}" name="item_no" type="text" class="mt-1 block w-full" :value="$item->item_no" />
                                    </div>
                                    <div>
                                        <x-input-label for="edit_li_sort_order_{{ $item->id }}" value="Sort Order" />
                                        <x-text-input id="edit_li_sort_order_{{ $item->id }}" name="sort_order" type="number" class="mt-1 block w-full" :value="$item->sort_order" required />
                                    </div>
                                    <div class="col-span-2">
                                        <x-input-label for="edit_li_description_{{ $item->id }}" value="Description" />
                                        <x-text-input id="edit_li_description_{{ $item->id }}" name="description" type="text" class="mt-1 block w-full" :value="$item->description" required />
                                    </div>
                                    <div>
                                        <x-input-label for="edit_li_uom_{{ $item->id }}" value="Unit of Measure" />
                                        <x-text-input id="edit_li_uom_{{ $item->id }}" name="unit_of_measure" type="text" class="mt-1 block w-full" :value="$item->unit_of_measure" />
                                    </div>
                                    <div>
                                        <x-input-label for="edit_li_qty_{{ $item->id }}" value="Original Qty" />
                                        <x-text-input id="edit_li_qty_{{ $item->id }}" name="original_qty" type="number" step="0.01" class="mt-1 block w-full" :value="$item->original_qty" required />
                                    </div>
                                    <div>
                                        <x-input-label for="edit_li_rate_{{ $item->id }}" value="Original Rate" />
                                        <x-text-input id="edit_li_rate_{{ $item->id }}" name="original_rate" type="number" step="0.01" class="mt-1 block w-full" :value="$item->original_rate" required />
                                    </div>
                                    <div>
                                        <x-input-label for="edit_li_amount_{{ $item->id }}" value="Original Amount" />
                                        <x-text-input id="edit_li_amount_{{ $item->id }}" name="original_amount" type="number" step="0.01" class="mt-1 block w-full" :value="$item->original_amount" required />
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end gap-3">
                                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                    <x-primary-button>Save Changes</x-primary-button>
                                </div>
                            </form>
                        </x-modal>

                        <x-modal name="delete-line-item-{{ $item->id }}" :show="false">
                            <form method="POST" action="{{ route('projects.line-items.destroy', [$project, $package, $item]) }}" class="p-6">
                                @csrf
                                @method('DELETE')
                                <h2 class="text-lg font-medium text-gray-900">Delete Line Item</h2>
                                <p class="mt-2 text-sm text-gray-600">Are you sure you want to delete <strong>{{ $item->description }}</strong>? This will also delete all associated forecasts.</p>
                                <div class="mt-6 flex justify-end gap-3">
                                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                    <x-danger-button>Delete</x-danger-button>
                                </div>
                            </form>
                        </x-modal>
                    @endforeach
                @endforeach
            @endif
        </div>
    </div>
</x-app-layout>
