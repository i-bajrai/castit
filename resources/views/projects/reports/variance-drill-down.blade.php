<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="reports" :breadcrumbs="[['route' => route('projects.reports', $project), 'label' => 'Reports']]">
            Variance Drill-Down - {{ $project->name }}
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
                                    <option value="{{ route('projects.variance-drill-down', ['project' => $project, 'period' => $p->id]) }}"
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-24">CA Code</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Package</th>
                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase min-w-[200px]">Description</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Original Amount</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">FCAC</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Variance ($)</th>
                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-20">Variance (%)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($items as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-3 text-sm text-gray-600">{{ $item['ca_code'] }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-600">{{ $item['package_name'] }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900">{{ $item['description'] }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($item['original_amount'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($item['fcac_amount'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-right font-medium {{ $item['variance'] < 0 ? 'text-red-600' : ($item['variance'] > 0 ? 'text-green-600' : 'text-gray-400') }}">
                                        @if($item['variance'] != 0)
                                            ${{ number_format($item['variance'], 0) }}
                                        @else
                                            0
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-sm text-right {{ $item['variance_pct'] < 0 ? 'text-red-600' : ($item['variance_pct'] > 0 ? 'text-green-600' : 'text-gray-400') }}">
                                        {{ number_format($item['variance_pct'], 1) }}%
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3 py-8 text-center text-gray-500">No data for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(!empty($items))
                            <tfoot class="bg-gray-100">
                                <tr class="font-bold">
                                    <td colspan="3" class="px-3 py-3 text-sm text-gray-700 text-right">Totals</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['original_amount'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['fcac_amount'], 0) }}</td>
                                    <td class="px-3 py-3 text-sm text-right font-bold {{ $totals['variance'] < 0 ? 'text-red-600' : ($totals['variance'] > 0 ? 'text-green-600' : 'text-gray-900') }}">
                                        ${{ number_format($totals['variance'], 0) }}
                                    </td>
                                    <td class="px-3 py-3 text-sm text-right {{ $totals['original_amount'] > 0 ? ($totals['variance'] / $totals['original_amount'] * 100 < 0 ? 'text-red-600' : 'text-green-600') : '' }}">
                                        @if($totals['original_amount'] > 0)
                                            {{ number_format($totals['variance'] / $totals['original_amount'] * 100, 1) }}%
                                        @endif
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
