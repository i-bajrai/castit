# Spec: Incremental Per-Period Forecasting with Rate Change Tracking

## Problem

The current system stores **cumulative** CTD values with a single rate hardcoded to `original_rate`. This makes it impossible to accurately track costs when rates change mid-project, because:
- A single `ctd_rate` can't represent "40 hours at $250 then 25 hours at $300"
- The system needs to **distinctly know** each rate and when it changed, not blend them

## Proposed Model: Incremental Per-Period

Each forecast record stores **that month's** actuals, not cumulative totals. Cumulative values (CTD) are computed by summing all periods up to the selected one.

### Worked Example

**Line item: 100 hrs @ $250/hr = $25,000 original budget**

**Data entry — user enters each month's actuals:**

| Month | Period Qty | Period Rate | Period Amount |
|---|---|---|---|
| Jan | 10 | $250 | $2,500 |
| Feb | 15 | $250 | $3,750 |
| Mar | 15 | $250 | $3,750 |
| **Apr** | **10** | **$300** | **$3,000** |
| May | 12 | $300 | $3,600 |
| Jun | 8 | $300 | $2,400 |

When the rate changes in April, it **sticks forward** — May and June default to $300 without the user re-entering it.

**Report view — cumulative up to selected period:**

Viewing **May**:

| | Qty | Rate | Amount |
|---|---|---|---|
| Original | 100 | $250 | $25,000 |
| CTD (sum Jan-May) | 62 | $300* | $16,600 |
| CTC (remaining @ current rate) | 38 | $300 | $11,400 |
| FCAC (CTD + CTC) | 100 | — | $28,000 |
| Variance | | | +$3,000 |

The **$300*** has an asterisk because the rate changed during the project. Clicking it opens a modal showing all period rows:

| Period | Qty | Rate | Amount |
|---|---|---|---|
| Jan 2026 | 10 | $250.00 | $2,500.00 |
| Feb 2026 | 15 | $250.00 | $3,750.00 |
| Mar 2026 | 15 | $250.00 | $3,750.00 |
| Apr 2026 | 10 | $300.00 | $3,000.00 |
| May 2026 | 12 | $300.00 | $3,600.00 |
| **Total** | **62** | | **$16,600.00** |

Viewing **March** (before rate change):

| | Qty | Rate | Amount |
|---|---|---|---|
| Original | 100 | $250 | $25,000 |
| CTD (sum Jan-Mar) | 40 | $250 | $10,000 |
| CTC | 60 | $250 | $15,000 |
| FCAC | 100 | — | $25,000 |
| Variance | | | $0 |

No asterisk — rate hasn't changed yet.

---

## Schema Changes

### Current `line_item_forecasts` table (cumulative model)

```
previous_qty, previous_rate, previous_amount (stored computed)
ctd_qty, ctd_rate, ctd_amount (stored computed)          ← cumulative
fcac_qty, fcac_rate, fcac_amount (stored computed)
ctc_qty (stored computed), ctc_rate, ctc_amount (stored computed)
variance (stored computed)
```

### New `line_item_forecasts` table (incremental model)

```
period_qty          — this month's actual quantity (user enters)
period_rate         — this month's rate (user enters, defaults to previous period's rate or original)
period_amount       — stored computed: period_qty * period_rate

fcac_qty            — total forecast quantity (user can override, defaults to original_qty)
fcac_rate           — forecast rate going forward (= period_rate, or user override)
fcac_amount         — stored computed: fcac_qty * fcac_rate

comments
```

**Removed columns:** `previous_qty`, `previous_rate`, `previous_amount`, `ctd_qty`, `ctd_rate`, `ctd_amount`, `ctc_qty`, `ctc_rate`, `ctc_amount`, `variance`

**Why removed:** These are all derivable at query time:
- **CTD qty** = SUM(period_qty) across all periods up to selected
- **CTD amount** = SUM(period_amount) across all periods up to selected
- **CTD rate** = the rate from the most recent period (the "current" rate)
- **CTC qty** = fcac_qty - CTD qty
- **CTC rate** = fcac_rate (the expected rate for remaining work)
- **CTC amount** = CTC qty * CTC rate
- **Previous FCAC** = the fcac_amount from the prior period's forecast record
- **Variance** = fcac_amount - previous period's fcac_amount

### Migration strategy

New migration that:
1. Adds `period_qty`, `period_rate`, `period_amount` (stored computed)
2. Removes the old stored computed columns
3. Removes `previous_*`, `ctd_*`, `ctc_*` columns
4. Keeps `fcac_qty`, `fcac_rate`, `fcac_amount`, `comments`

