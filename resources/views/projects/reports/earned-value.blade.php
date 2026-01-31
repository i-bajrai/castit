<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="reports" :breadcrumbs="[['route' => route('projects.reports', $project), 'label' => 'Reports']]">
            Earned Value Summary - {{ $project->name }}
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
                                    <option value="{{ route('projects.earned-value', ['project' => $project, 'period' => $p->id]) }}"
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

            {{-- Legend --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="px-6 py-3">
                    <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500">
                        <span><strong>BAC</strong> = Budget at Completion</span>
                        <span><strong>PV</strong> = Planned Value</span>
                        <span><strong>EV</strong> = Earned Value</span>
                        <span><strong>AC</strong> = Actual Cost</span>
                        <span><strong>SV</strong> = Schedule Variance</span>
                        <span><strong>CV</strong> = Cost Variance</span>
                        <span><strong>SPI</strong> = Schedule Performance Index</span>
                        <span><strong>CPI</strong> = Cost Performance Index</span>
                        <span><strong>EAC</strong> = Estimate at Completion</span>
                        <span><strong>VAC</strong> = Variance at Completion</span>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th colspan="2" class="px-3 py-2 bg-gray-50"></th>
                                <th colspan="4" class="px-3 py-2 text-center text-xs font-bold text-gray-700 uppercase bg-blue-50 border-l border-r border-gray-200">Values</th>
                                <th colspan="2" class="px-3 py-2 text-center text-xs font-bold text-gray-700 uppercase bg-amber-50 border-r border-gray-200">Variances</th>
                                <th colspan="2" class="px-3 py-2 text-center text-xs font-bold text-gray-700 uppercase bg-green-50 border-r border-gray-200">Indices</th>
                                <th colspan="2" class="px-3 py-2 text-center text-xs font-bold text-gray-700 uppercase bg-purple-50 border-r border-gray-200">Projections</th>
                                <th class="px-3 py-2 bg-gray-50"></th>
                            </tr>
                            <tr class="bg-gray-50">
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-20">Code</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase min-w-[160px]">Description</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-blue-700 uppercase w-24 bg-blue-50 border-l border-gray-200">BAC</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-blue-700 uppercase w-24 bg-blue-50">PV</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-blue-700 uppercase w-24 bg-blue-50">EV</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-blue-700 uppercase w-24 bg-blue-50 border-r border-gray-200">AC</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-amber-700 uppercase w-24 bg-amber-50">SV</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-amber-700 uppercase w-24 bg-amber-50 border-r border-gray-200">CV</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-green-700 uppercase w-16 bg-green-50">SPI</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-green-700 uppercase w-16 bg-green-50 border-r border-gray-200">CPI</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-purple-700 uppercase w-24 bg-purple-50">EAC</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-purple-700 uppercase w-24 bg-purple-50 border-r border-gray-200">VAC</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-16">% Done</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($rows as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-3 text-sm text-gray-600">{{ $row['code'] }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900">{{ $row['description'] }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right bg-blue-50/30 border-l border-gray-100">${{ number_format($row['bac'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right bg-blue-50/30">${{ number_format($row['pv'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right bg-blue-50/30">${{ number_format($row['ev'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right bg-blue-50/30 border-r border-gray-100">${{ number_format($row['ac'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-right bg-amber-50/30 font-medium {{ $row['sv'] < 0 ? 'text-red-600' : ($row['sv'] > 0 ? 'text-green-600' : 'text-gray-400') }}">
                                        ${{ number_format($row['sv'], 0) }}
                                    </td>
                                    <td class="px-3 py-3 text-sm text-right bg-amber-50/30 border-r border-gray-100 font-medium {{ $row['cv'] < 0 ? 'text-red-600' : ($row['cv'] > 0 ? 'text-green-600' : 'text-gray-400') }}">
                                        ${{ number_format($row['cv'], 0) }}
                                    </td>
                                    <td class="px-3 py-3 text-sm text-right bg-green-50/30 font-medium {{ $row['spi'] < 1 ? 'text-red-600' : ($row['spi'] > 1 ? 'text-green-600' : 'text-gray-900') }}">
                                        {{ number_format($row['spi'], 2) }}
                                    </td>
                                    <td class="px-3 py-3 text-sm text-right bg-green-50/30 border-r border-gray-100 font-medium {{ $row['cpi'] < 1 ? 'text-red-600' : ($row['cpi'] > 1 ? 'text-green-600' : 'text-gray-900') }}">
                                        {{ number_format($row['cpi'], 2) }}
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right bg-purple-50/30">${{ number_format($row['eac'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-right bg-purple-50/30 border-r border-gray-100 font-medium {{ $row['vac'] < 0 ? 'text-red-600' : ($row['vac'] > 0 ? 'text-green-600' : 'text-gray-400') }}">
                                        ${{ number_format($row['vac'], 0) }}
                                    </td>
                                    <td class="px-3 py-3 text-sm text-right">
                                        <span class="{{ $row['pct_complete'] >= 100 ? 'text-green-600 font-semibold' : 'text-gray-600' }}">{{ number_format($row['pct_complete'], 1) }}%</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="px-3 py-8 text-center text-gray-500">No data for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(!empty($rows))
                            <tfoot class="bg-gray-100">
                                <tr class="font-bold">
                                    <td colspan="2" class="px-3 py-3 text-sm text-gray-700 text-right">Totals</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right border-l border-gray-200">${{ number_format($totals['bac'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['pv'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['ev'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right border-r border-gray-200">${{ number_format($totals['ac'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-right {{ $totals['sv'] < 0 ? 'text-red-600' : 'text-green-600' }}">${{ number_format($totals['sv'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-right border-r border-gray-200 {{ $totals['cv'] < 0 ? 'text-red-600' : 'text-green-600' }}">${{ number_format($totals['cv'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-right {{ $totals['spi'] < 1 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($totals['spi'], 2) }}</td>
                                    <td class="px-3 py-3 text-sm text-right border-r border-gray-200 {{ $totals['cpi'] < 1 ? 'text-red-600' : 'text-green-600' }}">{{ number_format($totals['cpi'], 2) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['eac'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-right border-r border-gray-200 {{ $totals['vac'] < 0 ? 'text-red-600' : 'text-green-600' }}">${{ number_format($totals['vac'], 0) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
