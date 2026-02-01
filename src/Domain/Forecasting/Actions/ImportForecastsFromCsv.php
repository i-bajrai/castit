<?php

namespace Domain\Forecasting\Actions;

use App\Models\CostPackage;
use App\Models\ForecastPeriod;
use App\Models\LineItem;
use App\Models\LineItemForecast;
use App\Models\Project;
use Illuminate\Support\Collection;

class ImportForecastsFromCsv
{
    public function execute(Project $project, array $rows): ImportForecastResult
    {
        $imported = 0;
        $skipped = 0;
        $created = 0;
        $errors = [];

        $lineItems = LineItem::whereHas('costPackage', function ($q) use ($project) {
            $q->where('project_id', $project->id);
        })->get();

        $lineItemsByDescription = $lineItems->keyBy(
            fn (LineItem $li) => strtolower(trim($li->description))
        );

        $periods = $project->forecastPeriods()->orderBy('period_date')->get();
        $periodsByKey = $periods->keyBy(
            fn (ForecastPeriod $p) => $p->period_date->format('Y-m')
        );

        $defaultPackage = null;

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 for 1-indexed + header row

            $description = trim($row['description'] ?? '');
            $periodKey = trim($row['period'] ?? '');
            $ctdQty = $row['ctd_qty'] ?? null;

            if ($description === '' || $periodKey === '' || $ctdQty === null || $ctdQty === '') {
                $errors[] = "Row {$rowNum}: missing required field(s).";
                continue;
            }

            if (! is_numeric($ctdQty)) {
                $errors[] = "Row {$rowNum}: ctd_qty must be numeric.";
                continue;
            }

            $lineItem = $lineItemsByDescription->get(strtolower($description));
            if (! $lineItem) {
                $defaultPackage ??= $this->getOrCreateDefaultPackage($project);
                $lineItem = $this->createLineItem($defaultPackage, $description, $periods);
                $lineItemsByDescription->put(strtolower($description), $lineItem);
                $created++;
            }

            $period = $periodsByKey->get($periodKey);
            if (! $period) {
                $errors[] = "Row {$rowNum}: period '{$periodKey}' not found.";
                continue;
            }

            if ($period->period_date->gte(now()->startOfMonth())) {
                $errors[] = "Row {$rowNum}: period '{$periodKey}' is not a past period.";
                continue;
            }

            $forecast = LineItemForecast::where('line_item_id', $lineItem->id)
                ->where('forecast_period_id', $period->id)
                ->first();

            if (! $forecast) {
                $forecast = LineItemForecast::create([
                    'line_item_id' => $lineItem->id,
                    'forecast_period_id' => $period->id,
                ]);
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

        return new ImportForecastResult($imported, $skipped, $errors, $created);
    }

    private function getOrCreateDefaultPackage(Project $project): CostPackage
    {
        $defaultCa = $project->controlAccounts()->firstOrCreate(
            ['code' => 'UNASSIGNED'],
            ['description' => 'Unassigned', 'phase' => 'Unassigned', 'sort_order' => 999],
        );

        return CostPackage::firstOrCreate(
            ['project_id' => $project->id, 'name' => 'Unassigned'],
            ['control_account_id' => $defaultCa->id, 'sort_order' => 999],
        );
    }

    private function createLineItem(CostPackage $package, string $description, Collection $periods): LineItem
    {
        $lineItem = LineItem::create([
            'cost_package_id' => $package->id,
            'description' => $description,
            'original_qty' => 0,
            'original_rate' => 0,
            'original_amount' => 0,
        ]);

        foreach ($periods as $period) {
            LineItemForecast::create([
                'line_item_id' => $lineItem->id,
                'forecast_period_id' => $period->id,
            ]);
        }

        return $lineItem;
    }
}