Data migration: for existing forecast records, set `period_qty = ctd_qty` and `period_rate = ctd_rate` (since there's only one period of data currently, cumulative = incremental).

---

## Application Changes

### 1. `UpdateLineItemForecast` → store incremental values

```php
public function execute(
    LineItem $lineItem,
    ForecastPeriod $period,
    float $periodQty,
    ?float $periodRate = null,   // null = use previous period's rate or original
    ?float $fcacQty = null,      // null = use original_qty
    ?float $fcacRate = null,     // null = use periodRate
    ?string $comments = null,
): LineItemForecast {
    $origRate = (float) $lineItem->original_rate;
    $origQty  = (float) $lineItem->original_qty;

    // Resolve rate: explicit > previous period > original
    $rate = $periodRate ?? $this->getPreviousPeriodRate($lineItem, $period) ?? $origRate;

    return LineItemForecast::updateOrCreate(
        ['line_item_id' => $lineItem->id, 'forecast_period_id' => $period->id],
        [
            'period_qty'  => $periodQty,
            'period_rate'  => $rate,
            'fcac_qty'     => $fcacQty ?? $origQty,
            'fcac_rate'    => $fcacRate ?? $rate,
            'comments'     => $comments,
        ],
    );
}

private function getPreviousPeriodRate(LineItem $lineItem, ForecastPeriod $currentPeriod): ?float
{
    $previousPeriod = ForecastPeriod::where('project_id', $currentPeriod->project_id)
        ->where('period_date', '<', $currentPeriod->period_date)
        ->orderByDesc('period_date')
        ->first();

    if (!$previousPeriod) return null;

    $previousForecast = LineItemForecast::where('line_item_id', $lineItem->id)
        ->where('forecast_period_id', $previousPeriod->id)
        ->first();

    return $previousForecast?->period_rate;
}
```

### 2. `GetProjectForecastSummary` → compute cumulative CTD

Instead of reading `ctd_amount` from a single row, sum across periods:

```php
// For a given line item up to period X:
$forecasts = LineItemForecast::where('line_item_id', $lineItem->id)
    ->whereHas('forecastPeriod', fn($q) => $q->where('period_date', '<=', $period->period_date))
    ->get();

$ctdQty    = $forecasts->sum('period_qty');
$ctdAmount = $forecasts->sum('period_amount');
$currentRate = $forecasts->sortByDesc('forecastPeriod.period_date')->first()?->period_rate;

// Rate changed? = more than one distinct rate across periods
$rateChanged = $forecasts->pluck('period_rate')->unique()->count() > 1;
```

### 3. `CarryForwardForecasts` → simplified

No need to copy values forward. The new period just needs an empty record. The rate defaults to the previous period's rate via `getPreviousPeriodRate()` when data is entered.

Could be simplified to just calling `EnsureLineItemForecastsExist` or removed entirely.

### 4. Report views — asterisk + rate history modal

In the cost detail report, when displaying CTD rate:

```blade
<td>
    ${{ number_format($currentRate, 2) }}
    @if($rateChanged)
        <button x-on:click="$dispatch('open-modal', 'rate-history-{{ $item->id }}')"
                class="text-amber-500 hover:text-amber-700 ml-1"
                title="Rate changed during project">*</button>
    @endif
</td>
```

The modal shows all period rows for that line item:

```blade
<x-modal name="rate-history-{{ $item->id }}" maxWidth="md">
    <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">
            Rate History — {{ $item->description }}
        </h2>
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr>
                    <th class="text-left py-2">Period</th>
                    <th class="text-right py-2">Qty</th>
                    <th class="text-right py-2">Rate</th>
                    <th class="text-right py-2">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($item->periodForecasts as $pf)
                    <tr @class(['bg-amber-50' => $pf->period_rate != $item->original_rate])>
                        <td class="py-2">{{ $pf->forecastPeriod->period_date->format('M Y') }}</td>
                        <td class="text-right py-2">{{ number_format($pf->period_qty, 1) }}</td>
                        <td class="text-right py-2">${{ number_format($pf->period_rate, 2) }}</td>
                        <td class="text-right py-2">${{ number_format($pf->period_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="font-semibold border-t-2">
                <tr>
                    <td class="py-2">Total (CTD)</td>
                    <td class="text-right py-2">{{ number_format($ctdQty, 1) }}</td>
                    <td></td>
                    <td class="text-right py-2">${{ number_format($ctdAmount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-modal>
```

Rows where the rate differs from original are highlighted in amber.

### 5. Data entry page — enter this month's qty

The per-CA data entry page (`control-account-forecast.blade.php`) changes:

| Item | Description | UoM | Orig Qty | Orig Rate | **This Month Qty** | **Rate** | Comments |
|------|-------------|-----|----------|-----------|-------------------|----------|----------|

- **This Month Qty** — editable, this period's actual quantity
- **Rate** — shows the current rate (inherited from previous period or original). Editable — when changed, sticks forward to future periods
- Rate field highlighted if it differs from original rate

---

## What does NOT change

- **Original rate on the line item** — `line_items.original_rate` stays as the baseline
- **FCAC columns** — still per-row, still stored computed (`fcac_amount = fcac_qty * fcac_rate`)
- **Report structure** — same cards, same layout, just the data source changes from single-row to summed

---

## Implementation Order

1. **Migration** — add `period_qty`, `period_rate`, `period_amount`; remove old cumulative columns; migrate existing data
2. **Update `UpdateLineItemForecast`** — store incremental values with rate inheritance
3. **Update `GetProjectForecastSummary`** — compute CTD by summing periods, detect rate changes
4. **Update `CarryForwardForecasts`** — simplify (or remove)
5. **Update data entry UI** — "This Month Qty" + "Rate" instead of cumulative CTD Qty
6. **Update report views** — asterisk on changed rates + rate history modal
7. **Update other reports** (cost analysis, variance drill-down, etc.) to use the new summation model
