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
    public function __construct(
        private UpdateLineItemForecast $updateLineItemForecast,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function execute(Project $project, array $rows): ImportForecastResult
    {
        $imported = 0;
        $skipped = 0;
        $created = 0;
        $errors = [];

        // Exclude items in the UNASSIGNED package so we always prefer matching against real assigned items
        $unassignedCaId = $project->controlAccounts()->where('code', 'UNASSIGNED')->value('id');

        $lineItems = LineItem::whereHas('costPackage', function ($q) use ($project, $unassignedCaId) {
            $q->where('project_id', $project->id);
            if ($unassignedCaId) {
                $q->where('control_account_id', '!=', $unassignedCaId);
            }
        })->get();

        $lineItemsByDescription = $lineItems->keyBy(
            fn (LineItem $li) => $this->normalizeDescription($li->description)
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
            $periodQty = $row['period_qty'] ?? $row['ctd_qty'] ?? null;

            if ($description === '' || $periodKey === '' || $periodQty === null || $periodQty === '') {
                $errors[] = "Row {$rowNum}: missing required field(s).";

                continue;
            }

            if (! is_numeric($periodQty)) {
                $errors[] = "Row {$rowNum}: period_qty must be numeric.";

                continue;
            }

            $normalizedDesc = $this->normalizeDescription($description);
            $lineItem = $lineItemsByDescription->get($normalizedDesc);
            if (! $lineItem) {
                $defaultPackage ??= $this->getOrCreateDefaultPackage($project);
                $lineItem = $this->createLineItem($defaultPackage, $description, $periods);
                $lineItemsByDescription->put($normalizedDesc, $lineItem);
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

            if ((float) $forecast->period_qty !== 0.0) {
                $skipped++;

                continue;
            }

            $this->updateLineItemForecast->execute($lineItem, $period, (float) $periodQty);

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

    private function normalizeDescription(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * @param  Collection<int, ForecastPeriod>  $periods
     */
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
