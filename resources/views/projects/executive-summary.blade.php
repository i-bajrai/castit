<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="reports" :breadcrumbs="[['route' => route('projects.reports', $project), 'label' => 'Reports']]">
            Executive Summary - {{ $project->name }}
        </x-project-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-20">Phase</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Control Account</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-28">Category</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Original Budget</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-blue-600 uppercase w-28 bg-blue-50">Prev FCAC</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-green-600 uppercase w-28 bg-green-50">Cost to Date</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-amber-600 uppercase w-28 bg-amber-50">Cost to Complete</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-indigo-600 uppercase w-28 bg-indigo-50">FCAC</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Variance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($accounts as $account)
                                @php
                                    $caOriginal = 0;
                                    $caPrevFcac = 0;
                                    $caCtd = 0;
                                    $caCtc = 0;
                                    $caFcac = 0;
                                    $caVariance = 0;
                                    foreach ($account->costPackages as $pkg) {
                                        foreach ($pkg->lineItems as $item) {
                                            if ($period && ! $item->existedInPeriod($period)) {
                                                continue;
                                            }
                                            $caOriginal += (float) $item->original_amount;
                                            $itemCtd = (float) $item->forecasts->sum('period_amount');
                                            $caCtd += $itemCtd;
                                            $currentForecast = $period
                                                ? $item->forecasts->firstWhere('forecast_period_id', $period->id)
                                                : null;
                                            $itemFcac = $currentForecast ? (float) $currentForecast->fcac_amount : 0.0;
                                            $caFcac += $itemFcac;
                                            $caCtc += $itemFcac - $itemCtd;
                                            if (isset($previousPeriod) && $previousPeriod) {
                                                $prevF = $item->forecasts->firstWhere('forecast_period_id', $previousPeriod->id);
                                                $caPrevFcac += $prevF ? (float) $prevF->fcac_amount : 0.0;
                                            }
                                        }
                                    }
                                    $caVariance = $caFcac - $caPrevFcac;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $account->phase }}</td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-gray-900">{{ $account->code }}</p>
                                        <p class="text-xs text-gray-500">{{ $account->description }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $account->category }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($caOriginal, 0) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right bg-blue-50/50">${{ number_format($caPrevFcac, 0) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right bg-green-50/50">${{ number_format($caCtd, 0) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right bg-amber-50/50">${{ number_format($caCtc, 0) }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right bg-indigo-50/50">${{ number_format($caFcac, 0) }}</td>
                                    <td class="px-4 py-3 text-sm text-right {{ $caVariance < 0 ? 'text-red-600 font-medium' : ($caVariance > 0 ? 'text-green-600 font-medium' : 'text-gray-900') }}">
                                        @if($caVariance != 0)
                                            ${{ number_format($caVariance, 0) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-100">
                            <tr class="font-bold">
                                <td colspan="3" class="px-4 py-3 text-sm text-gray-700 text-right">Totals</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['original_budget'], 0) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['previous_fcac'], 0) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['ctd'], 0) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['ctc'], 0) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($totals['fcac'], 0) }}</td>
                                <td class="px-4 py-3 text-sm text-right {{ $totals['variance'] < 0 ? 'text-red-600' : 'text-gray-900' }}">${{ number_format($totals['variance'], 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
