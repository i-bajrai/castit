<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="cost-detail">
            {{ $project->name }}
        </x-project-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)">
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            @endif

            {{-- ==================== PERIOD NAVIGATION ==================== --}}
            @if($allPeriods->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="px-6 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <label for="period-selector" class="text-sm font-medium text-gray-700">Period:</label>
                            <select id="period-selector" onchange="window.location.href = this.value" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($allPeriods as $p)
                                    <option value="{{ route('projects.show', ['project' => $project, 'period' => $p->id]) }}"
                                        {{ $period && $period->id === $p->id ? 'selected' : '' }}>
                                        {{ $p->period_date->format('F Y') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            @if($isEditable)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Current Month &mdash; Editable
                                </span>
                            @elseif($period)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    {{ $period->period_date->format('M Y') }} &mdash; Read Only
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @elseif(!$project->start_date || !$project->end_date)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-12 text-center">
                    <p class="text-gray-500">No forecast periods yet. <a href="{{ route('projects.settings', $project) }}" class="text-indigo-600 hover:underline">Set project start and end dates in Settings</a> to auto-generate periods.</p>
                </div>
            @endif

            {{-- ==================== SETUP PROMPT ==================== --}}
            @if($accounts->isNotEmpty() && $accounts->every(fn($a) => $a->baseline_budget == 0 && $a->costPackages->isEmpty()))
                <div class="mb-6 bg-indigo-50 border border-indigo-200 rounded-lg p-4 flex items-center justify-between" data-testid="setup-budget-banner">
                    <p class="text-sm text-indigo-700">Your control accounts are set up but have no budgets yet. Set up your baseline budgets to get started.</p>
                    <a href="{{ route('projects.budget', $project) }}" class="shrink-0 ml-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                        Set Up Budget
                    </a>
                </div>
            @endif

            {{-- ==================== PROJECT SUMMARY CARDS ==================== --}}
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

            {{-- ==================== CONTROL ACCOUNTS ==================== --}}
            @if($accounts->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-12 text-center" data-testid="no-control-accounts">
                    <p class="text-gray-500">No control accounts yet. <a href="{{ route('projects.setup', $project) }}" class="text-indigo-600 hover:underline">Add control accounts</a> to get started.</p>
                </div>
            @endif

            @foreach($accounts as $account)
                @php
                    $caItemCount = 0;
                    foreach ($account->costPackages as $pkg) {
                        foreach ($pkg->lineItems as $li) {
                            if ($period && ! $li->existedInPeriod($period)) {
                                continue;
                            }
                            $caItemCount++;
                        }
                    }
                @endphp

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div>
                                <span class="font-semibold text-gray-900">{{ $account->code }}</span>
                                <span class="ml-2 text-sm text-gray-600">{{ $account->description }}</span>
                            </div>
                            <span class="text-sm {{ $caItemCount > 0 ? 'text-emerald-600 font-semibold' : 'text-gray-400' }}">{{ $caItemCount }} items</span>
                        </div>
                        <a href="{{ route('projects.control-accounts.forecast', ['project' => $project, 'controlAccount' => $account, 'period' => $period?->id]) }}"
                           class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 transition">
                            Enter data
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
