<?php

namespace Domain\Forecasting\Actions;

use App\Models\ForecastPeriod;
use App\Models\LineItem;
use App\Models\LineItemForecast;
use App\Models\Project;
use Carbon\Carbon;

class ImportForecastsFromCsv
{
    public function execute(Project $project, array $rows): ImportForecastResult
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $lineItems = LineItem::whereHas('costPackage', function ($q) use ($project) {
            $q->where('project_id', $project->id);
        })->get()->keyBy('item_no');

        $periods = $project->forecastPeriods()->get()->keyBy(
            fn (ForecastPeriod $p) => $p->period_date->format('Y-m')
        );

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 for 1-indexed + header row

            $itemNo = trim($row['item_no'] ?? '');
            $periodKey = trim($row['period'] ?? '');
            $ctdQty = $row['ctd_qty'] ?? null;

            if ($itemNo === '' || $periodKey === '' || $ctdQty === null || $ctdQty === '') {
                $errors[] = "Row {$rowNum}: missing required field(s).";
                continue;
            }

            if (! is_numeric($ctdQty)) {
                $errors[] = "Row {$rowNum}: ctd_qty must be numeric.";
                continue;
            }

            $lineItem = $lineItems->get($itemNo);
            if (! $lineItem) {
                $errors[] = "Row {$rowNum}: item_no '{$itemNo}' not found.";
                continue;
            }

            $period = $periods->get($periodKey);
            if (! $period) {
                $errors[] = "Row {$rowNum}: period '{$periodKey}' not found.";
                continue;
            }

            $forecast = LineItemForecast::where('line_item_id', $lineItem->id)
                ->where('forecast_period_id', $period->id)
                ->first();

            if (! $forecast) {
                $errors[] = "Row {$rowNum}: no forecast record found for item '{$itemNo}' in period '{$periodKey}'.";
                continue;
            }

            if ((float) $forecast->ctd_qty !== 0.0) {
                $skipped++;
                continue;
            }

            $ctdQty = (float) $ctdQty;
            $ctdRate = (float) $lineItem->original_rate;
            $ctdAmount = $ctdQty * $ctdRate;

            $ctcQty = max(0, (float) $lineItem->original_qty - $ctdQty);
            $ctcAmount = $ctcQty * $ctdRate;

            $fcacAmount = $ctdAmount + $ctcAmount;
            $totalQty = $ctdQty + $ctcQty;
            $fcacRate = $totalQty > 0 ? $fcacAmount / $totalQty : 0;

            $variance = (float) ($forecast->previous_amount ?? 0) - $fcacAmount;

            $forecast->update([
                'ctd_qty' => $ctdQty,
                'ctd_rate' => $ctdRate,
                'ctd_amount' => $ctdAmount,
                'ctc_qty' => $ctcQty,
                'ctc_rate' => $ctdRate,
                'ctc_amount' => $ctcAmount,
                'fcac_rate' => $fcacRate,
                'fcac_amount' => $fcacAmount,
                'variance' => $variance,
            ]);

            $imported++;
        }

        return new ImportForecastResult($imported, $skipped, $errors);
    }
}
