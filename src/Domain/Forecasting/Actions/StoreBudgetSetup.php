<?php

namespace Domain\Forecasting\Actions;

use App\Models\ControlAccount;
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
        });
    }
}
