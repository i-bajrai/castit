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
                <a href="{{ route('projects.data-entry.control-accounts', $project) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-indigo-700 transition">
                    Control Accounts
                </a>
                <a href="{{ route('projects.show', $project) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Cost Detail
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
                    <span class="whitespace-nowrap py-3 px-1 border-b-2 border-indigo-500 text-indigo-600 font-medium text-sm">
                        Line Items
                    </span>
                    <a href="{{ route('projects.data-entry.control-accounts', $project) }}" class="whitespace-nowrap py-3 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm transition">
                        Control Accounts
                    </a>
                </nav>
            </div>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            @if($packages->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                    <p class="text-gray-500">No cost packages configured. <a href="{{ route('projects.settings', $project) }}" class="text-indigo-600 hover:underline">Add them in Settings</a>.</p>
                </div>
            @endif

            {{-- Cost Packages --}}
            @foreach($packages as $package)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6" x-data="{ open: true }">
                    <div class="border-b border-gray-200">
                        <button @click="open = !open" class="w-full px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-90': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                <div class="text-left">
                                    <span class="font-semibold text-gray-900">{{ $package->name }}</span>
                                    @if($package->item_no)
                                        <span class="ml-2 text-sm text-gray-500">({{ $package->item_no }})</span>
                                    @endif
                                </div>
                            </div>
                            <span class="text-sm text-gray-500">{{ $package->lineItems->count() }} items</span>
                        </button>
                    </div>

                    <div x-show="open" x-transition>
                        @if($package->lineItems->isEmpty())
                            <div class="p-6 text-center text-gray-500 text-sm">No line items in this package.</div>
                        @else
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

                                                    {{-- CTD inputs --}}
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

                                                    {{-- CTC inputs --}}
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

                                                    {{-- Calculated FCAC & Variance --}}
                                                    <td class="px-3 py-2 text-sm font-medium text-gray-900 text-right bg-indigo-50/50" x-text="'$' + fcac.toFixed(2)"></td>
                                                    <td class="px-3 py-2 text-sm text-right" :class="variance < 0 ? 'text-red-600 font-medium' : 'text-gray-900'" x-text="variance !== 0 ? '$' + variance.toFixed(2) : '-'"></td>

                                                    {{-- Comments --}}
                                                    <td class="px-1 py-1">
                                                        <input type="text" name="{{ $prefix }}[comments]"
                                                            value="{{ $forecast->comments ?? '' }}"
                                                            class="w-full text-sm border-gray-300 rounded px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500"
                                                            placeholder="Notes...">
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
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
