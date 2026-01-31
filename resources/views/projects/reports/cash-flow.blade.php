<x-app-layout>
    <x-slot name="header">
        <x-project-header :project="$project" active="reports" :breadcrumbs="[['route' => route('projects.reports', $project), 'label' => 'Reports']]">
            S-Curve / Cash Flow - {{ $project->name }}
        </x-project-header>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase">Total Budget</p>
                    <p class="text-2xl font-bold text-gray-900">${{ number_format($totalBudget, 0) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase">Total Spent (CTD)</p>
                    @php $lastPeriod = end($periods); @endphp
                    <p class="text-2xl font-bold text-blue-600">${{ number_format($lastPeriod ? $lastPeriod['cumulative_ctd'] : 0, 0) }}</p>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-4">
                    <p class="text-xs font-medium text-gray-500 uppercase">Latest FCAC</p>
                    <p class="text-2xl font-bold {{ $totalFcac > $totalBudget ? 'text-red-600' : 'text-green-600' }}">${{ number_format($totalFcac, 0) }}</p>
                </div>
            </div>

            {{-- S-Curve Chart --}}
            @if(!empty($periods))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Cumulative Spend vs Planned Budget</h3>
                    <div x-data="{
                        init() {
                            const canvas = this.$refs.chart;
                            const ctx = canvas.getContext('2d');
                            const dpr = window.devicePixelRatio || 1;
                            const rect = canvas.parentElement.getBoundingClientRect();
                            canvas.width = rect.width * dpr;
                            canvas.height = 300 * dpr;
                            canvas.style.width = rect.width + 'px';
                            canvas.style.height = '300px';
                            ctx.scale(dpr, dpr);
                            const w = rect.width;
                            const h = 300;
                            const padding = { top: 20, right: 20, bottom: 50, left: 80 };
                            const periods = {{ Js::from(collect($periods)->map(fn($p) => ['label' => $p['label'], 'cumulative' => $p['cumulative_ctd'], 'planned' => $p['planned_cumulative']])->values()) }};
                            const budget = {{ $totalBudget }};
                            const maxVal = Math.max(budget, ...periods.map(p => p.cumulative), ...periods.map(p => p.planned)) * 1.1;
                            const chartW = w - padding.left - padding.right;
                            const chartH = h - padding.top - padding.bottom;
                            ctx.strokeStyle = '#e5e7eb';
                            ctx.lineWidth = 1;
                            for (let i = 0; i <= 4; i++) {
                                const y = padding.top + (chartH / 4) * i;
                                ctx.beginPath();
                                ctx.moveTo(padding.left, y);
                                ctx.lineTo(w - padding.right, y);
                                ctx.stroke();
                                ctx.fillStyle = '#6b7280';
                                ctx.font = '11px system-ui';
                                ctx.textAlign = 'right';
                                const val = maxVal - (maxVal / 4) * i;
                                ctx.fillText('$' + Math.round(val).toLocaleString(), padding.left - 8, y + 4);
                            }
                            ctx.fillStyle = '#6b7280';
                            ctx.font = '10px system-ui';
                            ctx.textAlign = 'center';
                            periods.forEach((p, i) => {
                                const x = padding.left + (chartW / (periods.length - 1 || 1)) * i;
                                ctx.save();
                                ctx.translate(x, h - padding.bottom + 15);
                                ctx.rotate(-0.5);
                                ctx.fillText(p.label, 0, 0);
                                ctx.restore();
                            });
                            function drawLine(data, key, color, dashed) {
                                ctx.strokeStyle = color;
                                ctx.lineWidth = 2;
                                ctx.setLineDash(dashed ? [5, 5] : []);
                                ctx.beginPath();
                                data.forEach((p, i) => {
                                    const x = padding.left + (chartW / (data.length - 1 || 1)) * i;
                                    const y = padding.top + chartH - (p[key] / maxVal) * chartH;
                                    if (i === 0) ctx.moveTo(x, y);
                                    else ctx.lineTo(x, y);
                                });
                                ctx.stroke();
                                ctx.setLineDash([]);
                                data.forEach((p, i) => {
                                    const x = padding.left + (chartW / (data.length - 1 || 1)) * i;
                                    const y = padding.top + chartH - (p[key] / maxVal) * chartH;
                                    ctx.beginPath();
                                    ctx.arc(x, y, 3, 0, Math.PI * 2);
                                    ctx.fillStyle = color;
                                    ctx.fill();
                                });
                            }
                            ctx.strokeStyle = '#9ca3af';
                            ctx.lineWidth = 1;
                            ctx.setLineDash([3, 3]);
                            const budgetY = padding.top + chartH - (budget / maxVal) * chartH;
                            ctx.beginPath();
                            ctx.moveTo(padding.left, budgetY);
                            ctx.lineTo(w - padding.right, budgetY);
                            ctx.stroke();
                            ctx.setLineDash([]);
                            ctx.fillStyle = '#9ca3af';
                            ctx.font = '10px system-ui';
                            ctx.textAlign = 'left';
                            ctx.fillText('Budget', w - padding.right - 40, budgetY - 5);
                            drawLine(periods, 'planned', '#9ca3af', true);
                            drawLine(periods, 'cumulative', '#3b82f6', false);
                            const legendY = h - 8;
                            ctx.font = '11px system-ui';
                            ctx.textAlign = 'left';
                            ctx.fillStyle = '#3b82f6';
                            ctx.fillRect(padding.left, legendY - 8, 12, 3);
                            ctx.fillStyle = '#374151';
                            ctx.fillText('Actual Cumulative', padding.left + 16, legendY - 3);
                            ctx.strokeStyle = '#9ca3af';
                            ctx.setLineDash([3, 3]);
                            ctx.beginPath();
                            ctx.moveTo(padding.left + 140, legendY - 6);
                            ctx.lineTo(padding.left + 152, legendY - 6);
                            ctx.stroke();
                            ctx.setLineDash([]);
                            ctx.fillStyle = '#374151';
                            ctx.fillText('Planned Cumulative', padding.left + 156, legendY - 3);
                        }
                    }" class="relative">
                        <canvas x-ref="chart" height="300"></canvas>
                    </div>
                </div>
            @endif

            {{-- Period Data Table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-blue-700 uppercase bg-blue-50">Period Spend</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-blue-700 uppercase bg-blue-50">Cumulative Spend</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Planned Cumulative</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-amber-700 uppercase bg-amber-50">CTC</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-green-700 uppercase bg-green-50">FCAC</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($periods as $p)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $p['label'] }}</td>
                                    <td class="px-4 py-3 text-sm text-right bg-blue-50/30 {{ $p['period_ctd'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                        ${{ number_format($p['period_ctd'], 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right bg-blue-50/30 font-semibold text-gray-900">
                                        ${{ number_format($p['cumulative_ctd'], 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-600">
                                        ${{ number_format($p['planned_cumulative'], 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right bg-amber-50/30 {{ $p['period_ctc'] > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                        ${{ number_format($p['period_ctc'], 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right bg-green-50/30 font-semibold text-gray-900">
                                        ${{ number_format($p['period_fcac'], 0) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">No period data available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
