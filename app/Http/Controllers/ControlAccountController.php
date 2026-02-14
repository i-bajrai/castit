<?php

namespace App\Http\Controllers;

use App\Models\ControlAccount;
use App\Models\ForecastPeriod;
use App\Models\LineItemForecast;
use App\Models\Project;
use Domain\Forecasting\Actions\CreateControlAccount;
use Domain\Forecasting\Actions\CreateCostPackage;
use Domain\Forecasting\Actions\CreateLineItem;
use Domain\Forecasting\Actions\DeleteControlAccount;
use Domain\Forecasting\Actions\UpdateControlAccount;
use Domain\Forecasting\DataTransferObjects\ControlAccountData;
use Domain\Forecasting\DataTransferObjects\CostPackageData;
use Domain\Forecasting\DataTransferObjects\LineItemData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ControlAccountController extends Controller
{
    public function bulkStore(Request $request, Project $project, CreateControlAccount $action): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'accounts' => 'required|array|min:1',
            'accounts.*.code' => 'required|string|max:255',
            'accounts.*.description' => 'required|string|max:255',
            'accounts.*.category' => 'nullable|string|max:255',
        ]);

        $existingCount = $project->controlAccounts()->count();

        foreach ($validated['accounts'] as $index => $account) {
            $data = new ControlAccountData(
                phase: '',
                code: $account['code'],
                description: $account['description'],
                category: $account['category'] ?? null,
                sortOrder: $existingCount + $index,
            );

            $action->execute($project, $data);
        }

        return redirect()->route('projects.budget', $project);
    }

    public function store(Request $request, Project $project, CreateControlAccount $action): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'phase' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'baseline_budget' => 'required|numeric|min:0',
            'approved_budget' => 'required|numeric|min:0',
            'sort_order' => 'required|integer|min:0',
        ]);

        $data = new ControlAccountData(
            phase: $validated['phase'],
            code: $validated['code'],
            description: $validated['description'],
            category: $validated['category'] ?? null,
            baselineBudget: (float) $validated['baseline_budget'],
            approvedBudget: (float) $validated['approved_budget'],
            sortOrder: (int) $validated['sort_order'],
        );

        $action->execute($project, $data);

        return redirect()->route('projects.settings', $project)
            ->with('success', 'Control account created.');
    }

    public function update(
        Request $request,
        Project $project,
        ControlAccount $controlAccount,
        UpdateControlAccount $action,
    ): RedirectResponse {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'phase' => 'required|string|max:255',
            'code' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'baseline_budget' => 'required|numeric|min:0',
            'approved_budget' => 'required|numeric|min:0',
            'sort_order' => 'required|integer|min:0',
        ]);

        $data = new ControlAccountData(
            phase: $validated['phase'],
            code: $validated['code'],
            description: $validated['description'],
            category: $validated['category'] ?? null,
            baselineBudget: (float) $validated['baseline_budget'],
            approvedBudget: (float) $validated['approved_budget'],
            sortOrder: (int) $validated['sort_order'],
        );

        $action->execute($controlAccount, $data);

        return redirect()->route('projects.settings', $project)
            ->with('success', 'Control account updated.');
    }

    public function destroy(
        Project $project,
        ControlAccount $controlAccount,
        DeleteControlAccount $action,
    ): RedirectResponse {
        Gate::authorize('update', $project);

        $action->execute($controlAccount);

        return redirect()->route('projects.settings', $project)
            ->with('success', 'Control account deleted.');
    }

    public function lineItems(Project $project, ControlAccount $controlAccount): View
    {
        Gate::authorize('view', $project);
        abort_unless($controlAccount->project_id === $project->id, 404);

        $costPackages = $controlAccount->costPackages()
            ->with(['lineItems' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('projects.control-accounts.line-items', [
            'project' => $project,
            'controlAccount' => $controlAccount,
            'costPackages' => $costPackages,
        ]);
    }

    public function importLineItems(
        Request $request,
        Project $project,
        ControlAccount $controlAccount,
        CreateCostPackage $createCostPackage,
        CreateLineItem $createLineItem,
    ): RedirectResponse {
        Gate::authorize('update', $project);
        abort_unless($controlAccount->project_id === $project->id, 404);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $hasLineItems = $controlAccount->costPackages()
            ->whereHas('lineItems')
            ->exists();

        if ($hasLineItems) {
            return redirect()->route('projects.control-accounts.line-items', [$project, $controlAccount])
                ->with('error', 'Cannot import CSV when line items already exist. Delete existing items first.');
        }

        $currentPeriod = ForecastPeriod::where('project_id', $project->id)
            ->orderByDesc('period_date')
            ->first();

        $handle = fopen($request->file('csv_file')->getPathname(), 'r');
        $header = fgetcsv($handle);
        $grouped = [];

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 8 || empty($row[0])) {
                continue;
            }

            $code = trim($row[0]);
            if (strtolower($code) !== strtolower($controlAccount->code)) {
                continue;
            }

            $pkgName = trim($row[1]);
            if (! isset($grouped[$pkgName])) {
                $grouped[$pkgName] = [];
            }

            $grouped[$pkgName][] = [
                'item_no' => trim($row[2]),
                'description' => trim($row[3]),
                'unit_of_measure' => trim($row[4]),
                'qty' => (float) $row[5],
                'rate' => (float) $row[6],
                'amount' => (float) $row[7],
            ];
        }

        fclose($handle);

        if (empty($grouped)) {
            return redirect()->route('projects.control-accounts.line-items', [$project, $controlAccount])
                ->with('error', 'No matching rows found for '.$controlAccount->code);
        }

        $existingPackages = $controlAccount->costPackages()->pluck('id', 'name');
        $existingPkgCount = $existingPackages->count();

        DB::transaction(function () use ($controlAccount, $grouped, $createCostPackage, $createLineItem, $currentPeriod, $existingPackages, $existingPkgCount) {
            $pkgIndex = $existingPkgCount;
            foreach ($grouped as $pkgName => $items) {
                if ($existingPackages->has($pkgName)) {
                    $package = $controlAccount->costPackages()->find($existingPackages[$pkgName]);
                    $package->lineItems()->delete();
                } else {
                    $pkgData = new CostPackageData(
                        name: $pkgName,
                        sortOrder: $pkgIndex++,
                        controlAccountId: $controlAccount->id,
                    );

                    $package = $createCostPackage->execute($controlAccount, $pkgData);
                }

                foreach ($items as $liIndex => $item) {
                    $liData = new LineItemData(
                        description: $item['description'],
                        itemNo: $item['item_no'] ?: null,
                        unitOfMeasure: $item['unit_of_measure'] ?: null,
                        originalQty: $item['qty'],
                        originalRate: $item['rate'],
                        originalAmount: $item['amount'],
                        sortOrder: $liIndex,
                    );

                    $lineItem = $createLineItem->execute($package, $liData);

                    if ($currentPeriod) {
                        LineItemForecast::create([
                            'line_item_id' => $lineItem->id,
                            'forecast_period_id' => $currentPeriod->id,
                            'period_rate' => $item['rate'],
                            'fcac_qty' => $item['qty'],
                            'fcac_rate' => $item['rate'],
                        ]);
                    }
                }
            }
        });

        $totalItems = collect($grouped)->flatten(1)->count();

        return redirect()->route('projects.control-accounts.line-items', [$project, $controlAccount])
            ->with('success', count($grouped).' cost package(s) with '.$totalItems.' line item(s) imported.');
    }
}
