<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\CostPackage;
use App\Models\ForecastPeriod;
use App\Models\LineItem;
use App\Models\LineItemForecast;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportForecastsFromCsvTest extends TestCase
{
    use RefreshDatabase;

    private function seedImportData(): array
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create([
            'company_id' => $company->id,
            'name' => 'Test',
            'original_budget' => 100000,
            'start_date' => '2024-01-01',
            'end_date' => '2024-03-01',
        ]);

        $period1 = ForecastPeriod::create(['project_id' => $project->id, 'period_date' => '2024-01-01']);
        $period2 = ForecastPeriod::create(['project_id' => $project->id, 'period_date' => '2024-02-01']);
        $period3 = ForecastPeriod::create(['project_id' => $project->id, 'period_date' => '2024-03-01']);

        $controlAccount = ControlAccount::create([
            'project_id' => $project->id,
            'code' => 'CA-001',
            'description' => 'Test CA',
            'phase' => 'Phase 1',
            'sort_order' => 1,
        ]);

        $package = CostPackage::create([
            'project_id' => $project->id,
            'control_account_id' => $controlAccount->id,
            'name' => 'Foundation',
            'sort_order' => 1,
        ]);

        $item1 = LineItem::create([
            'cost_package_id' => $package->id,
            'item_no' => '006-001',
            'description' => 'Concrete',
            'original_qty' => 100,
            'original_rate' => 250,
            'original_amount' => 25000,
            'sort_order' => 1,
        ]);

        $item2 = LineItem::create([
            'cost_package_id' => $package->id,
            'item_no' => '006-002',
            'description' => 'Steel',
            'original_qty' => 50,
            'original_rate' => 500,
            'original_amount' => 25000,
            'sort_order' => 2,
        ]);

        // Create zero-filled forecasts (as SyncForecastPeriods would)
        foreach ([$period1, $period2, $period3] as $period) {
            foreach ([$item1, $item2] as $item) {
                LineItemForecast::create([
                    'line_item_id' => $item->id,
                    'forecast_period_id' => $period->id,
                    'previous_qty' => 0, 'previous_rate' => 0, 'previous_amount' => 0,
                    'ctd_qty' => 0, 'ctd_rate' => 0, 'ctd_amount' => 0,
                    'ctc_qty' => 0, 'ctc_rate' => 0, 'ctc_amount' => 0,
                    'fcac_rate' => 0, 'fcac_amount' => 0, 'variance' => 0,
                ]);
            }
        }

        return [$user, $project, $period1, $period2, $period3, $item1, $item2];
    }

    private function makeCsv(string $content): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('import.csv', $content);
    }

    public function test_owner_can_import_csv(): void
    {
        [$user, $project, $period1, , , $item1, $item2] = $this->seedImportData();

        $csv = $this->makeCsv("item_no,period,ctd_qty\n006-001,2024-01,80\n006-002,2024-01,30\n");

        $this->actingAs($user)
            ->post("/projects/{$project->id}/forecasts/import", ['csv_file' => $csv])
            ->assertRedirect(route('projects.settings', $project))
            ->assertSessionHas('success');

        // item1: ctd_qty=80, rate=250, ctd_amount=20000, ctc_qty=20, ctc_amount=5000, fcac=25000
        $this->assertDatabaseHas('line_item_forecasts', [
            'line_item_id' => $item1->id,
            'forecast_period_id' => $period1->id,
            'ctd_qty' => 80,
            'ctd_amount' => 20000,
            'ctc_qty' => 20,
            'ctc_amount' => 5000,
            'fcac_amount' => 25000,
        ]);

        // item2: ctd_qty=30, rate=500, ctd_amount=15000, ctc_qty=20, ctc_amount=10000, fcac=25000
        $this->assertDatabaseHas('line_item_forecasts', [
            'line_item_id' => $item2->id,
            'forecast_period_id' => $period1->id,
            'ctd_qty' => 30,
            'ctd_amount' => 15000,
            'ctc_qty' => 20,
            'ctc_amount' => 10000,
            'fcac_amount' => 25000,
        ]);
    }

    public function test_skips_forecasts_already_set(): void
    {
        [$user, $project, $period1, , , $item1] = $this->seedImportData();

        // Set ctd_qty on period1 for item1
        LineItemForecast::where('line_item_id', $item1->id)
            ->where('forecast_period_id', $period1->id)
            ->update(['ctd_qty' => 50, 'ctd_amount' => 12500]);

        $csv = $this->makeCsv("item_no,period,ctd_qty\n006-001,2024-01,80\n");

        $this->actingAs($user)
            ->post("/projects/{$project->id}/forecasts/import", ['csv_file' => $csv])
            ->assertRedirect(route('projects.settings', $project));

        // Should NOT have been updated to 80
        $this->assertDatabaseHas('line_item_forecasts', [
            'line_item_id' => $item1->id,
            'forecast_period_id' => $period1->id,
            'ctd_qty' => 50,
        ]);
    }

    public function test_errors_on_unknown_item_no(): void
    {
        [$user, $project] = $this->seedImportData();

        $csv = $this->makeCsv("item_no,period,ctd_qty\nXXX-999,2024-01,80\n");

        $this->actingAs($user)
            ->post("/projects/{$project->id}/forecasts/import", ['csv_file' => $csv])
            ->assertRedirect(route('projects.settings', $project))
            ->assertSessionHas('import_errors');
    }

    public function test_errors_on_unknown_period(): void
    {
        [$user, $project] = $this->seedImportData();

        $csv = $this->makeCsv("item_no,period,ctd_qty\n006-001,2099-01,80\n");

        $this->actingAs($user)
            ->post("/projects/{$project->id}/forecasts/import", ['csv_file' => $csv])
            ->assertRedirect(route('projects.settings', $project))
            ->assertSessionHas('import_errors');
    }

    public function test_import_across_multiple_periods(): void
    {
        [$user, $project, $period1, $period2, , $item1] = $this->seedImportData();

        $csv = $this->makeCsv("item_no,period,ctd_qty\n006-001,2024-01,40\n006-001,2024-02,60\n");

        $this->actingAs($user)
            ->post("/projects/{$project->id}/forecasts/import", ['csv_file' => $csv])
            ->assertRedirect(route('projects.settings', $project))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('line_item_forecasts', [
            'line_item_id' => $item1->id,
            'forecast_period_id' => $period1->id,
            'ctd_qty' => 40,
        ]);

        $this->assertDatabaseHas('line_item_forecasts', [
            'line_item_id' => $item1->id,
            'forecast_period_id' => $period2->id,
            'ctd_qty' => 60,
        ]);
    }

    public function test_non_owner_cannot_import(): void
    {
        [, $project] = $this->seedImportData();
        $otherUser = User::factory()->create();

        $csv = $this->makeCsv("item_no,period,ctd_qty\n006-001,2024-01,80\n");

        $this->actingAs($otherUser)
            ->post("/projects/{$project->id}/forecasts/import", ['csv_file' => $csv])
            ->assertForbidden();
    }

    public function test_requires_csv_file(): void
    {
        [$user, $project] = $this->seedImportData();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/forecasts/import", [])
            ->assertSessionHasErrors('csv_file');
    }

    public function test_rejects_current_and_future_periods(): void
    {
        [$user, $project] = $this->seedImportData();

        $currentPeriod = now()->startOfMonth()->format('Y-m');

        $futurePeriod = now()->addMonth()->startOfMonth();
        ForecastPeriod::create(['project_id' => $project->id, 'period_date' => $futurePeriod]);
        LineItemForecast::create([
            'line_item_id' => \App\Models\LineItem::whereHas('costPackage', fn ($q) => $q->where('project_id', $project->id))->first()->id,
            'forecast_period_id' => ForecastPeriod::where('project_id', $project->id)->where('period_date', $futurePeriod)->first()->id,
            'previous_qty' => 0, 'previous_rate' => 0, 'previous_amount' => 0,
            'ctd_qty' => 0, 'ctd_rate' => 0, 'ctd_amount' => 0,
            'ctc_qty' => 0, 'ctc_rate' => 0, 'ctc_amount' => 0,
            'fcac_rate' => 0, 'fcac_amount' => 0, 'variance' => 0,
        ]);

        $futureKey = $futurePeriod->format('Y-m');
        $csv = $this->makeCsv("item_no,period,ctd_qty\n006-001,{$futureKey},80\n");

        $this->actingAs($user)
            ->post("/projects/{$project->id}/forecasts/import", ['csv_file' => $csv])
            ->assertRedirect(route('projects.settings', $project))
            ->assertSessionHas('import_errors');
    }

    public function test_empty_csv_returns_error(): void
    {
        [$user, $project] = $this->seedImportData();

        $csv = $this->makeCsv("item_no,period,ctd_qty\n");

        $this->actingAs($user)
            ->post("/projects/{$project->id}/forecasts/import", ['csv_file' => $csv])
            ->assertRedirect(route('projects.settings', $project))
            ->assertSessionHas('error');
    }
}
