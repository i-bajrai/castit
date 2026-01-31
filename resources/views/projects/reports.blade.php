<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="reports">
            Reports - {{ $project->name }}
        </x-project-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <a href="{{ route('projects.executive-summary', $project) }}"
                   class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition border border-transparent hover:border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Executive Summary</h3>
                    <p class="mt-2 text-sm text-gray-500">High-level overview of control account forecasts and variances.</p>
                </a>

                <a href="{{ route('projects.cost-analysis', $project) }}"
                   class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition border border-transparent hover:border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Detailed Value and Cost Analysis</h3>
                    <p class="mt-2 text-sm text-gray-500">Budget values, cost to date, estimates, and monthly movements per control account.</p>
                </a>

                <a href="{{ route('projects.line-item-progress', $project) }}"
                   class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition border border-transparent hover:border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Line Item Progress</h3>
                    <p class="mt-2 text-sm text-gray-500">Detailed progress of each line item showing quantities, costs, and % complete.</p>
                </a>

                <a href="{{ route('projects.period-movement', $project) }}"
                   class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition border border-transparent hover:border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Period-over-Period Movement</h3>
                    <p class="mt-2 text-sm text-gray-500">Month-to-month changes in cost to date, estimate to complete, and forecast at completion.</p>
                </a>

                <a href="{{ route('projects.cash-flow', $project) }}"
                   class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition border border-transparent hover:border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">S-Curve / Cash Flow</h3>
                    <p class="mt-2 text-sm text-gray-500">Cumulative spend over time with planned vs actual S-curve chart.</p>
                </a>

                <a href="{{ route('projects.variance-drill-down', $project) }}"
                   class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition border border-transparent hover:border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Variance Drill-Down</h3>
                    <p class="mt-2 text-sm text-gray-500">Line items sorted by largest variance to quickly identify cost overruns.</p>
                </a>

                <a href="{{ route('projects.earned-value', $project) }}"
                   class="block bg-white shadow-sm sm:rounded-lg p-6 hover:shadow-md transition border border-transparent hover:border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Earned Value Summary</h3>
                    <p class="mt-2 text-sm text-gray-500">EVM metrics including SPI, CPI, EAC, and VAC per control account.</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
