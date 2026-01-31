<?php

namespace Domain\Forecasting\Actions;

use App\Models\ControlAccount;
use App\Models\LineItemForecast;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class StoreBudgetSetup
{
    public function execute(Project $project, array $accounts): void
    {
        DB::transaction(function () use ($project, $accounts) {
            foreach ($accounts as $accountData) {
                $ca = ControlAccount::where('id', $accountData['control_account_id'])
                    ->where('project_id', $project->id)
                    ->firstOrFail();

                $ca->update([
                    'baseline_budget' => $accountData['baseline_budget'],
                    'approved_budget' => $accountData['baseline_budget'],
                ]);

                if (! empty($accountData['packages'])) {
                    foreach ($accountData['packages'] as $pkgIndex => $pkgData) {
                        $package = $ca->costPackages()->create([
                            'project_id' => $project->id,
                            'item_no' => $pkgData['item_no'] ?? null,
                            'name' => $pkgData['name'],
                            'sort_order' => $pkgIndex,
                        ]);

                        foreach ($pkgData['line_items'] as $liIndex => $liData) {
                            $package->lineItems()->create([
                                'item_no' => $liData['item_no'] ?? null,
                                'description' => $liData['description'],
                                'unit_of_measure' => $liData['unit_of_measure'] ?? null,
                                'original_qty' => $liData['qty'],
                                'original_rate' => $liData['rate'],
                                'original_amount' => $liData['amount'],
                                'sort_order' => $liIndex,
                            ]);
                        }
                    }
                }
            }

            // Create forecast records for new line items across all existing periods
            (new SyncForecastPeriods)->execute($project);

            // Initialize forecasts with original budget as the baseline:
            // previous = original, CTC = original (since CTD is 0), FCAC = original
            $periods = $project->forecastPeriods()->pluck('id');
            if ($periods->isNotEmpty()) {
                $lineItems = $project->controlAccounts()
                    ->with('costPackages.lineItems')
                    ->get()
                    ->flatMap(fn ($ca) => $ca->costPackages->flatMap->lineItems);

                foreach ($lineItems as $item) {
                    $origQty = (float) $item->original_qty;
                    $origRate = (float) $item->original_rate;
                    $origAmount = (float) $item->original_amount;

                    LineItemForecast::where('line_item_id', $item->id)
                        ->whereIn('forecast_period_id', $periods)
                        ->where('previous_amount', 0)
                        ->where('fcac_amount', 0)
                        ->update([
                            'previous_qty' => $origQty,
                            'previous_rate' => $origRate,
                            'previous_amount' => $origAmount,
                            'ctc_qty' => $origQty,
                            'ctc_rate' => $origRate,
                            'ctc_amount' => $origAmount,
                            'fcac_rate' => $origRate,
                            'fcac_amount' => $origAmount,
                            'variance' => 0,
                        ]);
                }
            }
        });
    }
}
