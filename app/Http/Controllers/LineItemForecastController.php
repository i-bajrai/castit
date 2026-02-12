<?php

namespace App\Http\Controllers;

use App\Models\LineItemForecast;
use App\Models\Project;
use Domain\Forecasting\Actions\ImportForecastsFromCsv;
use Domain\Forecasting\Actions\SaveLineItemForecasts;
use Domain\Forecasting\DataTransferObjects\LineItemForecastData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LineItemForecastController extends Controller
{
    public function store(Request $request, Project $project, SaveLineItemForecasts $action): RedirectResponse
    {
        Gate::authorize('update', $project);

        $period = $project->forecastPeriods()
            ->where('period_date', now()->startOfMonth())
            ->firstOrFail();

        abort_if(! $period->isEditable(), 403, 'Period is not editable.');

        $validated = $request->validate([
            'forecasts' => 'required|array',
            'forecasts.*.line_item_id' => 'required|exists:line_items,id',
            'forecasts.*.ctd_qty' => 'required|numeric',
            'forecasts.*.comments' => 'nullable|string|max:2000',
        ]);

        $dtos = array_map(fn (array $f) => new LineItemForecastData(
            lineItemId: (int) $f['line_item_id'],
            ctdQty: (float) $f['ctd_qty'],
            comments: $f['comments'] ?? null,
        ), $validated['forecasts']);

        $action->execute($period, $dtos);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Line item forecasts saved.');
    }

    public function updateCtdQty(Request $request, Project $project, LineItemForecast $forecast): JsonResponse
    {
        Gate::authorize('update', $project);

        abort_if(! $forecast->forecastPeriod->isEditable(), 403, 'Period is not editable.');

        $validated = $request->validate([
            'ctd_qty' => 'required|numeric',
        ]);

        $lineItem = $forecast->lineItem;
        $origRate = (float) $lineItem->original_rate;
        $origQty = (float) $lineItem->original_qty;

        $forecast->update([
            'ctd_qty' => (float) $validated['ctd_qty'],
            'ctd_rate' => $origRate,
            'ctc_rate' => $origRate,
            'fcac_qty' => $origQty,
            'fcac_rate' => $origRate,
        ]);

        return response()->json(['status' => 'ok']);
    }

    public function import(Request $request, Project $project, ImportForecastsFromCsv $action): RedirectResponse
    {
        Gate::authorize('update', $project);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $rows = [];

        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            $header = fgetcsv($handle);

            if ($header) {
                $header = array_map(fn ($h) => strtolower(trim($h)), $header);

                while (($data = fgetcsv($handle)) !== false) {
                    if (count($data) === count($header)) {
                        $rows[] = array_combine($header, $data);
                    }
                }
            }

            fclose($handle);
        }

        if (empty($rows)) {
            return redirect()->route('projects.settings', $project)
                ->with('error', 'No valid rows found in CSV.');
        }

        $result = $action->execute($project, $rows);

        // If unassigned items were created, redirect to the resolver page
        if ($result->created > 0) {
            return redirect()->route('projects.unassigned', $project)
                ->with('success', $result->summary())
                ->with('import_errors', $result->errors);
        }

        $flash = $result->imported > 0 ? 'success' : 'error';

        return redirect()->route('projects.settings', $project)
            ->with($flash, $result->summary())
            ->with('import_errors', $result->errors);
    }

    public function updateComment(Request $request, Project $project, LineItemForecast $forecast): JsonResponse
    {
        Gate::authorize('update', $project);

        abort_if(! $forecast->forecastPeriod->isEditable(), 403, 'Period is not editable.');

        $validated = $request->validate([
            'comments' => 'nullable|string|max:2000',
        ]);

        $forecast->update(['comments' => $validated['comments']]);

        return response()->json(['status' => 'ok']);
    }
}
