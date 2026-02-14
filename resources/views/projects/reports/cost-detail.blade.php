<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="reports" :breadcrumbs="[
            ['label' => 'Reports', 'route' => route('projects.reports', $project)],
        ]">
            Cost Detail - {{ $project->name }}
        </x-project-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- ==================== PERIOD NAVIGATION ==================== --}}
            @if($allPeriods->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="px-6 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <label for="period-selector" class="text-sm font-medium text-gray-700">Period:</label>
                            <select id="period-selector" onchange="window.location.href = this.value" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($allPeriods as $p)
                                    <option value="{{ route('projects.cost-detail-report', ['project' => $project, 'period' => $p->id]) }}"
                                        {{ $period && $period->id === $p->id ? 'selected' : '' }}>
                                        {{ $p->period_date->format('F Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                            @if($period)
                                {{ $period->period_date->format('M Y') }} &mdash; Read Only
                            @else
                                Read Only
                            @endif
                        </span>
                    </div>
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

            {{-- ==================== COLUMN FILTER ==================== --}}
            <div class="mb-4 flex justify-end" x-data="{ filterOpen: false }">
                <div class="relative">
                    <button @click="filterOpen = !filterOpen" type="button" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Columns
                    </button>
                    <div x-show="filterOpen" @click.away="filterOpen = false" x-transition x-cloak
                        class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50 py-2">
                        <div class="px-3 py-2 border-b border-gray-100">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Toggle Columns</p>
                        </div>
                        <div class="py-1">
                            @foreach([
                                'orig_qty' => 'Orig Qty',
                                'orig_rate' => 'Orig Rate',
                                'orig_amount' => 'Orig Amount',
                                'prev_qty' => 'Prev Qty',
                                'prev_rate' => 'Prev Rate',
                                'prev_fcac' => 'Prev FCAC',
                                'ctd_qty' => 'CTD Qty',
                                'ctd_rate' => 'CTD Rate',
                                'ctd_amount' => 'CTD Amount',
                                'ctc_qty' => 'CTC Qty',
                                'ctc_rate' => 'CTC Rate',
                                'ctc_amount' => 'CTC Amount',
                                'fcac_qty' => 'FCAC Qty',
                                'fcac_rate' => 'FCAC Rate',
                                'fcac' => 'FCAC',
                                'variance' => 'Variance',
                                'comments' => 'Comments',
                            ] as $key => $label)
                                <label class="flex items-center px-3 py-1.5 hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" :checked="$store.columns.{{ $key }}" @change="$store.columns.toggle('{{ $key }}')" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- ==================== CONTROL ACCOUNTS & COST PACKAGES ==================== --}}
            @if($accounts->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-12 text-center">
                    <p class="text-gray-500">No control accounts.</p>
                </div>
            @endif

            @foreach($accounts as $account)
                @php
                    $caOriginal = 0;
                    $caPrevious = 0;
                    $caCtd = 0;
                    $caCtc = 0;
                    $caFcac = 0;
                    $caVariance = 0;
                    $caItemCount = 0;
                    foreach ($account->costPackages as $pkg) {
                        foreach ($pkg->lineItems as $li) {
                            if ($period && ! $li->existedInPeriod($period)) {
                                continue;
                            }
                            $caOriginal += $li->original_amount;
                            $caItemCount++;
                            $f = $li->forecasts->first();
                            if ($f) {
                                $caPrevious += $f->previous_amount ?? 0;
                                $caCtd += $f->ctd_amount ?? 0;
                                $caCtc += $f->ctc_amount ?? 0;
                                $caFcac += $f->fcac_amount ?? 0;
                                $caVariance += $f->variance ?? 0;
                            }
                        }
                    }
                @endphp

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6" x-data="{ open: false }">
                    {{-- CA Summary Row --}}
                    <div class="border-b border-gray-200">
                        <div class="w-full px-6 py-4">
                            <div class="flex items-center justify-between">
                                <button @click="open = !open" class="flex items-center gap-3 hover:text-gray-600 transition">
                                    <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                    <div class="text-left">
                                        <span class="font-semibold text-gray-900">{{ $account->code }}</span>
                                        <span class="ml-2 text-sm text-gray-600">{{ $account->description }}</span>
                                    </div>
                                </button>
                                <span class="text-sm {{ $caItemCount > 0 ? 'text-emerald-600 font-semibold' : 'text-gray-400' }}">{{ $caItemCount }} items</span>
                            </div>
                            {{-- CA Aggregated Totals --}}
                            <div class="mt-3 grid grid-cols-3 md:grid-cols-6 gap-3 text-xs">
                                <div class="bg-gray-50 rounded px-3 py-2">
                                    <span class="text-gray-500 uppercase font-medium">Orig Budget</span>
                                    <p class="font-bold text-gray-900">${{ number_format($caOriginal, 2) }}</p>
                                </div>
                                <div class="bg-blue-50 rounded px-3 py-2">
                                    <span class="text-blue-600 uppercase font-medium">Prev FCAC</span>
                                    <p class="font-bold text-gray-900">${{ number_format($caPrevious, 2) }}</p>
                                </div>
                                <div class="bg-green-50 rounded px-3 py-2">
                                    <span class="text-green-600 uppercase font-medium">CTD</span>
                                    <p class="font-bold text-gray-900">${{ number_format($caCtd, 2) }}</p>
                                </div>
                                <div class="bg-amber-50 rounded px-3 py-2">
                                    <span class="text-amber-600 uppercase font-medium">CTC</span>
                                    <p class="font-bold text-gray-900">${{ number_format($caCtc, 2) }}</p>
                                </div>
                                <div class="bg-indigo-50 rounded px-3 py-2">
                                    <span class="text-indigo-600 uppercase font-medium">FCAC</span>
                                    <p class="font-bold text-gray-900">${{ number_format($caFcac, 2) }}</p>
                                </div>
                                <div class="rounded px-3 py-2 {{ $caVariance < 0 ? 'bg-red-50' : 'bg-green-50' }}">
                                    <span class="{{ $caVariance < 0 ? 'text-red-600' : 'text-green-600' }} uppercase font-medium">Variance</span>
                                    <p class="font-bold {{ $caVariance < 0 ? 'text-red-700' : 'text-green-700' }}">${{ number_format($caVariance, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Expanded Content: Cost Packages & Line Items (Read-Only) --}}
                    <div x-show="open" x-transition>
                        @if($account->costPackages->isEmpty())
                            <div class="p-6 text-center text-gray-500 text-sm">
                                No cost packages in this control account.
                            </div>
                        @else
                            @foreach($account->costPackages as $package)
                                {{-- Package Header --}}
                                <div class="px-6 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="font-semibold text-gray-900">{{ $package->name }}</span>
                                        @if($package->item_no)
                                            <span class="text-sm text-gray-500">({{ $package->item_no }})</span>
                                        @endif
                                        <span class="text-xs text-gray-400">{{ $package->lineItems->count() }} items</span>
                                    </div>
                                </div>

                                @if($package->lineItems->isEmpty())
                                    <div class="px-6 py-4 text-center text-gray-500 text-sm border-b border-gray-100">
                                        No line items in this package.
                                    </div>
                                @else
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-16">Item</th>
                                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase w-14">UoM</th>
                                                    <th x-show="$store.columns.orig_qty" class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-20 bg-gray-100">Orig Qty</th>
                                                    <th x-show="$store.columns.orig_rate" class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-20 bg-gray-100">Orig Rate</th>
                                                    <th x-show="$store.columns.orig_amount" class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-24 bg-gray-100">Orig Amount</th>
                                                    <th x-show="$store.columns.prev_qty" class="px-3 py-3 text-right text-xs font-medium text-blue-600 uppercase w-20 bg-blue-50">Prev Qty</th>
                                                    <th x-show="$store.columns.prev_rate" class="px-3 py-3 text-right text-xs font-medium text-blue-600 uppercase w-20 bg-blue-50">Prev Rate</th>
                                                    <th x-show="$store.columns.prev_fcac" class="px-3 py-3 text-right text-xs font-medium text-blue-600 uppercase w-24 bg-blue-50">Prev FCAC</th>
                                                    <th x-show="$store.columns.ctd_qty" class="px-3 py-3 text-right text-xs font-medium text-green-600 uppercase w-24 bg-green-50">CTD Qty</th>
                                                    <th x-show="$store.columns.ctd_rate" class="px-3 py-3 text-right text-xs font-medium text-green-600 uppercase w-20 bg-green-50">CTD Rate</th>
                                                    <th x-show="$store.columns.ctd_amount" class="px-3 py-3 text-right text-xs font-medium text-green-600 uppercase w-24 bg-green-50">CTD Amount</th>
                                                    <th x-show="$store.columns.ctc_qty" class="px-3 py-3 text-right text-xs font-medium text-amber-600 uppercase w-20 bg-amber-50">CTC Qty</th>
                                                    <th x-show="$store.columns.ctc_rate" class="px-3 py-3 text-right text-xs font-medium text-amber-600 uppercase w-20 bg-amber-50">CTC Rate</th>
                                                    <th x-show="$store.columns.ctc_amount" class="px-3 py-3 text-right text-xs font-medium text-amber-600 uppercase w-24 bg-amber-50">CTC Amount</th>
                                                    <th x-show="$store.columns.fcac_qty" class="px-3 py-3 text-right text-xs font-medium text-indigo-600 uppercase w-20 bg-indigo-50">FCAC Qty</th>
                                                    <th x-show="$store.columns.fcac_rate" class="px-3 py-3 text-right text-xs font-medium text-indigo-600 uppercase w-20 bg-indigo-50">FCAC Rate</th>
                                                    <th x-show="$store.columns.fcac" class="px-3 py-3 text-right text-xs font-medium text-indigo-600 uppercase w-24 bg-indigo-50">FCAC</th>
                                                    <th x-show="$store.columns.variance" class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-24">Variance</th>
                                                    <th x-show="$store.columns.comments" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comments</th>
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
                                                        $existedInPeriod = !$period || $item->existedInPeriod($period);
                                                        $forecast = $item->forecasts->first();
                                                        if ($existedInPeriod) {
                                                            $pkgOriginal += $item->original_amount;
                                                            if ($forecast) {
                                                                $pkgPrevious += $forecast->previous_amount ?? 0;
                                                                $pkgCtd += $forecast->ctd_amount ?? 0;
                                                                $pkgCtc += $forecast->ctc_amount ?? 0;
                                                                $pkgFcac += $forecast->fcac_amount ?? 0;
                                                                $pkgVariance += $forecast->variance ?? 0;
                                                            }
                                                        }
                                                    @endphp
                                                    @if(!$existedInPeriod)
                                                        <tr class="hover:bg-gray-50 opacity-40">
                                                            <td class="px-3 py-2 text-sm text-gray-400">{{ $item->item_no }}</td>
                                                            <td class="px-3 py-2 text-sm text-gray-400">{{ $item->description }}</td>
                                                            <td class="px-3 py-2 text-sm text-gray-400 text-center">{{ $item->unit_of_measure }}</td>
                                                            <td :colspan="$store.columns.visibleDataCount" class="px-3 py-2 text-sm text-gray-400 text-center italic">Added in {{ $item->createdInPeriod->period_date->format('M Y') }}</td>
                                                        </tr>
                                                    @else
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-3 py-2 text-sm text-gray-600">{{ $item->item_no }}</td>
                                                        <td class="px-3 py-2 text-sm text-gray-900">{{ $item->description }}</td>
                                                        <td class="px-3 py-2 text-sm text-gray-500 text-center">{{ $item->unit_of_measure }}</td>
                                                        <td x-show="$store.columns.orig_qty" class="px-3 py-2 text-sm text-gray-900 text-right bg-gray-50">{{ number_format($item->original_qty, 1) }}</td>
                                                        <td x-show="$store.columns.orig_rate" class="px-3 py-2 text-sm text-gray-900 text-right bg-gray-50">${{ number_format($item->original_rate, 2) }}</td>
                                                        <td x-show="$store.columns.orig_amount" class="px-3 py-2 text-sm font-medium text-gray-900 text-right bg-gray-50">${{ number_format($item->original_amount, 2) }}</td>
                                                        @if($forecast)
                                                            <td x-show="$store.columns.prev_qty" class="px-3 py-2 text-sm text-gray-900 text-right bg-blue-50/50">{{ number_format($forecast->previous_qty ?? 0, 1) }}</td>
                                                            <td x-show="$store.columns.prev_rate" class="px-3 py-2 text-sm text-gray-900 text-right bg-blue-50/50">${{ number_format($forecast->previous_rate ?? 0, 2) }}</td>
                                                            <td x-show="$store.columns.prev_fcac" class="px-3 py-2 text-sm text-gray-900 text-right bg-blue-50/50">${{ number_format($forecast->previous_amount, 2) }}</td>
                                                            <td x-show="$store.columns.ctd_qty" class="px-3 py-2 text-sm text-gray-900 text-right bg-green-50/50">{{ number_format($forecast->ctd_qty ?? 0, 1) }}</td>
                                                            <td x-show="$store.columns.ctd_rate" class="px-3 py-2 text-sm text-gray-900 text-right bg-green-50/50">${{ number_format($item->original_rate, 2) }}</td>
                                                            <td x-show="$store.columns.ctd_amount" class="px-3 py-2 text-sm text-gray-900 text-right bg-green-50/50">${{ number_format($forecast->ctd_amount, 2) }}</td>
                                                            <td x-show="$store.columns.ctc_qty" class="px-3 py-2 text-sm text-gray-900 text-right bg-amber-50/50">{{ number_format($forecast->ctc_qty ?? 0, 1) }}</td>
                                                            <td x-show="$store.columns.ctc_rate" class="px-3 py-2 text-sm text-gray-900 text-right bg-amber-50/50">${{ number_format($forecast->ctc_rate ?? 0, 2) }}</td>
                                                            <td x-show="$store.columns.ctc_amount" class="px-3 py-2 text-sm text-gray-900 text-right bg-amber-50/50">${{ number_format($forecast->ctc_amount, 2) }}</td>
                                                            <td x-show="$store.columns.fcac_qty" class="px-3 py-2 text-sm text-gray-900 text-right bg-indigo-50/50">{{ number_format($forecast->fcac_qty ?? 0, 1) }}</td>
                                                            <td x-show="$store.columns.fcac_rate" class="px-3 py-2 text-sm text-gray-900 text-right bg-indigo-50/50">${{ number_format($forecast->fcac_rate ?? 0, 2) }}</td>
                                                            <td x-show="$store.columns.fcac" class="px-3 py-2 text-sm font-medium text-gray-900 text-right bg-indigo-50/50">${{ number_format($forecast->fcac_amount, 2) }}</td>
                                                            <td x-show="$store.columns.variance" class="px-3 py-2 text-sm text-right {{ ($forecast->variance ?? 0) < 0 ? 'text-red-600 font-medium' : 'text-gray-900' }}">
                                                                @if(($forecast->variance ?? 0) != 0)
                                                                    ${{ number_format($forecast->variance, 2) }}
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                            <td x-show="$store.columns.comments" class="px-3 py-2 text-sm text-gray-500 max-w-xs truncate">{{ $forecast->comments }}</td>
                                                        @else
                                                            <td :colspan="$store.columns.visibleDataCount - ($store.columns.orig_qty ? 1 : 0) - ($store.columns.orig_rate ? 1 : 0) - ($store.columns.orig_amount ? 1 : 0)" class="px-3 py-2 text-sm text-gray-400 text-center">No forecast data</td>
                                                        @endif
                                                    </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                            <tfoot class="bg-gray-100">
                                                <tr class="font-semibold">
                                                    <td :colspan="3 + ($store.columns.orig_qty ? 1 : 0) + ($store.columns.orig_rate ? 1 : 0)" class="px-3 py-3 text-sm text-gray-700 text-right">Package Total</td>
                                                    <td x-show="$store.columns.orig_amount" class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($pkgOriginal, 2) }}</td>
                                                    <td x-show="$store.columns.prev_qty"></td>
                                                    <td x-show="$store.columns.prev_rate"></td>
                                                    <td x-show="$store.columns.prev_fcac" class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($pkgPrevious, 2) }}</td>
                                                    <td x-show="$store.columns.ctd_qty"></td>
                                                    <td x-show="$store.columns.ctd_rate"></td>
                                                    <td x-show="$store.columns.ctd_amount" class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($pkgCtd, 2) }}</td>
                                                    <td x-show="$store.columns.ctc_qty"></td>
                                                    <td x-show="$store.columns.ctc_rate"></td>
                                                    <td x-show="$store.columns.ctc_amount" class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($pkgCtc, 2) }}</td>
                                                    <td x-show="$store.columns.fcac_qty"></td>
                                                    <td x-show="$store.columns.fcac_rate"></td>
                                                    <td x-show="$store.columns.fcac" class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($pkgFcac, 2) }}</td>
                                                    <td x-show="$store.columns.variance" class="px-3 py-3 text-sm text-right {{ $pkgVariance < 0 ? 'text-red-600' : 'text-gray-900' }}">${{ number_format($pkgVariance, 2) }}</td>
                                                    <td x-show="$store.columns.comments"></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </div>
            @endforeach

        </div>
    </div>
<script>
document.addEventListener('alpine:init', () => {
    const defaults = {
        orig_qty: false, orig_rate: false, orig_amount: true,
        prev_qty: false, prev_rate: false, prev_fcac: true,
        ctd_qty: true, ctd_rate: true, ctd_amount: true,
        ctc_qty: false, ctc_rate: false, ctc_amount: true,
        fcac_qty: false, fcac_rate: false, fcac: true,
        variance: true, comments: true,
    };
    const saved = JSON.parse(localStorage.getItem('projectColumnFilter') || 'null');
    const keys = Object.keys(defaults);
    Alpine.store('columns', {
        ...defaults,
        ...(saved || {}),
        toggle(key) { this[key] = !this[key]; this.save(); },
        save() {
            const data = {};
            keys.forEach(k => data[k] = this[k]);
            localStorage.setItem('projectColumnFilter', JSON.stringify(data));
        },
        get visibleDataCount() {
            return keys.filter(k => this[k]).length;
        },
    });
});
</script>
</x-app-layout>
