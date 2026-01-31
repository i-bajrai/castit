<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="reports" :breadcrumbs="[['route' => route('projects.reports', $project), 'label' => 'Reports']]">
            Period-over-Period Movement - {{ $project->name }}
        </x-project-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto sm:px-6 lg:px-8" style="max-width: 90%">

            {{-- Period Selector --}}
            @if($allPeriods->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="px-6 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <label for="period-selector" class="text-sm font-medium text-gray-700">Period:</label>
                            <select id="period-selector" onchange="window.location.href = this.value" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($allPeriods as $p)
                                    <option value="{{ route('projects.period-movement', ['project' => $project, 'period' => $p->id]) }}"
                                        {{ $period && $period->id === $p->id ? 'selected' : '' }}>
                                        {{ $p->period_date->format('F Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if($period)
                            <span class="text-sm text-gray-500">
                                {{ $period->period_date->format('F Y') }}
                                @if($previousPeriod)
                                    &mdash; compared to {{ $previousPeriod->period_date->format('M Y') }}
                                @else
                                    &mdash; no previous period
                                @endif
                            </span>
                        @endif
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th colspan="3" class="px-3 py-2 bg-gray-50"></th>
                                <th colspan="3" class="px-3 py-2 text-center text-xs font-bold text-gray-700 uppercase bg-gray-100 border-l border-r border-gray-200">
                                    {{ $previousPeriod ? $previousPeriod->period_date->format('M Y') : 'Previous' }}
                                </th>
                                <th colspan="3" class="px-3 py-2 text-center text-xs font-bold text-gray-700 uppercase bg-blue-50 border-r border-gray-200">
                                    {{ $period ? $period->period_date->format('M Y') : 'Current' }}
                                </th>
                                <th colspan="3" class="px-3 py-2 text-center text-xs font-bold text-gray-700 uppercase bg-green-50 border-r border-gray-200">Movement</th>
                            </tr>
                            <tr class="bg-gray-50">
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-20">CA</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-16">Item</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase min-w-[180px]">Description</th>
                                {{-- Previous --}}
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-600 uppercase w-24 bg-gray-100 border-l border-gray-200">CTD</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-600 uppercase w-24 bg-gray-100">CTC</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-600 uppercase w-24 bg-gray-100 border-r border-gray-200">FCAC</th>
                                {{-- Current --}}
                                <th class="px-3 py-2 text-right text-xs font-medium text-blue-700 uppercase w-24 bg-blue-50">CTD</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-blue-700 uppercase w-24 bg-blue-50">CTC</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-blue-700 uppercase w-24 bg-blue-50 border-r border-gray-200">FCAC</th>
                                {{-- Deltas --}}
                                <th class="px-3 py-2 text-right text-xs font-medium text-green-700 uppercase w-24 bg-green-50">CTD</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-green-700 uppercase w-24 bg-green-50">CTC</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-green-700 uppercase w-24 bg-green-50 border-r border-gray-200">FCAC</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($rows as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-2 text-sm text-gray-600">{{ $row['ca_code'] }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-500">{{ $row['item_no'] }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900">{{ $row['description'] }}</td>
                                    {{-- Previous --}}
                                    <td class="px-3 py-2 text-sm text-gray-600 text-right bg-gray-50/50 border-l border-gray-100">${{ number_format($row['prev_ctd_amount'], 0) }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-600 text-right bg-gray-50/50">${{ number_format($row['prev_ctc_amount'], 0) }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-600 text-right bg-gray-50/50 border-r border-gray-100">${{ number_format($row['prev_fcac_amount'], 0) }}</td>
                                    {{-- Current --}}
                                    <td class="px-3 py-2 text-sm text-gray-900 text-right bg-blue-50/30">${{ number_format($row['curr_ctd_amount'], 0) }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900 text-right bg-blue-50/30">${{ number_format($row['curr_ctc_amount'], 0) }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-900 text-right bg-blue-50/30 border-r border-gray-100">${{ number_format($row['curr_fcac_amount'], 0) }}</td>
                                    {{-- Deltas --}}
                                    <td class="px-3 py-2 text-sm text-right bg-green-50/30 font-medium {{ $row['ctd_delta'] > 0 ? 'text-red-600' : ($row['ctd_delta'] < 0 ? 'text-green-600' : 'text-gray-400') }}">
                                        {{ $row['ctd_delta'] != 0 ? '$' . number_format($row['ctd_delta'], 0) : '0' }}
                                    </td>
                                    <td class="px-3 py-2 text-sm text-right bg-green-50/30 font-medium {{ $row['ctc_delta'] > 0 ? 'text-red-600' : ($row['ctc_delta'] < 0 ? 'text-green-600' : 'text-gray-400') }}">
                                        {{ $row['ctc_delta'] != 0 ? '$' . number_format($row['ctc_delta'], 0) : '0' }}
                                    </td>
                                    <td class="px-3 py-2 text-sm text-right bg-green-50/30 border-r border-gray-100 font-medium {{ $row['fcac_delta'] > 0 ? 'text-red-600' : ($row['fcac_delta'] < 0 ? 'text-green-600' : 'text-gray-400') }}">
                                        {{ $row['fcac_delta'] != 0 ? '$' . number_format($row['fcac_delta'], 0) : '0' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="px-3 py-8 text-center text-gray-500">No movements between periods.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(!empty($rows))
                            <tfoot class="bg-gray-100">
                                <tr class="font-bold">
                                    <td colspan="3" class="px-3 py-3 text-sm text-gray-700 text-right">Totals</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right border-l border-gray-200">${{ number_format($totals['prev_ctd_amount'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['prev_ctc_amount'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right border-r border-gray-200">${{ number_format($totals['prev_fcac_amount'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['curr_ctd_amount'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['curr_ctc_amount'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right border-r border-gray-200">${{ number_format($totals['curr_fcac_amount'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-right {{ $totals['ctd_delta'] > 0 ? 'text-red-600' : ($totals['ctd_delta'] < 0 ? 'text-green-600' : 'text-gray-900') }}">${{ number_format($totals['ctd_delta'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-right {{ $totals['ctc_delta'] > 0 ? 'text-red-600' : ($totals['ctc_delta'] < 0 ? 'text-green-600' : 'text-gray-900') }}">${{ number_format($totals['ctc_delta'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-right border-r border-gray-200 {{ $totals['fcac_delta'] > 0 ? 'text-red-600' : ($totals['fcac_delta'] < 0 ? 'text-green-600' : 'text-gray-900') }}">${{ number_format($totals['fcac_delta'], 0) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
