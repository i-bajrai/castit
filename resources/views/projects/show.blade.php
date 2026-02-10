<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="cost-detail">
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

            {{-- ==================== SETUP PROMPT ==================== --}}
            @if($accounts->isNotEmpty() && $accounts->every(fn($a) => $a->baseline_budget == 0 && $a->costPackages->isEmpty()))
                <div class="mb-6 bg-indigo-50 border border-indigo-200 rounded-lg p-4 flex items-center justify-between" data-testid="setup-budget-banner">
                    <p class="text-sm text-indigo-700">Your control accounts are set up but have no budgets yet. Set up your baseline budgets to get started.</p>
                    <a href="{{ route('projects.budget', $project) }}" class="shrink-0 ml-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        Set Up Budget
                    </a>
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
                                'prev_fcac' => 'Prev FCAC',
                                'ctd_qty' => 'CTD Qty',
                                'ctd_rate' => 'CTD Rate',
                                'ctd_amount' => 'CTD Amount',
                                'ctc_qty' => 'CTC Qty',
                                'ctc_amount' => 'CTC Amount',
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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-12 text-center" data-testid="no-control-accounts">
                    <p class="text-gray-500">No control accounts yet. <a href="{{ route('projects.setup', $project) }}" class="text-indigo-600 hover:underline">Add control accounts</a> to get started.</p>
                </div>
            @endif

            @foreach($accounts as $account)
                @php
                    // Compute CA-level totals from line items across all packages
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
                                <div class="flex items-center gap-3">
                                    <span class="text-sm {{ $caItemCount > 0 ? 'text-emerald-600 font-semibold' : 'text-gray-400' }}">{{ $caItemCount }} items</span>
                                    <a href="{{ route('projects.control-accounts.line-items', [$project, $account]) }}" class="text-xs text-gray-500 hover:text-gray-700 font-medium">Manage</a>
                                </div>
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

                    {{-- Expanded Content: Cost Packages & Line Items --}}
                    <div x-show="open" x-transition>
                        @if($account->costPackages->isEmpty())
                            <div class="p-6 text-center text-gray-500 text-sm">
                                No cost packages in this control account. <a href="{{ route('projects.control-accounts.line-items', [$project, $account]) }}" class="text-indigo-600 hover:underline">Manage line items</a>.
                            </div>
                        @else
                            @if($isEditable)
                                {{-- EDITABLE MODE --}}
                                <div>

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
                                            <div class="flex items-center gap-2">
                                                <a href="#" x-on:click.prevent="$dispatch('open-modal', 'add-line-item-{{ $package->id }}')" class="inline-flex items-center text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    Add Line Item
                                                </a>
                                                <a href="#" x-on:click.prevent="$dispatch('open-modal', 'edit-cost-package-{{ $package->id }}')" class="text-xs text-gray-400 hover:text-gray-600">Edit</a>
                                                <a href="#" x-on:click.prevent="$dispatch('open-modal', 'delete-cost-package-{{ $package->id }}')" class="text-xs text-red-400 hover:text-red-600">Delete</a>
                                            </div>
                                        </div>

                                        @if($package->lineItems->isEmpty())
                                            <div class="px-6 py-4 text-center text-gray-500 text-sm border-b border-gray-100">
                                                No line items in this package. <a href="#" x-on:click.prevent="$dispatch('open-modal', 'add-line-item-{{ $package->id }}')" class="text-indigo-600 hover:underline">Add a line item</a>.
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
                                                            <th x-show="$store.columns.prev_fcac" class="px-3 py-3 text-right text-xs font-medium text-blue-600 uppercase w-24 bg-blue-50">Prev FCAC</th>
                                                            <th x-show="$store.columns.ctd_qty" class="px-3 py-3 text-right text-xs font-medium text-green-600 uppercase w-24 bg-green-50">CTD Qty</th>
                                                            <th x-show="$store.columns.ctd_rate" class="px-3 py-3 text-right text-xs font-medium text-green-600 uppercase w-20 bg-green-50">CTD Rate</th>
                                                            <th x-show="$store.columns.ctd_amount" class="px-3 py-3 text-right text-xs font-medium text-green-600 uppercase w-24 bg-green-50">CTD Amount</th>
                                                            <th x-show="$store.columns.ctc_qty" class="px-3 py-3 text-right text-xs font-medium text-amber-600 uppercase w-20 bg-amber-50">CTC Qty</th>
                                                            <th x-show="$store.columns.ctc_amount" class="px-3 py-3 text-right text-xs font-medium text-amber-600 uppercase w-24 bg-amber-50">CTC Amount</th>
                                                            <th x-show="$store.columns.fcac" class="px-3 py-3 text-right text-xs font-medium text-indigo-600 uppercase w-24 bg-indigo-50">FCAC</th>
                                                            <th x-show="$store.columns.variance" class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-24">Variance</th>
                                                            <th x-show="$store.columns.comments" class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-40">Comments</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        @foreach($package->lineItems as $item)
                                                            @php
                                                                $forecast = $item->forecasts->first();
                                                            @endphp
                                                            <tr class="hover:bg-gray-50"
                                                                x-data="{
                                                                    ctdQty: {{ $forecast->ctd_qty ?? 0 }},
                                                                    origQty: {{ $item->original_qty }},
                                                                    origRate: {{ $item->original_rate }},
                                                                    origAmount: {{ $item->original_amount }},
                                                                    prevAmount: {{ $forecast->previous_amount ?? 0 }},
                                                                    get ctdRate() { return this.origRate },
                                                                    get ctdAmount() { return +(this.ctdQty * this.origRate).toFixed(2) },
                                                                    get ctcQty() { return Math.max(0, this.origQty - this.ctdQty) },
                                                                    get ctcAmount() { return +(this.ctcQty * this.origRate).toFixed(2) },
                                                                    get fcac() { return this.ctdAmount + this.ctcAmount },
                                                                    get variance() { return +(this.prevAmount - this.fcac).toFixed(2) },
                                                                }">
                                                                <td class="px-3 py-2 text-sm text-gray-600">{{ $item->item_no }}</td>
                                                                <td class="px-3 py-2 text-sm text-gray-900">{{ $item->description }}</td>
                                                                <td class="px-3 py-2 text-sm text-gray-500 text-center">{{ $item->unit_of_measure }}</td>
                                                                <td x-show="$store.columns.orig_qty" class="px-3 py-2 text-sm text-gray-900 text-right bg-gray-50">{{ number_format($item->original_qty, 1) }}</td>
                                                                <td x-show="$store.columns.orig_rate" class="px-3 py-2 text-sm text-gray-900 text-right bg-gray-50">${{ number_format($item->original_rate, 2) }}</td>
                                                                <td x-show="$store.columns.orig_amount" class="px-3 py-2 text-sm font-medium text-gray-900 text-right bg-gray-50">${{ number_format($item->original_amount, 2) }}</td>
                                                                <td x-show="$store.columns.prev_fcac" class="px-3 py-2 text-sm text-gray-900 text-right bg-blue-50/50">${{ number_format($forecast->previous_amount ?? 0, 2) }}</td>

                                                                {{-- CTD Qty (MODAL) --}}
                                                                <td x-show="$store.columns.ctd_qty" class="px-1 py-1 bg-green-50/30"
                                                                    x-data="{
                                                                        editQty: ctdQty,
                                                                        saving: false,
                                                                        error: false,
                                                                        async saveCtdQty() {
                                                                            @if($forecast?->id)
                                                                                this.saving = true;
                                                                                this.error = false;
                                                                                try {
                                                                                    const res = await fetch('{{ route('projects.forecasts.update-ctd-qty', [$project, $forecast]) }}', {
                                                                                        method: 'PATCH',
                                                                                        headers: {
                                                                                            'Content-Type': 'application/json',
                                                                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                                        },
                                                                                        body: JSON.stringify({ ctd_qty: this.editQty }),
                                                                                    });
                                                                                    if (!res.ok) throw new Error();
                                                                                    ctdQty = this.editQty;
                                                                                    this.$dispatch('close');
                                                                                } catch {
                                                                                    this.error = true;
                                                                                } finally {
                                                                                    this.saving = false;
                                                                                }
                                                                            @else
                                                                                ctdQty = this.editQty;
                                                                                this.$dispatch('close');
                                                                            @endif
                                                                        }
                                                                    }"
                                                                    x-on:open-modal.window="if ($event.detail === 'ctd-qty-{{ $item->id }}') editQty = ctdQty">
                                                                    <button type="button"
                                                                        x-on:click.prevent="$dispatch('open-modal', 'ctd-qty-{{ $item->id }}')"
                                                                        class="w-full text-sm text-right px-2 py-1 rounded border border-gray-300 hover:border-green-400 hover:bg-green-50 transition"
                                                                        x-text="ctdQty">
                                                                    </button>
                                                                    <x-modal name="ctd-qty-{{ $item->id }}" :show="false" maxWidth="sm">
                                                                        <div class="p-6">
                                                                            <h2 class="text-lg font-medium text-gray-900 mb-1">CTD Qty - {{ $item->description }}</h2>
                                                                            <p class="text-sm text-gray-500 mb-4">Item {{ $item->item_no }}</p>
                                                                            <input type="number" step="0.01"
                                                                                x-model.number="editQty"
                                                                                x-on:keydown.enter.prevent="saveCtdQty()"
                                                                                class="w-full text-sm border-gray-300 rounded-md focus:border-green-500 focus:ring-green-500">
                                                                            <p x-show="error" x-cloak class="mt-2 text-sm text-red-600">Failed to save. Please try again.</p>
                                                                            <div class="mt-4 flex justify-end gap-2">
                                                                                <span x-show="saving" class="text-sm text-gray-400 self-center">Saving...</span>
                                                                                <x-primary-button type="button" x-on:click="saveCtdQty()" x-bind:disabled="saving">Save</x-primary-button>
                                                                            </div>
                                                                        </div>
                                                                    </x-modal>
                                                                </td>
                                                                {{-- CTD Rate (computed) --}}
                                                                <td x-show="$store.columns.ctd_rate" class="px-3 py-2 text-sm text-gray-900 text-right bg-green-50/30" x-text="'$' + ctdRate.toFixed(2)"></td>
                                                                {{-- CTD Amount (computed) --}}
                                                                <td x-show="$store.columns.ctd_amount" class="px-3 py-2 text-sm text-gray-900 text-right bg-green-50/30" x-text="'$' + ctdAmount.toFixed(2)"></td>
                                                                {{-- CTC Qty (computed) --}}
                                                                <td x-show="$store.columns.ctc_qty" class="px-3 py-2 text-sm text-gray-900 text-right bg-amber-50/30" x-text="ctcQty.toFixed(1)"></td>
                                                                {{-- CTC Amount (computed) --}}
                                                                <td x-show="$store.columns.ctc_amount" class="px-3 py-2 text-sm text-gray-900 text-right bg-amber-50/30" x-text="'$' + ctcAmount.toFixed(2)"></td>
                                                                {{-- FCAC (computed) --}}
                                                                <td x-show="$store.columns.fcac" class="px-3 py-2 text-sm font-medium text-gray-900 text-right bg-indigo-50/50" x-text="'$' + fcac.toFixed(2)"></td>
                                                                {{-- Variance (computed) --}}
                                                                <td x-show="$store.columns.variance" class="px-3 py-2 text-sm text-right" :class="variance < 0 ? 'text-red-600 font-medium' : 'text-gray-900'" x-text="variance !== 0 ? '$' + variance.toFixed(2) : '-'"></td>

                                                                {{-- Comments (MODAL) --}}
                                                                <td x-show="$store.columns.comments" class="px-1 py-1"
                                                                    x-data="{
                                                                        comment: '{{ str_replace("'", "\\'", $forecast->comments ?? '') }}',
                                                                        saving: false,
                                                                        error: false,
                                                                        async saveComment() {
                                                                            @if($forecast?->id)
                                                                                this.saving = true;
                                                                                this.error = false;
                                                                                try {
                                                                                    const res = await fetch('{{ route('projects.forecasts.update-comment', [$project, $forecast]) }}', {
                                                                                        method: 'PATCH',
                                                                                        headers: {
                                                                                            'Content-Type': 'application/json',
                                                                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                                        },
                                                                                        body: JSON.stringify({ comments: this.comment }),
                                                                                    });
                                                                                    if (!res.ok) throw new Error();
                                                                                    this.$dispatch('close');
                                                                                } catch {
                                                                                    this.error = true;
                                                                                } finally {
                                                                                    this.saving = false;
                                                                                }
                                                                            @else
                                                                                this.$dispatch('close');
                                                                            @endif
                                                                        }
                                                                    }">
                                                                    <button type="button"
                                                                        x-on:click.prevent="$dispatch('open-modal', 'comment-{{ $item->id }}')"
                                                                        class="w-full text-sm text-left px-2 py-1 rounded border border-gray-300 hover:border-indigo-400 hover:bg-indigo-50 transition truncate"
                                                                        :class="comment ? 'text-gray-900' : 'text-gray-400'"
                                                                        x-text="comment || 'Add comment...'">
                                                                    </button>
                                                                    <x-modal name="comment-{{ $item->id }}" :show="false" maxWidth="lg">
                                                                        <div class="p-6">
                                                                            <h2 class="text-lg font-medium text-gray-900 mb-1">Comment - {{ $item->description }}</h2>
                                                                            <p class="text-sm text-gray-500 mb-4">Item {{ $item->item_no }}</p>
                                                                            <textarea
                                                                                x-model="comment"
                                                                                rows="4"
                                                                                class="w-full text-sm border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500"
                                                                                placeholder="Enter comment..."></textarea>
                                                                            <p x-show="error" x-cloak class="mt-2 text-sm text-red-600">Failed to save. Please try again.</p>
                                                                            <div class="mt-4 flex justify-end gap-2">
                                                                                <span x-show="saving" class="text-sm text-gray-400 self-center">Saving...</span>
                                                                                <x-primary-button type="button" x-on:click="saveComment()" x-bind:disabled="saving">Save</x-primary-button>
                                                                            </div>
                                                                        </div>
                                                                    </x-modal>
                                                                </td>
                                                                {{-- No edit/delete during forecasting — structure is locked --}}
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    @endforeach

                                </div>
                            @else
                                {{-- READ-ONLY MODE --}}
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
                                                        <th x-show="$store.columns.prev_fcac" class="px-3 py-3 text-right text-xs font-medium text-blue-600 uppercase w-24 bg-blue-50">Prev FCAC</th>
                                                        <th x-show="$store.columns.ctd_qty" class="px-3 py-3 text-right text-xs font-medium text-green-600 uppercase w-24 bg-green-50">CTD Qty</th>
                                                        <th x-show="$store.columns.ctd_rate" class="px-3 py-3 text-right text-xs font-medium text-green-600 uppercase w-20 bg-green-50">CTD Rate</th>
                                                        <th x-show="$store.columns.ctd_amount" class="px-3 py-3 text-right text-xs font-medium text-green-600 uppercase w-24 bg-green-50">CTD Amount</th>
                                                        <th x-show="$store.columns.ctc_qty" class="px-3 py-3 text-right text-xs font-medium text-amber-600 uppercase w-20 bg-amber-50">CTC Qty</th>
                                                        <th x-show="$store.columns.ctc_amount" class="px-3 py-3 text-right text-xs font-medium text-amber-600 uppercase w-24 bg-amber-50">CTC Amount</th>
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
                                                                <td x-show="$store.columns.prev_fcac" class="px-3 py-2 text-sm text-gray-900 text-right bg-blue-50/50">${{ number_format($forecast->previous_amount, 2) }}</td>
                                                                <td x-show="$store.columns.ctd_qty" class="px-3 py-2 text-sm text-gray-900 text-right bg-green-50/50">{{ number_format($forecast->ctd_qty ?? 0, 1) }}</td>
                                                                <td x-show="$store.columns.ctd_rate" class="px-3 py-2 text-sm text-gray-900 text-right bg-green-50/50">${{ number_format($item->original_rate, 2) }}</td>
                                                                <td x-show="$store.columns.ctd_amount" class="px-3 py-2 text-sm text-gray-900 text-right bg-green-50/50">${{ number_format($forecast->ctd_amount, 2) }}</td>
                                                                <td x-show="$store.columns.ctc_qty" class="px-3 py-2 text-sm text-gray-900 text-right bg-amber-50/50">{{ number_format(max(0, $item->original_qty - ($forecast->ctd_qty ?? 0)), 1) }}</td>
                                                                <td x-show="$store.columns.ctc_amount" class="px-3 py-2 text-sm text-gray-900 text-right bg-amber-50/50">${{ number_format($forecast->ctc_amount, 2) }}</td>
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
                                                        <td x-show="$store.columns.prev_fcac" class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($pkgPrevious, 2) }}</td>
                                                        <td x-show="$store.columns.ctd_qty"></td>
                                                        <td x-show="$store.columns.ctd_rate"></td>
                                                        <td x-show="$store.columns.ctd_amount" class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($pkgCtd, 2) }}</td>
                                                        <td x-show="$store.columns.ctc_qty"></td>
                                                        <td x-show="$store.columns.ctc_amount" class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($pkgCtc, 2) }}</td>
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
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- ==================== MODALS (only when editable) ==================== --}}
            @if($isEditable)
                {{-- Create Cost Package Modals (one per CA) --}}
                @foreach($accounts as $account)
                    <x-modal name="create-cost-package-{{ $account->id }}" :show="false" maxWidth="lg">
                        <form method="POST" action="{{ route('projects.cost-packages.store', $project) }}" class="p-6">
                            @csrf
                            <input type="hidden" name="control_account_id" value="{{ $account->id }}">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Add Cost Package to {{ $account->code }}</h2>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="create_pkg_item_no_{{ $account->id }}" value="Item No" />
                                    <x-text-input id="create_pkg_item_no_{{ $account->id }}" name="item_no" type="text" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <x-input-label for="create_pkg_sort_order_{{ $account->id }}" value="Sort Order" />
                                    <x-text-input id="create_pkg_sort_order_{{ $account->id }}" name="sort_order" type="number" class="mt-1 block w-full" value="0" required />
                                </div>
                                <div class="col-span-2">
                                    <x-input-label for="create_pkg_name_{{ $account->id }}" value="Name" />
                                    <x-text-input id="create_pkg_name_{{ $account->id }}" name="name" type="text" class="mt-1 block w-full" required />
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3">
                                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                <x-primary-button>Create</x-primary-button>
                            </div>
                        </form>
                    </x-modal>

                    {{-- Edit / Delete Cost Package + Line Item Modals --}}
                    @foreach($account->costPackages as $package)
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
                @endforeach
            @endif
        </div>
    </div>
<script>
document.addEventListener('alpine:init', () => {
    const defaults = {
        orig_qty: false, orig_rate: false, orig_amount: true,
        prev_fcac: true, ctd_qty: true, ctd_rate: true, ctd_amount: true,
        ctc_qty: false, ctc_amount: true, fcac: true, variance: true, comments: true,
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
