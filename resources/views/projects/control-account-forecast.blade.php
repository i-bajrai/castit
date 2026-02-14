<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="cost-detail" :breadcrumbs="[
            ['label' => $project->name, 'route' => route('projects.show', $project)],
        ]">
            {{ $account->code }} — {{ $account->description }}
        </x-project-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- ==================== PERIOD NAVIGATION ==================== --}}
            @if($allPeriods->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="px-6 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <label for="period-selector" class="text-sm font-medium text-gray-700">Period:</label>
                            <select id="period-selector" onchange="window.location.href = this.value" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($allPeriods as $p)
                                    <option value="{{ route('projects.control-accounts.forecast', ['project' => $project, 'controlAccount' => $account, 'period' => $p->id]) }}"
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
            @endif

            {{-- ==================== COST PACKAGES & LINE ITEMS ==================== --}}
            @if($account->costPackages->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                    <p class="text-gray-500">No cost packages in this control account. <a href="{{ route('projects.control-accounts.line-items', [$project, $account]) }}" class="text-indigo-600 hover:underline">Manage line items</a>.</p>
                </div>
            @else
                @foreach($account->costPackages as $package)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        {{-- Package Header --}}
                        <div class="px-6 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="font-semibold text-gray-900">{{ $package->name }}</span>
                                @if($package->item_no)
                                    <span class="text-sm text-gray-500">({{ $package->item_no }})</span>
                                @endif
                                <span class="text-xs text-gray-400">{{ $package->lineItems->count() }} items</span>
                            </div>
                        </div>

                        @if($package->lineItems->isEmpty())
                            <div class="px-6 py-4 text-center text-gray-500 text-sm">
                                No line items in this package.
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-16">Item</th>
                                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                            <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase w-14">UoM</th>
                                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-20 bg-gray-100">Orig Qty</th>
                                            <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-20 bg-gray-100">Orig Rate</th>
                                            <th class="px-3 py-3 text-right text-xs font-medium text-green-600 uppercase w-24 bg-green-50">This Month Qty</th>
                                            <th class="px-3 py-3 text-right text-xs font-medium text-green-600 uppercase w-24 bg-green-50">Rate</th>
                                            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-40">Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($package->lineItems as $item)
                                            @php
                                                $forecast = $item->forecasts->first();
                                                $effectiveRate = ($forecast && (float) $forecast->period_rate > 0)
                                                    ? (float) $forecast->period_rate
                                                    : (float) $item->original_rate;
                                                $rateChanged = $effectiveRate != (float) $item->original_rate;
                                            @endphp
                                            @if($isEditable)
                                                <tr class="hover:bg-gray-50"
                                                    x-data="{
                                                        periodQty: {{ $forecast->period_qty ?? 0 }},
                                                        periodRate: {{ $effectiveRate }},
                                                    }">
                                                    <td class="px-3 py-2 text-sm text-gray-600">{{ $item->item_no }}</td>
                                                    <td class="px-3 py-2 text-sm text-gray-900">{{ $item->description }}</td>
                                                    <td class="px-3 py-2 text-sm text-gray-500 text-center">{{ $item->unit_of_measure }}</td>
                                                    <td class="px-3 py-2 text-sm text-gray-900 text-right bg-gray-50">{{ number_format($item->original_qty, 1) }}</td>
                                                    <td class="px-3 py-2 text-sm text-gray-900 text-right bg-gray-50">${{ number_format($item->original_rate, 2) }}</td>

                                                    {{-- This Month Qty (EDITABLE) --}}
                                                    <td class="px-1 py-1 bg-green-50/30"
                                                        x-data="{
                                                            editQty: periodQty,
                                                            editRate: periodRate,
                                                            saving: false,
                                                            error: false,
                                                            async savePeriodData() {
                                                                @if($forecast?->id)
                                                                    this.saving = true;
                                                                    this.error = false;
                                                                    try {
                                                                        const res = await fetch('{{ route('projects.forecasts.update-ctd-qty', [$project, $forecast]) }}', {
                                                                            method: 'PATCH',
                                                                            headers: {
                                                                                'Content-Type': 'application/json',
                                                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                            },
                                                                            body: JSON.stringify({ period_qty: this.editQty, period_rate: this.editRate }),
                                                                        });
                                                                        if (!res.ok) throw new Error();
                                                                        periodQty = this.editQty;
                                                                        periodRate = this.editRate;
                                                                        this.$dispatch('close');
                                                                    } catch {
                                                                        this.error = true;
                                                                    } finally {
                                                                        this.saving = false;
                                                                    }
                                                                @else
                                                                    periodQty = this.editQty;
                                                                    periodRate = this.editRate;
                                                                    this.$dispatch('close');
                                                                @endif
                                                            }
                                                        }"
                                                        x-on:open-modal.window="if ($event.detail === 'period-qty-{{ $item->id }}') { editQty = periodQty; editRate = periodRate; }">
                                                        <button type="button"
                                                            x-on:click.prevent="$dispatch('open-modal', 'period-qty-{{ $item->id }}')"
                                                            class="w-full text-sm text-right px-2 py-1 rounded border border-gray-300 hover:border-green-400 hover:bg-green-50 transition"
                                                            x-text="periodQty">
                                                        </button>
                                                        <x-modal name="period-qty-{{ $item->id }}" :show="false" maxWidth="sm">
                                                            <div class="p-6">
                                                                <h2 class="text-lg font-medium text-gray-900 mb-1">This Month - {{ $item->description }}</h2>
                                                                <p class="text-sm text-gray-500 mb-4">Item {{ $item->item_no }}</p>
                                                                <div class="space-y-3">
                                                                    <div>
                                                                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                                                                        <input type="number" step="0.01"
                                                                            x-model.number="editQty"
                                                                            class="w-full text-sm border-gray-300 rounded-md focus:border-green-500 focus:ring-green-500">
                                                                    </div>
                                                                    <div>
                                                                        <label class="block text-sm font-medium text-gray-700 mb-1">Rate</label>
                                                                        <input type="number" step="0.01"
                                                                            x-model.number="editRate"
                                                                            x-on:keydown.enter.prevent="savePeriodData()"
                                                                            class="w-full text-sm border-gray-300 rounded-md focus:border-green-500 focus:ring-green-500">
                                                                    </div>
                                                                </div>
                                                                <p x-show="error" x-cloak class="mt-2 text-sm text-red-600">Failed to save. Please try again.</p>
                                                                <div class="mt-4 flex justify-end gap-2">
                                                                    <span x-show="saving" class="text-sm text-gray-400 self-center">Saving...</span>
                                                                    <x-primary-button type="button" x-on:click="savePeriodData()" x-bind:disabled="saving">Save</x-primary-button>
                                                                </div>
                                                            </div>
                                                        </x-modal>
                                                    </td>

                                                    {{-- Rate (display, highlighted if changed) --}}
                                                    <td class="px-3 py-2 text-sm text-right {{ $rateChanged ? 'bg-amber-50 text-amber-700 font-medium' : 'bg-green-50/30 text-gray-900' }}"
                                                        x-text="'$' + periodRate.toFixed(2)">
                                                    </td>

                                                    {{-- Comments (EDITABLE) --}}
                                                    <td class="px-1 py-1"
                                                        x-data="{
                                                            comment: '{{ str_replace("'", "\\'", $forecast->comments ?? '') }}',
                                                            saving: false,
                                                            error: false,
                                                            async saveComment() {
                                                                @if($forecast?->id)
                                                                    this.saving = true;
                                                                    this.error = false;
                                                                    try {
                                                                        const res = await fetch('{{ route('projects.forecasts.update-comment', [$project, $forecast]) }}', {
                                                                            method: 'PATCH',
                                                                            headers: {
                                                                                'Content-Type': 'application/json',
                                                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                                            },
                                                                            body: JSON.stringify({ comments: this.comment }),
                                                                        });
                                                                        if (!res.ok) throw new Error();
                                                                        this.$dispatch('close');
                                                                    } catch {
                                                                        this.error = true;
                                                                    } finally {
                                                                        this.saving = false;
                                                                    }
                                                                @else
                                                                    this.$dispatch('close');
                                                                @endif
                                                            }
                                                        }">
                                                        <button type="button"
                                                            x-on:click.prevent="$dispatch('open-modal', 'comment-{{ $item->id }}')"
                                                            class="w-full text-sm text-left px-2 py-1 rounded border border-gray-300 hover:border-indigo-400 hover:bg-indigo-50 transition truncate"
                                                            :class="comment ? 'text-gray-900' : 'text-gray-400'"
                                                            x-text="comment || 'Add comment...'">
                                                        </button>
                                                        <x-modal name="comment-{{ $item->id }}" :show="false" maxWidth="lg">
                                                            <div class="p-6">
                                                                <h2 class="text-lg font-medium text-gray-900 mb-1">Comment - {{ $item->description }}</h2>
                                                                <p class="text-sm text-gray-500 mb-4">Item {{ $item->item_no }}</p>
                                                                <textarea
                                                                    x-model="comment"
                                                                    rows="4"
                                                                    class="w-full text-sm border-gray-300 rounded-md focus:border-indigo-500 focus:ring-indigo-500"
                                                                    placeholder="Enter comment..."></textarea>
                                                                <p x-show="error" x-cloak class="mt-2 text-sm text-red-600">Failed to save. Please try again.</p>
                                                                <div class="mt-4 flex justify-end gap-2">
                                                                    <span x-show="saving" class="text-sm text-gray-400 self-center">Saving...</span>
                                                                    <x-primary-button type="button" x-on:click="saveComment()" x-bind:disabled="saving">Save</x-primary-button>
                                                                </div>
                                                            </div>
                                                        </x-modal>
                                                    </td>
                                                </tr>
                                            @else
                                                {{-- READ-ONLY --}}
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-3 py-2 text-sm text-gray-600">{{ $item->item_no }}</td>
                                                    <td class="px-3 py-2 text-sm text-gray-900">{{ $item->description }}</td>
                                                    <td class="px-3 py-2 text-sm text-gray-500 text-center">{{ $item->unit_of_measure }}</td>
                                                    <td class="px-3 py-2 text-sm text-gray-900 text-right bg-gray-50">{{ number_format($item->original_qty, 1) }}</td>
                                                    <td class="px-3 py-2 text-sm text-gray-900 text-right bg-gray-50">${{ number_format($item->original_rate, 2) }}</td>
                                                    <td class="px-3 py-2 text-sm text-gray-900 text-right bg-green-50/50">{{ number_format($forecast->period_qty ?? 0, 1) }}</td>
                                                    <td class="px-3 py-2 text-sm text-right {{ $rateChanged ? 'bg-amber-50 text-amber-700 font-medium' : 'bg-green-50/50 text-gray-900' }}">${{ number_format($effectiveRate, 2) }}</td>
                                                    <td class="px-3 py-2 text-sm text-gray-500 max-w-xs truncate">{{ $forecast->comments ?? '' }}</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif

        </div>
    </div>
</x-app-layout>
