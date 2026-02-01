<?php

namespace Domain\Forecasting\Actions;

use App\Models\CostPackage;
use App\Models\LineItem;
use App\Models\LineItemForecast;
use Illuminate\Support\Facades\DB;

class ReassignLineItems
{
    public function execute(array $operations): ReassignLineItemsResult
    {
        $moved = 0;
        $merged = 0;
        $errors = [];

        DB::transaction(function () use ($operations, &$moved, &$merged, &$errors) {
            foreach ($operations as $index => $op) {
                $lineItemId = $op['line_item_id'];
                $targetPackageId = $op['target_package_id'] ?? null;
                $mergeIntoId = $op['merge_into_id'] ?? null;

                $sourceItem = LineItem::find($lineItemId);
                if (! $sourceItem) {
                    $errors[] = "Row {$index}: line item not found.";
                    continue;
                }

                if ($mergeIntoId) {
                    $targetItem = LineItem::find($mergeIntoId);
                    if (! $targetItem) {
                        $errors[] = "Row {$index}: merge target line item not found.";
                        continue;
                    }

                    $this->mergeForecasts($sourceItem, $targetItem);
                    $sourceItem->delete();
                    $merged++;
                } elseif ($targetPackageId) {
                    $targetPackage = CostPackage::find($targetPackageId);
                    if (! $targetPackage) {
                        $errors[] = "Row {$index}: target package not found.";
                        continue;
                    }

                    $sourceItem->update(['cost_package_id' => $targetPackage->id]);
                    $moved++;
                } else {
                    $errors[] = "Row {$index}: no target specified.";
                }
            }
        });

        return new ReassignLineItemsResult($moved, $merged, $errors);
    }

    private function mergeForecasts(LineItem $source, LineItem $target): void
    {
        $sourceForecasts = $source->forecasts()->get();

        foreach ($sourceForecasts as $sourceForecast) {
            $targetForecast = LineItemForecast::where('line_item_id', $target->id)
                ->where('forecast_period_id', $sourceForecast->forecast_period_id)
                ->first();

            if ($targetForecast) {
                $ctdQty = (float) $targetForecast->ctd_qty + (float) $sourceForecast->ctd_qty;
                $ctdRate = (float) $target->original_rate;
                $ctdAmount = (float) $targetForecast->ctd_amount + (float) $sourceForecast->ctd_amount;

                $ctcQty = max(0, (float) $target->original_qty - $ctdQty);
                $ctcAmount = $ctcQty * $ctdRate;

                $fcacAmount = $ctdAmount + $ctcAmount;
                $totalQty = $ctdQty + $ctcQty;
                $fcacRate = $totalQty > 0 ? $fcacAmount / $totalQty : 0;

                $variance = (float) $targetForecast->previous_amount - $fcacAmount;

                $targetForecast->update([
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
            } else {
                // No matching period on target — reassign the forecast record
                $sourceForecast->update(['line_item_id' => $target->id]);
            }
        }
    }
}
