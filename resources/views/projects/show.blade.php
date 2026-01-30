<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $project->name }}
                </h2>
                @if($period)
                    <p class="text-sm text-gray-500 mt-1">Forecast Period: {{ $period->period_date->format('F Y') }}</p>
                @endif
            </div>
            <div class="flex gap-3">
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
            {{-- Project Totals Summary --}}
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
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
