<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="executive-summary" :subtitle="$period ? 'Period: ' . $period->period_date->format('F Y') : null">
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
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Baseline Budget</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Approved Budget</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Monthly Cost</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Cost to Date</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Est. to Complete</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-indigo-600 uppercase w-28 bg-indigo-50">Est. Final Cost</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Last Month EFC</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">EFC Movement</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comments</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @php
                                $totalBaseline = 0;
                                $totalApproved = 0;
                                $totalMonthlyCost = 0;
                                $totalCtd = 0;
                                $totalEtc = 0;
                                $totalEfc = 0;
                                $totalLastEfc = 0;
                                $totalMovement = 0;
                            @endphp
                            @foreach($accounts as $account)
                                @php
                                    $forecast = $account->forecasts->first();
                                    $totalBaseline += $account->baseline_budget;
                                    $totalApproved += $account->approved_budget;
                                    if ($forecast) {
                                        $totalMonthlyCost += $forecast->monthly_cost;
                                        $totalCtd += $forecast->cost_to_date;
                                        $totalEtc += $forecast->estimate_to_complete;
                                        $totalEfc += $forecast->estimated_final_cost;
                                        $totalLastEfc += $forecast->last_month_efc;
                                        $totalMovement += $forecast->efc_movement;
                                    }
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $account->phase }}</td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-medium text-gray-900">{{ $account->code }}</p>
                                        <p class="text-xs text-gray-500">{{ $account->description }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $account->category }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($account->baseline_budget, 0) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($account->approved_budget, 0) }}</td>
                                    @if($forecast)
                                        <td class="px-4 py-3 text-sm text-right {{ $forecast->monthly_cost < 0 ? 'text-red-600' : 'text-gray-900' }}">
                                            ${{ number_format($forecast->monthly_cost, 0) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($forecast->cost_to_date, 0) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($forecast->estimate_to_complete, 0) }}</td>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right bg-indigo-50/50">${{ number_format($forecast->estimated_final_cost, 0) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($forecast->last_month_efc, 0) }}</td>
                                        <td class="px-4 py-3 text-sm text-right {{ $forecast->efc_movement != 0 ? ($forecast->efc_movement > 0 ? 'text-red-600 font-medium' : 'text-green-600 font-medium') : 'text-gray-900' }}">
                                            @if($forecast->efc_movement != 0)
                                                ${{ number_format($forecast->efc_movement, 0) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500 max-w-sm">
                                            @if($forecast->monthly_comments)
                                                <div x-data="{ expanded: false }">
                                                    <p x-show="!expanded" class="truncate">{{ Str::limit($forecast->monthly_comments, 80) }}</p>
                                                    <p x-show="expanded" class="whitespace-pre-line">{{ $forecast->monthly_comments }}</p>
                                                    @if(strlen($forecast->monthly_comments) > 80)
                                                        <button @click="expanded = !expanded" class="text-indigo-600 text-xs mt-1 hover:underline" x-text="expanded ? 'Show less' : 'Show more'"></button>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    @else
                                        <td colspan="7" class="px-4 py-3 text-sm text-gray-400 text-center">No forecast data</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-100">
                            <tr class="font-bold">
                                <td colspan="3" class="px-4 py-3 text-sm text-gray-700 text-right">Totals</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($totalBaseline, 0) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($totalApproved, 0) }}</td>
                                <td class="px-4 py-3 text-sm text-right {{ $totalMonthlyCost < 0 ? 'text-red-600' : 'text-gray-900' }}">${{ number_format($totalMonthlyCost, 0) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($totalCtd, 0) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($totalEtc, 0) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($totalEfc, 0) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 text-right">${{ number_format($totalLastEfc, 0) }}</td>
                                <td class="px-4 py-3 text-sm text-right {{ $totalMovement > 0 ? 'text-red-600' : 'text-gray-900' }}">${{ number_format($totalMovement, 0) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
