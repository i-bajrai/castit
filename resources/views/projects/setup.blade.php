<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <a href="{{ route('projects.show', $project) }}" class="text-sm text-indigo-600 hover:text-indigo-800">&larr; {{ $project->name }}</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight mt-1">
                    Set Up Control Accounts
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <p class="text-sm text-gray-600">Add the control accounts for your project. You can add more details later in project settings.</p>
                        <div class="flex items-center gap-3 ml-4 shrink-0">
                            <a
                                href="#"
                                data-testid="download-sample-csv"
                                x-data
                                x-on:click.prevent="
                                    const csv = 'code,description,category\n100GC00,General Conditions,100 - General\n200SI00,Site Improvements,200 - Site\n301FW00,Foundation Work,301 - Structure\n401CB00,Civil - Concrete Barriers,401S - Structure\n402ST00,Structural - Steel Works,401S - Structure\n403EL00,Electrical - Lighting,401C - Civil\n501ME00,Mechanical - HVAC,501 - Mechanical\n502PL00,Plumbing Systems,502 - Plumbing\n601LA00,Landscaping,601 - Landscape\n701PM00,Project Management,701 - Management';
                                    const blob = new Blob([csv], { type: 'text/csv' });
                                    const a = document.createElement('a');
                                    a.href = URL.createObjectURL(blob);
                                    a.download = 'control-accounts-sample.csv';
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
                                x-on:click="document.querySelector('[data-testid=csv-file-input]').click()"
                                data-testid="import-csv-button"
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
                        data-testid="setup-control-accounts-form"
                        method="POST"
                        action="{{ route('projects.control-accounts.bulk-store', $project) }}"
                        x-data="{
                            accounts: {{ $controlAccounts->count() > 0
                                ? $controlAccounts->map(fn($ca) => ['code' => $ca->code, 'description' => $ca->description, 'category' => $ca->category ?? ''])->values()->toJson()
                                : json_encode([['code' => '', 'description' => '', 'category' => '']]) }},
                            addRow() {
                                this.accounts.push({ code: '', description: '', category: '' });
                                this.$nextTick(() => {
                                    const rows = this.$el.querySelectorAll('[data-testid=account-row]');
                                    rows[rows.length - 1].querySelector('input').focus();
                                });
                            },
                            removeRow(index) {
                                if (this.accounts.length > 1) {
                                    this.accounts.splice(index, 1);
                                }
                            },
                            parseCsvLine(line) {
                                const result = [];
                                let current = '';
                                let inQuotes = false;
                                for (let i = 0; i < line.length; i++) {
                                    const ch = line[i];
                                    if (ch === '&quot;') {
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
                            },
                            importCsv(event) {
                                const file = event.target.files[0];
                                if (!file) return;
                                const reader = new FileReader();
                                reader.onload = (e) => {
                                    const text = e.target.result;
                                    const lines = text.split(/\r?\n/).filter(line => line.trim() !== '');
                                    const imported = [];
                                    for (const line of lines) {
                                        const parts = this.parseCsvLine(line);
                                        if (parts.length >= 2 && parts[0].toLowerCase() === 'code' && parts[1].toLowerCase() === 'description') {
                                            continue;
                                        }
                                        if (parts.length >= 2 && parts[0]) {
                                            imported.push({
                                                code: parts[0],
                                                description: parts[1] || '',
                                                category: parts[2] || '',
                                            });
                                        }
                                    }
                                    if (imported.length > 0) {
                                        if (this.accounts.length === 1 && !this.accounts[0].code && !this.accounts[0].description) {
                                            this.accounts = imported;
                                        } else {
                                            this.accounts = this.accounts.concat(imported);
                                        }
                                    }
                                    event.target.value = '';
                                };
                                reader.readAsText(file);
                            }
                        }"
                    >
                        @csrf

                        <input
                            type="file"
                            accept=".csv"
                            x-on:change="importCsv($event)"
                            data-testid="csv-file-input"
                            class="hidden"
                        />

                        <div class="space-y-3">
                            <div class="grid grid-cols-12 gap-3 text-xs font-medium text-gray-500 uppercase tracking-wider px-1">
                                <div class="col-span-3">Code</div>
                                <div class="col-span-5">Description</div>
                                <div class="col-span-3">Category</div>
                                <div class="col-span-1"></div>
                            </div>

                            <template x-for="(account, index) in accounts" :key="index">
                                <div data-testid="account-row" class="grid grid-cols-12 gap-3 items-center">
                                    <div class="col-span-3">
                                        <input
                                            type="text"
                                            x-model="account.code"
                                            :name="`accounts[${index}][code]`"
                                            data-testid="account-code-input"
                                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                            placeholder="e.g. 401CB00"
                                            required
                                        />
                                    </div>
                                    <div class="col-span-5">
                                        <input
                                            type="text"
                                            x-model="account.description"
                                            :name="`accounts[${index}][description]`"
                                            data-testid="account-description-input"
                                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                            placeholder="e.g. Civil - Concrete Barriers"
                                            required
                                        />
                                    </div>
                                    <div class="col-span-3">
                                        <input
                                            type="text"
                                            x-model="account.category"
                                            :name="`accounts[${index}][category]`"
                                            data-testid="account-category-input"
                                            class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm"
                                            placeholder="e.g. 401S - Structure"
                                        />
                                    </div>
                                    <div class="col-span-1 flex justify-center">
                                        <button
                                            type="button"
                                            x-show="accounts.length > 1"
                                            x-on:click="removeRow(index)"
                                            data-testid="remove-account-button"
                                            class="text-gray-400 hover:text-red-500 transition"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-4">
                            <button
                                type="button"
                                x-on:click="addRow()"
                                data-testid="add-account-button"
                                class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Another
                            </button>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                            <a
                                href="{{ route('projects.show', $project) }}"
                                data-testid="skip-setup-link"
                                class="text-sm text-gray-600 hover:text-gray-800"
                            >
                                Skip for now
                            </a>
                            <x-primary-button data-testid="save-accounts-button">
                                Save & Continue
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
