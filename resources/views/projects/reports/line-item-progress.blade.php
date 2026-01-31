<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="reports" :breadcrumbs="[['route' => route('projects.reports', $project), 'label' => 'Reports']]">
            Line Item Progress - {{ $project->name }}
        </x-project-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto sm:px-6 lg:px-8" style="max-width: 95%">

            {{-- Period Selector --}}
            @if($allPeriods->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="px-6 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <label for="period-selector" class="text-sm font-medium text-gray-700">Period:</label>
                            <select id="period-selector" onchange="window.location.href = this.value" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($allPeriods as $p)
                                    <option value="{{ route('projects.line-item-progress', ['project' => $project, 'period' => $p->id]) }}"
                                        {{ $period && $period->id === $p->id ? 'selected' : '' }}>
                                        {{ $p->period_date->format('F Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if($period)
                            <span class="text-sm text-gray-500">{{ $period->period_date->format('F Y') }}</span>
                        @endif
                    </div>
                </div>
            @endif

            @if(empty($groups))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                    No line item data for this period.
                </div>
            @else
                @foreach($groups as $group)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <h3 class="text-sm font-bold text-gray-800">{{ $group['code'] }} - {{ $group['description'] }}</h3>
                        </div>

                        @foreach($group['packages'] as $package)
                            <div class="px-6 py-2 bg-gray-50/50 border-b border-gray-100">
                                <span class="text-xs font-semibold text-gray-600 uppercase">{{ $package['name'] }}</span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">Item No</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase min-w-[180px]">Description</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-20">Orig Qty</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-24">Orig Amount</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-blue-700 uppercase w-20 bg-blue-50">CTD Qty</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-blue-700 uppercase w-24 bg-blue-50">CTD Amount</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-amber-700 uppercase w-20 bg-amber-50">CTC Qty</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-amber-700 uppercase w-24 bg-amber-50">CTC Amount</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-green-700 uppercase w-24 bg-green-50">FCAC</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-20">% Complete</th>
                                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-24">Variance</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($package['items'] as $item)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-3 py-2 text-sm text-gray-500">{{ $item['item_no'] }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-900">{{ $item['description'] }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($item['original_qty'], 2) }}</td>
                                                <td class="px-3 py-2 text-sm text-gray-900 text-right">${{ number_format($item['original_amount'], 0) }}</td>
                                                <td class="px-3 py-2 text-sm text-right bg-blue-50/30 {{ $item['ctd_qty'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">{{ number_format($item['ctd_qty'], 2) }}</td>
                                                <td class="px-3 py-2 text-sm text-right bg-blue-50/30 {{ $item['ctd_amount'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">${{ number_format($item['ctd_amount'], 0) }}</td>
                                                <td class="px-3 py-2 text-sm text-right bg-amber-50/30 {{ $item['ctc_qty'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">{{ number_format($item['ctc_qty'], 2) }}</td>
                                                <td class="px-3 py-2 text-sm text-right bg-amber-50/30 {{ $item['ctc_amount'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">${{ number_format($item['ctc_amount'], 0) }}</td>
                                                <td class="px-3 py-2 text-sm font-semibold text-right bg-green-50/30">${{ number_format($item['fcac_amount'], 0) }}</td>
                                                <td class="px-3 py-2 text-sm text-right">
                                                    <div class="flex items-center justify-end gap-2">
                                                        <div class="w-16 bg-gray-200 rounded-full h-2">
                                                            <div class="bg-blue-500 h-2 rounded-full" style="width: {{ min(100, $item['pct_complete']) }}%"></div>
                                                        </div>
                                                        <span class="text-xs {{ $item['pct_complete'] >= 100 ? 'text-green-600 font-semibold' : 'text-gray-600' }}">{{ number_format($item['pct_complete'], 1) }}%</span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 text-sm text-right {{ $item['variance'] < 0 ? 'text-red-600 font-medium' : ($item['variance'] > 0 ? 'text-green-600 font-medium' : 'text-gray-400') }}">
                                                    @if($item['variance'] != 0)
                                                        ${{ number_format($item['variance'], 0) }}
                                                    @else
                                                        0
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                {{-- Totals --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-6 py-4">
                        <table class="min-w-full">
                            <tfoot class="bg-gray-100">
                                <tr class="font-bold">
                                    <td colspan="2" class="px-3 py-3 text-sm text-gray-700 text-right">Totals</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right w-20">{{ number_format($totals['original_qty'], 2) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right w-24">${{ number_format($totals['original_amount'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right w-20">{{ number_format($totals['ctd_qty'], 2) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right w-24">${{ number_format($totals['ctd_amount'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right w-20">{{ number_format($totals['ctc_qty'], 2) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right w-24">${{ number_format($totals['ctc_amount'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right w-24">${{ number_format($totals['fcac_amount'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right w-20"></td>
                                    <td class="px-3 py-3 text-sm text-right w-24 {{ $totals['variance'] < 0 ? 'text-red-600' : ($totals['variance'] > 0 ? 'text-green-600' : 'text-gray-900') }}">
                                        ${{ number_format($totals['variance'], 0) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
