<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="reports" :breadcrumbs="[['route' => route('projects.reports', $project), 'label' => 'Reports']]">
            Detailed Value and Cost Analysis - {{ $project->name }}
        </x-project-header>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto sm:px-6 lg:px-8" style="max-width: 80%">

            {{-- Period Selector --}}
            @if($allPeriods->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="px-6 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <label for="period-selector" class="text-sm font-medium text-gray-700">Period:</label>
                            <select id="period-selector" onchange="window.location.href = this.value" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($allPeriods as $p)
                                    <option value="{{ route('projects.cost-analysis', ['project' => $project, 'period' => $p->id]) }}"
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
                                    &mdash; comparing to {{ $previousPeriod->period_date->format('M Y') }}
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
                            {{-- Group header row --}}
                            <tr>
                                <th colspan="2" class="px-3 py-2 bg-gray-50"></th>
                                <th colspan="4" class="px-3 py-2 text-center text-xs font-bold text-gray-700 uppercase bg-blue-50 border-l border-r border-gray-200">Value</th>
                                <th colspan="7" class="px-3 py-2 text-center text-xs font-bold text-gray-700 uppercase bg-green-50 border-r border-gray-200">Cost</th>
                            </tr>
                            {{-- Column headers --}}
                            <tr class="bg-gray-50">
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-16">Phase</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase min-w-[200px]">Control Account | Description</th>
                                {{-- VALUE columns --}}
                                <th class="px-3 py-3 text-right text-xs font-medium text-blue-700 uppercase w-28 bg-blue-50 border-l border-gray-200">Baseline Budget</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-blue-700 uppercase w-28 bg-blue-50">Approved Budget</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-blue-700 uppercase w-28 bg-blue-50">Last Month Approved Budget</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-blue-700 uppercase w-28 bg-blue-50 border-r border-gray-200">Month Budget Movement</th>
                                {{-- COST columns --}}
                                <th class="px-3 py-3 text-right text-xs font-medium text-green-700 uppercase w-24 bg-green-50">Monthly Cost</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-green-700 uppercase w-28 bg-green-50">Cost To Date</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-green-700 uppercase w-28 bg-green-50">Estimate To Complete</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-green-700 uppercase w-28 bg-green-50">Estimated Final Cost</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-green-700 uppercase w-28 bg-green-50">Last Month EFC</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-green-700 uppercase w-28 bg-green-50 border-r border-gray-200">Monthly EFC Movement</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase min-w-[200px]">Monthly Comments</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($rows as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-3 text-sm text-gray-600">{{ $row['phase'] }}</td>
                                    <td class="px-3 py-3">
                                        <p class="text-sm font-medium text-gray-900">{{ $row['code'] }} - {{ $row['description'] }}</p>
                                        @if($row['category'])
                                            <p class="text-xs text-gray-400">{{ $row['category'] }}</p>
                                        @endif
                                    </td>
                                    {{-- VALUE --}}
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right bg-blue-50/30 border-l border-gray-100">
                                        ${{ number_format($row['baseline_budget'], 0) }}
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right bg-blue-50/30">
                                        ${{ number_format($row['approved_budget'], 0) }}
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right bg-blue-50/30">
                                        ${{ number_format($row['last_month_approved_budget'], 0) }}
                                    </td>
                                    <td class="px-3 py-3 text-sm text-right bg-blue-50/30 border-r border-gray-100 {{ $row['month_budget_movement'] != 0 ? ($row['month_budget_movement'] < 0 ? 'text-red-600 font-medium' : 'text-green-600 font-medium') : 'text-gray-900' }}">
                                        @if($row['month_budget_movement'] != 0)
                                            ${{ number_format($row['month_budget_movement'], 0) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                    {{-- COST --}}
                                    <td class="px-3 py-3 text-sm text-right bg-green-50/30 {{ $row['monthly_cost'] != 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                        @if($row['monthly_cost'] != 0)
                                            ${{ number_format($row['monthly_cost'], 0) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right bg-green-50/30">
                                        ${{ number_format($row['cost_to_date'], 0) }}
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right bg-green-50/30">
                                        ${{ number_format($row['estimate_to_complete'], 0) }}
                                    </td>
                                    <td class="px-3 py-3 text-sm font-semibold text-gray-900 text-right bg-green-50/30">
                                        ${{ number_format($row['estimated_final_cost'], 0) }}
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right bg-green-50/30">
                                        ${{ number_format($row['last_month_efc'], 0) }}
                                    </td>
                                    <td class="px-3 py-3 text-sm text-right bg-green-50/30 border-r border-gray-100 {{ $row['monthly_efc_movement'] != 0 ? ($row['monthly_efc_movement'] > 0 ? 'text-red-600 font-medium' : 'text-green-600 font-medium') : 'text-gray-900' }}">
                                        @if($row['monthly_efc_movement'] != 0)
                                            ${{ number_format($row['monthly_efc_movement'], 0) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-600 max-w-xs">
                                        @if($row['monthly_comments'])
                                            <div class="whitespace-pre-line text-xs">{{ $row['monthly_comments'] }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-100">
                            <tr class="font-bold">
                                <td colspan="2" class="px-3 py-3 text-sm text-gray-700 text-right">Totals</td>
                                <td class="px-3 py-3 text-sm text-gray-900 text-right border-l border-gray-200">${{ number_format($totals['baseline_budget'], 0) }}</td>
                                <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['approved_budget'], 0) }}</td>
                                <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['last_month_approved_budget'], 0) }}</td>
                                <td class="px-3 py-3 text-sm text-right border-r border-gray-200 {{ $totals['month_budget_movement'] != 0 ? ($totals['month_budget_movement'] < 0 ? 'text-red-600' : 'text-green-600') : 'text-gray-900' }}">
                                    ${{ number_format($totals['month_budget_movement'], 0) }}
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['monthly_cost'], 0) }}</td>
                                <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['cost_to_date'], 0) }}</td>
                                <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['estimate_to_complete'], 0) }}</td>
                                <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['estimated_final_cost'], 0) }}</td>
                                <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['last_month_efc'], 0) }}</td>
                                <td class="px-3 py-3 text-sm text-right border-r border-gray-200 {{ $totals['monthly_efc_movement'] != 0 ? ($totals['monthly_efc_movement'] > 0 ? 'text-red-600' : 'text-green-600') : 'text-gray-900' }}">
                                    ${{ number_format($totals['monthly_efc_movement'], 0) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
