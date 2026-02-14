<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="settings" :breadcrumbs="[['route' => route('projects.settings', $project), 'label' => 'Settings']]">
            Unassigned Items - {{ $project->name }}
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
            @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            @endif

            {{-- Summary --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $unassignedItems->count() }} unassigned line item(s)</h3>
                            <p class="text-sm text-gray-600 mt-1">These items were created during import and need to be assigned to an existing cost package, or merged into an existing line item.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Reassignment Form --}}
            <form method="POST" action="{{ route('projects.unassigned.reassign', $project) }}"
                x-data="reassignForm()"
                x-ref="form">
                @csrf

                <div class="space-y-3">
                    @foreach($unassignedItems as $index => $item)
                        @php
                            $itemSuggestions = $suggestions[$item->id] ?? [];
                            $bestMatch = ! empty($itemSuggestions) ? $itemSuggestions[0] : null;
                        @endphp
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                             x-data="{
                                action: '{{ $bestMatch && $bestMatch['score'] >= 60 ? 'merge' : 'move' }}',
                                mergeTarget: '{{ $bestMatch && $bestMatch['score'] >= 60 ? $bestMatch['id'] : '' }}'
                             }">
                            <div class="p-4">
                                <input type="hidden" name="operations[{{ $index }}][line_item_id]" value="{{ $item->id }}">
                                <input type="hidden" name="operations[{{ $index }}][action]" :value="action">

                                <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                                    {{-- Item Info --}}
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $item->description }}</p>
                                        @php
                                            $periodsWithData = $item->forecasts->filter(fn($f) => (float) $f->period_qty !== 0.0)->count();
                                            $totalPeriods = $item->forecasts->count();
                                        @endphp
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            {{ $periodsWithData }} of {{ $totalPeriods }} period(s) with data
                                        </p>

                                        {{-- Close Match Suggestions --}}
                                        @if(! empty($itemSuggestions))
                                            <div class="mt-2 flex flex-wrap gap-1.5">
                                                @foreach($itemSuggestions as $suggestion)
                                                    <button type="button"
                                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs cursor-pointer transition-colors
                                                                {{ $suggestion['score'] >= 60 ? 'bg-amber-100 text-amber-800 hover:bg-amber-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                                                            x-on:click="action = 'merge'; mergeTarget = '{{ $suggestion['id'] }}'; $nextTick(() => $refs.mergeSelect{{ $index }}.value = '{{ $suggestion['id'] }}')"
                                                            title="{{ $suggestion['ca_code'] }} > {{ $suggestion['package_name'] }} ({{ $suggestion['score'] }}% match)">
                                                        @if($suggestion['score'] >= 60)
                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                                                        @endif
                                                        {{ Str::limit($suggestion['description'], 40) }}
                                                        <span class="font-semibold">{{ $suggestion['score'] }}%</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Action Toggle --}}
                                    <div class="flex items-center gap-2 shrink-0">
                                        <select x-model="action"
                                                class="rounded-md border-gray-300 text-sm font-medium focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="move">Move to package</option>
                                            <option value="merge">Merge into line item</option>
                                        </select>
                                    </div>

                                    {{-- Target: Cost Package (for move) --}}
                                    <div x-show="action === 'move'" class="shrink-0 w-full lg:w-80">
                                        <select name="operations[{{ $index }}][target_package_id]"
                                                class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                            <option value="">Select cost package...</option>
                                            @foreach($controlAccounts as $ca)
                                                <optgroup label="{{ $ca->code }} - {{ $ca->description }}">
                                                    @foreach($ca->costPackages as $pkg)
                                                        <option value="{{ $pkg->id }}">{{ $pkg->name }} ({{ $pkg->lineItems->count() }} items)</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Target: Line Item (for merge) --}}
                                    <div x-show="action === 'merge'" class="shrink-0 w-full lg:w-80">
                                        <select name="operations[{{ $index }}][merge_into_id]"
                                                x-ref="mergeSelect{{ $index }}"
                                                class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                            <option value="">Select line item...</option>
                                            @foreach($controlAccounts as $ca)
                                                @foreach($ca->costPackages as $pkg)
                                                    <optgroup label="{{ $ca->code }} > {{ $pkg->name }}">
                                                        @foreach($pkg->lineItems as $li)
                                                            <option value="{{ $li->id }}" {{ $bestMatch && $bestMatch['id'] === $li->id ? 'selected' : '' }}>{{ $li->description }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Submit --}}
                <div class="mt-6 flex justify-end">
                    <x-primary-button data-testid="reassign-items-button">
                        Reassign Selected Items
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function reassignForm() {
            return {};
        }
    </script>
</x-app-layout>
