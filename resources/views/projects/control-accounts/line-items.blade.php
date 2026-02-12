<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="cost-detail" :breadcrumbs="[['route' => route('projects.show', $project), 'label' => 'Cost Detail']]">
            {{ $controlAccount->code }} - {{ $controlAccount->description }}
        </x-project-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 rounded-md bg-red-50 p-4">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            @endif

            {{-- Control Account Header --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $controlAccount->code }}</h3>
                            <p class="text-sm text-gray-600">{{ $controlAccount->description }}</p>
                            @if($controlAccount->category)
                                <p class="text-xs text-gray-400 mt-1">{{ $controlAccount->category }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-6 text-sm">
                            <div class="text-right">
                                <span class="text-gray-500">Baseline Budget</span>
                                <p class="font-semibold text-gray-900">${{ number_format($controlAccount->baseline_budget, 2) }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-gray-500">Approved Budget</span>
                                <p class="font-semibold text-gray-900">${{ number_format($controlAccount->approved_budget, 2) }}</p>
                            </div>
                            @php
                                $totalOriginal = $costPackages->flatMap->lineItems->sum('original_amount');
                                $hasLineItems = $costPackages->flatMap->lineItems->isNotEmpty();
                            @endphp
                            <div class="text-right">
                                <span class="text-gray-500">Line Item Total</span>
                                <p class="font-semibold text-gray-900">${{ number_format($totalOriginal, 2) }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Action Bar --}}
                    <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                        <button
                            type="button"
                            x-data
                            x-on:click="$dispatch('open-modal', 'create-cost-package')"
                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                        >
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Cost Package
                        </button>
                        <button
                            type="button"
                            x-data
                            @if(!$hasLineItems) x-on:click="$dispatch('open-modal', 'import-csv')" @endif
                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-sm font-medium {{ $hasLineItems ? 'text-gray-400 bg-gray-50 cursor-not-allowed' : 'text-indigo-600 bg-white hover:bg-indigo-50' }}"
                            @if($hasLineItems) disabled title="Cannot import when line items already exist" @endif
                        >
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            Import CSV
                        </button>
                        @if(!$hasLineItems)
                        <a
                            href="#"
                            x-data
                            x-on:click.prevent="
                                const code = @js($controlAccount->code);
                                let csv = 'control_account_code,package_name,item_no,description,unit_of_measure,qty,rate,amount\n';
                                csv += code + ',Package 01,001,Sample item description,EA,10,100.00,1000.00\n';
                                csv += code + ',Package 01,002,Another sample item,LM,25,50.00,1250.00\n';
                                const blob = new Blob([csv], { type: 'text/csv' });
                                const a = document.createElement('a');
                                a.href = URL.createObjectURL(blob);
                                a.download = code + '-line-items-sample.csv';
                                a.click();
                                URL.revokeObjectURL(a.href);
                            "
                            class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 font-medium"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Sample CSV
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Cost Packages & Line Items --}}
            @if($costPackages->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No cost packages yet</h3>
                        <p class="mt-1 text-sm text-gray-500">Add cost packages and line items to this control account.</p>
                        <div class="mt-4 flex items-center justify-center gap-3">
                            <button
                                type="button"
                                x-data
                                x-on:click="$dispatch('open-modal', 'create-cost-package')"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                            >
                                Add Cost Package
                            </button>
                            <button
                                type="button"
                                x-data
                                x-on:click="$dispatch('open-modal', 'import-csv')"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Import CSV
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($costPackages as $package)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            {{-- Package Header --}}
                            <div class="px-6 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="font-semibold text-gray-900">{{ $package->name }}</span>
                                    @if($package->item_no)
                                        <span class="text-sm text-gray-500">({{ $package->item_no }})</span>
                                    @endif
                                    <span class="text-xs text-gray-400">{{ $package->lineItems->count() }} items</span>
                                    @php $pkgTotal = $package->lineItems->sum('original_amount'); @endphp
                                    <span class="text-xs text-gray-500 font-medium">${{ number_format($pkgTotal, 2) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="#" x-data x-on:click.prevent="$dispatch('open-modal', 'add-line-item-{{ $package->id }}')" class="inline-flex items-center text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add Item
                                    </a>
                                    <a href="#" x-data x-on:click.prevent="$dispatch('open-modal', 'edit-cost-package-{{ $package->id }}')" class="text-xs text-gray-400 hover:text-gray-600">Edit</a>
                                    <a href="#" x-data x-on:click.prevent="$dispatch('open-modal', 'delete-cost-package-{{ $package->id }}')" class="text-xs text-red-400 hover:text-red-600">Delete</a>
                                </div>
                            </div>

                            {{-- Line Items Table --}}
                            @if($package->lineItems->isEmpty())
                                <div class="px-6 py-4 text-center text-gray-500 text-sm">
                                    No line items. <a href="#" x-data x-on:click.prevent="$dispatch('open-modal', 'add-line-item-{{ $package->id }}')" class="text-indigo-600 hover:underline">Add a line item</a>.
                                </div>
                            @else
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-16">Item</th>
                                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-16">UoM</th>
                                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-24">Orig Qty</th>
                                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-24">Orig Rate</th>
                                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-28">Orig Amount</th>
                                                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-24">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach($package->lineItems as $item)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-3 py-2 text-sm text-gray-500">{{ $item->item_no }}</td>
                                                    <td class="px-3 py-2 text-sm text-gray-900">{{ $item->description }}</td>
                                                    <td class="px-3 py-2 text-sm text-gray-500">{{ $item->unit_of_measure }}</td>
                                                    <td class="px-3 py-2 text-sm text-gray-900 text-right">{{ number_format($item->original_qty, 1) }}</td>
                                                    <td class="px-3 py-2 text-sm text-gray-900 text-right">${{ number_format($item->original_rate, 2) }}</td>
                                                    <td class="px-3 py-2 text-sm text-gray-900 text-right font-medium">${{ number_format($item->original_amount, 2) }}</td>
                                                    <td class="px-3 py-2 text-right">
                                                        <a href="#" x-data x-on:click.prevent="$dispatch('open-modal', 'edit-line-item-{{ $item->id }}')" class="text-xs text-indigo-600 hover:text-indigo-800">Edit</a>
                                                        <a href="#" x-data x-on:click.prevent="$dispatch('open-modal', 'delete-line-item-{{ $item->id }}')" class="text-xs text-red-500 hover:text-red-700 ml-2">Delete</a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ==================== MODALS ==================== --}}

    {{-- Create Cost Package Modal --}}
    <x-modal name="create-cost-package" :show="false" maxWidth="lg">
        <form method="POST" action="{{ route('projects.cost-packages.store', $project) }}" class="p-6">
            @csrf
            <input type="hidden" name="control_account_id" value="{{ $controlAccount->id }}">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Add Cost Package</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="create_pkg_item_no" value="Item No" />
                    <x-text-input id="create_pkg_item_no" name="item_no" type="text" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="create_pkg_sort_order" value="Sort Order" />
                    <x-text-input id="create_pkg_sort_order" name="sort_order" type="number" class="mt-1 block w-full" value="{{ $costPackages->count() }}" required />
                </div>
                <div class="col-span-2">
                    <x-input-label for="create_pkg_name" value="Name" />
                    <x-text-input id="create_pkg_name" name="name" type="text" class="mt-1 block w-full" required />
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <x-primary-button>Create</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Import CSV Modal --}}
    <x-modal name="import-csv" :show="false" maxWidth="lg">
        <form method="POST" action="{{ route('projects.control-accounts.line-items.import', [$project, $controlAccount]) }}" enctype="multipart/form-data" class="p-6">
            @csrf
            <h2 class="text-lg font-medium text-gray-900 mb-4">Import Line Items from CSV</h2>
            <p class="text-sm text-gray-600 mb-4">Upload a CSV file with columns: <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">control_account_code, package_name, item_no, description, unit_of_measure, qty, rate, amount</code></p>
            <div>
                <x-input-label for="csv_file" value="CSV File" />
                <input id="csv_file" name="csv_file" type="file" accept=".csv,.txt" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" required />
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                <x-primary-button>Import</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Per-package modals --}}
    @foreach($costPackages as $package)
        {{-- Edit Cost Package --}}
        <x-modal name="edit-cost-package-{{ $package->id }}" :show="false" maxWidth="lg">
            <form method="POST" action="{{ route('projects.cost-packages.update', [$project, $package]) }}" class="p-6">
                @csrf
                @method('PUT')
                <h2 class="text-lg font-medium text-gray-900 mb-4">Edit Cost Package - {{ $package->name }}</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="edit_pkg_item_no_{{ $package->id }}" value="Item No" />
                        <x-text-input id="edit_pkg_item_no_{{ $package->id }}" name="item_no" type="text" class="mt-1 block w-full" :value="$package->item_no" />
                    </div>
                    <div>
                        <x-input-label for="edit_pkg_sort_order_{{ $package->id }}" value="Sort Order" />
                        <x-text-input id="edit_pkg_sort_order_{{ $package->id }}" name="sort_order" type="number" class="mt-1 block w-full" :value="$package->sort_order" required />
                    </div>
                    <div class="col-span-2">
                        <x-input-label for="edit_pkg_name_{{ $package->id }}" value="Name" />
                        <x-text-input id="edit_pkg_name_{{ $package->id }}" name="name" type="text" class="mt-1 block w-full" :value="$package->name" required />
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                    <x-primary-button>Save Changes</x-primary-button>
                </div>
            </form>
        </x-modal>

        {{-- Delete Cost Package --}}
        <x-modal name="delete-cost-package-{{ $package->id }}" :show="false">
            <form method="POST" action="{{ route('projects.cost-packages.destroy', [$project, $package]) }}" class="p-6">
                @csrf
                @method('DELETE')
                <h2 class="text-lg font-medium text-gray-900">Delete Cost Package</h2>
                <p class="mt-2 text-sm text-gray-600">Are you sure you want to delete <strong>{{ $package->name }}</strong>? This will also delete all line items and their forecasts.</p>
                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                    <x-danger-button>Delete</x-danger-button>
                </div>
            </form>
        </x-modal>

        {{-- Add Line Item --}}
        <x-modal name="add-line-item-{{ $package->id }}" :show="false" maxWidth="lg">
            <form method="POST" action="{{ route('projects.line-items.store', [$project, $package]) }}" class="p-6"
                x-data="{
                    qty: 0,
                    rate: 0,
                    get amount() { return +(this.qty * this.rate).toFixed(2) },
                }">
                @csrf
                <h2 class="text-lg font-medium text-gray-900 mb-4">Add Line Item to {{ $package->name }}</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="create_li_item_no_{{ $package->id }}" value="Item No" />
                        <x-text-input id="create_li_item_no_{{ $package->id }}" name="item_no" type="text" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="create_li_sort_order_{{ $package->id }}" value="Sort Order" />
                        <x-text-input id="create_li_sort_order_{{ $package->id }}" name="sort_order" type="number" class="mt-1 block w-full" :value="$package->lineItems->count()" required />
                    </div>
                    <div class="col-span-2">
                        <x-input-label for="create_li_description_{{ $package->id }}" value="Description" />
                        <x-text-input id="create_li_description_{{ $package->id }}" name="description" type="text" class="mt-1 block w-full" required />
                    </div>
                    <div>
                        <x-input-label for="create_li_uom_{{ $package->id }}" value="Unit of Measure" />
                        <x-text-input id="create_li_uom_{{ $package->id }}" name="unit_of_measure" type="text" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="create_li_qty_{{ $package->id }}" value="Original Qty" />
                        <input id="create_li_qty_{{ $package->id }}" name="original_qty" type="number" step="0.01" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" x-model.number="qty" required />
                    </div>
                    <div>
                        <x-input-label for="create_li_rate_{{ $package->id }}" value="Original Rate" />
                        <input id="create_li_rate_{{ $package->id }}" name="original_rate" type="number" step="0.01" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" x-model.number="rate" required />
                    </div>
                    <div>
                        <x-input-label for="create_li_amount_{{ $package->id }}" value="Original Amount" />
                        <input name="original_amount" type="hidden" :value="amount" />
                        <div class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm text-gray-700" x-text="'$' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                    <x-primary-button>Create</x-primary-button>
                </div>
            </form>
        </x-modal>

        {{-- Per-item modals --}}
        @foreach($package->lineItems as $item)
            {{-- Edit Line Item --}}
            <x-modal name="edit-line-item-{{ $item->id }}" :show="false" maxWidth="lg">
                <form method="POST" action="{{ route('projects.line-items.update', [$project, $package, $item]) }}" class="p-6"
                    x-data="{
                        qty: {{ $item->original_qty }},
                        rate: {{ $item->original_rate }},
                        get amount() { return +(this.qty * this.rate).toFixed(2) },
                    }">
                    @csrf
                    @method('PUT')
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Edit Line Item</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="edit_li_item_no_{{ $item->id }}" value="Item No" />
                            <x-text-input id="edit_li_item_no_{{ $item->id }}" name="item_no" type="text" class="mt-1 block w-full" :value="$item->item_no" />
                        </div>
                        <div>
                            <x-input-label for="edit_li_sort_order_{{ $item->id }}" value="Sort Order" />
                            <x-text-input id="edit_li_sort_order_{{ $item->id }}" name="sort_order" type="number" class="mt-1 block w-full" :value="$item->sort_order" required />
                        </div>
                        <div class="col-span-2">
                            <x-input-label for="edit_li_description_{{ $item->id }}" value="Description" />
                            <x-text-input id="edit_li_description_{{ $item->id }}" name="description" type="text" class="mt-1 block w-full" :value="$item->description" required />
                        </div>
                        <div>
                            <x-input-label for="edit_li_uom_{{ $item->id }}" value="Unit of Measure" />
                            <x-text-input id="edit_li_uom_{{ $item->id }}" name="unit_of_measure" type="text" class="mt-1 block w-full" :value="$item->unit_of_measure" />
                        </div>
                        <div>
                            <x-input-label for="edit_li_qty_{{ $item->id }}" value="Original Qty" />
                            <input id="edit_li_qty_{{ $item->id }}" name="original_qty" type="number" step="0.01" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" x-model.number="qty" required />
                        </div>
                        <div>
                            <x-input-label for="edit_li_rate_{{ $item->id }}" value="Original Rate" />
                            <input id="edit_li_rate_{{ $item->id }}" name="original_rate" type="number" step="0.01" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" x-model.number="rate" required />
                        </div>
                        <div>
                            <x-input-label for="edit_li_amount_{{ $item->id }}" value="Original Amount" />
                            <input name="original_amount" type="hidden" :value="amount" />
                            <div class="mt-1 block w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-sm text-gray-700" x-text="'$' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                        <x-primary-button>Save Changes</x-primary-button>
                    </div>
                </form>
            </x-modal>

            {{-- Delete Line Item --}}
            <x-modal name="delete-line-item-{{ $item->id }}" :show="false">
                <form method="POST" action="{{ route('projects.line-items.destroy', [$project, $package, $item]) }}" class="p-6">
                    @csrf
                    @method('DELETE')
                    <h2 class="text-lg font-medium text-gray-900">Delete Line Item</h2>
                    <p class="mt-2 text-sm text-gray-600">Are you sure you want to delete <strong>{{ $item->description }}</strong>? This will also delete all associated forecasts.</p>
                    <div class="mt-6 flex justify-end gap-3">
                        <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                        <x-danger-button>Delete</x-danger-button>
                    </div>
                </form>
            </x-modal>
        @endforeach
    @endforeach
</x-app-layout>
