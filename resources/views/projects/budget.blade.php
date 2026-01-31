<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <a href="{{ route('projects.show', $project) }}" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; {{ $project->name }}</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight mt-1">
                    Set Up Budget
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <p class="text-sm text-gray-600">Set baseline budgets for each control account. You can enter amounts directly or import detailed line items from a CSV.</p>
                        <div class="flex items-center gap-3 ml-4 shrink-0">
                            <a
                                href="#"
                                data-testid="download-budget-sample-csv"
                                x-data
                                x-on:click.prevent="
                                    const csv = 'control_account_code,package_name,item_no,description,unit_of_measure,qty,rate,amount\n401CB00,Design Package 02,007,TL5 BARRIER 295-CB-001,LM,98,342.00,33516.00\n401CB00,Design Package 02,008,TL5 BARRIER 295-CB-002,LM,116,313.85,36406.60\n402ST00,Steel Fabrication,001,I-Beams Grade 350,Tonne,50,2500.00,125000.00';
                                    const blob = new Blob([csv], { type: 'text/csv' });
                                    const a = document.createElement('a');
                                    a.href = URL.createObjectURL(blob);
                                    a.download = 'budget-import-sample.csv';
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
                            <button
                                type="button"
                                x-data
                                x-on:click="document.querySelector('[data-testid=budget-csv-file-input]').click()"
                                data-testid="import-budget-csv-button"
                                class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Import CSV
                            </button>
                        </div>
                    </div>

                    <form
                        data-testid="budget-setup-form"
                        method="POST"
                        action="{{ route('projects.budget.store', $project) }}"
                        x-data="{
                            accounts: @js($controlAccounts->map(fn($ca) => [
                                'id' => $ca->id,
                                'code' => $ca->code,
                                'description' => $ca->description,
                                'baseline_budget' => $ca->baseline_budget > 0 ? $ca->baseline_budget : '',
                                'packages' => [],
                            ])->values()),
                            importCsv(event) {
                                const file = event.target.files[0];
                                if (!file) return;
                                const reader = new FileReader();
                                reader.onload = (e) => {
                                    const text = e.target.result;
                                    const lines = text.split(/\r?\n/).filter(l => l.trim() !== '');

                                    // Parse CSV with basic quote handling
                                    const parseLine = (line) => {
                                        const result = [];
                                        let current = '';
                                        let inQuotes = false;
                                        for (let i = 0; i < line.length; i++) {
                                            const ch = line[i];
                                            if (ch === String.fromCharCode(34)) {
                                                inQuotes = !inQuotes;
                                            } else if (ch === ',' && !inQuotes) {
                                                result.push(current.trim());
                                                current = '';
                                            } else {
                                                current += ch;
                                            }
                                        }
                                        result.push(current.trim());
                                        return result;
                                    };

                                    // Group by (code, package_name)
                                    const grouped = {};
                                    for (let i = 0; i < lines.length; i++) {
                                        const parts = parseLine(lines[i]);
                                        if (parts.length >= 8 && parts[0].toLowerCase() === 'control_account_code') continue;
                                        if (parts.length < 8 || !parts[0]) continue;

                                        const code = parts[0];
                                        const pkgName = parts[1];
                                        const li = {
                                            item_no: parts[2] || '',
                                            description: parts[3] || '',
                                            unit_of_measure: parts[4] || '',
                                            qty: parseFloat(parts[5]) || 0,
                                            rate: parseFloat(parts[6]) || 0,
                                            amount: parseFloat(parts[7]) || 0,
                                        };

                                        if (!grouped[code]) grouped[code] = {};
                                        if (!grouped[code][pkgName]) grouped[code][pkgName] = [];
                                        grouped[code][pkgName].push(li);
                                    }

                                    // Apply to accounts
                                    for (const account of this.accounts) {
                                        if (grouped[account.code]) {
                                            account.packages = [];
                                            let total = 0;
                                            for (const [pkgName, items] of Object.entries(grouped[account.code])) {
                                                account.packages.push({
                                                    item_no: '',
                                                    name: pkgName,
                                                    line_items: items,
                                                });
                                                total += items.reduce((s, li) => s + li.amount, 0);
                                            }
                                            account.baseline_budget = Math.round(total * 100) / 100;
                                        }
                                    }

                                    event.target.value = '';
                                };
                                reader.readAsText(file);
                            },
                            clearPackages(index) {
                                this.accounts[index].packages = [];
                                this.accounts[index].baseline_budget = '';
                            },
                            lineItemCount(account) {
                                return account.packages.reduce((sum, pkg) => sum + pkg.line_items.length, 0);
                            }
                        }"
                    >
                        @csrf

                        <input
                            type="file"
                            accept=".csv"
                            x-on:change="importCsv($event)"
                            data-testid="budget-csv-file-input"
                            class="hidden"
                        />

                        <div class="space-y-4">
                            <template x-for="(account, ai) in accounts" :key="ai">
                                <div data-testid="account-budget-card" class="border border-gray-200 rounded-lg p-4">
                                    <input type="hidden" :name="`accounts[${ai}][control_account_id]`" :value="account.id">

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="font-semibold text-gray-900" x-text="account.code"></span>
                                            <span class="ml-2 text-sm text-gray-600" x-text="account.description"></span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <label class="text-sm text-gray-500">Baseline Budget $</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                x-model="account.baseline_budget"
                                                :name="`accounts[${ai}][baseline_budget]`"
                                                data-testid="baseline-budget-input"
                                                class="w-40 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm text-right"
                                                placeholder="0.00"
                                                required
                                            />
                                        </div>
                                    </div>

                                    <template x-if="account.packages.length > 0">
                                        <div class="mt-3 flex items-center justify-between text-sm">
                                            <span data-testid="imported-summary" class="text-green-600">
                                                <span x-text="account.packages.length"></span> cost package(s),
                                                <span x-text="lineItemCount(account)"></span> line item(s) imported
                                            </span>
                                            <button
                                                type="button"
                                                x-on:click="clearPackages(ai)"
                                                data-testid="clear-imported-button"
                                                class="text-red-500 hover:text-red-700 text-xs font-medium"
                                            >Clear</button>
                                        </div>
                                    </template>

                                    <!-- Hidden inputs for packages/line items -->
                                    <template x-for="(pkg, pi) in account.packages" :key="pi">
                                        <div>
                                            <input type="hidden" :name="`accounts[${ai}][packages][${pi}][item_no]`" :value="pkg.item_no">
                                            <input type="hidden" :name="`accounts[${ai}][packages][${pi}][name]`" :value="pkg.name">
                                            <template x-for="(li, lii) in pkg.line_items" :key="lii">
                                                <div>
                                                    <input type="hidden" :name="`accounts[${ai}][packages][${pi}][line_items][${lii}][item_no]`" :value="li.item_no">
                                                    <input type="hidden" :name="`accounts[${ai}][packages][${pi}][line_items][${lii}][description]`" :value="li.description">
                                                    <input type="hidden" :name="`accounts[${ai}][packages][${pi}][line_items][${lii}][unit_of_measure]`" :value="li.unit_of_measure">
                                                    <input type="hidden" :name="`accounts[${ai}][packages][${pi}][line_items][${lii}][qty]`" :value="li.qty">
                                                    <input type="hidden" :name="`accounts[${ai}][packages][${pi}][line_items][${lii}][rate]`" :value="li.rate">
                                                    <input type="hidden" :name="`accounts[${ai}][packages][${pi}][line_items][${lii}][amount]`" :value="li.amount">
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                            <a
                                href="{{ route('projects.show', $project) }}"
                                data-testid="skip-budget-link"
                                class="text-sm text-gray-600 hover:text-gray-800"
                            >
                                Skip for now
                            </a>
                            <x-primary-button data-testid="save-budget-button">
                                Save & Continue
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
