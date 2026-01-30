<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\CostPackage;
use App\Models\ForecastPeriod;
use App\Models\LineItem;
use App\Models\LineItemForecast;
use App\Models\Project;
use App\Models\User;
use Domain\Forecasting\Actions\SaveLineItemForecasts;
use Domain\Forecasting\DataTransferObjects\LineItemForecastData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaveLineItemForecastsTest extends TestCase
{
    use RefreshDatabase;

    private function seedData(): array
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        $period = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        $controlAccount = ControlAccount::create([
            'project_id' => $project->id,
            'code' => 'CA-001',
            'description' => 'Test Control Account',
            'phase' => 'Phase 1',
            'category' => 'Labor',
            'sort_order' => 1,
        ]);

        $package = CostPackage::create([
            'project_id' => $project->id,
            'control_account_id' => $controlAccount->id,
            'name' => 'Foundation',
            'sort_order' => 1,
        ]);

        $item = LineItem::create([
            'cost_package_id' => $package->id,
            'description' => 'Concrete',
            'original_qty' => 100,
            'original_rate' => 250,
            'original_amount' => 25000,
            'sort_order' => 1,
        ]);

        return [$period, $item];
    }

    public function test_creates_new_forecast_records(): void
    {
        [$period, $item] = $this->seedData();

        $action = new SaveLineItemForecasts;
        $action->execute($period, [
            new LineItemForecastData(
                lineItemId: $item->id,
                ctdQty: 50,
                comments: 'Test',
            ),
        ]);

        // ctdAmount = 50 * 250 = 12500, ctcQty = 100 - 50 = 50, ctcAmount = 50 * 250 = 12500
        $this->assertDatabaseHas('line_item_forecasts', [
            'line_item_id' => $item->id,
            'forecast_period_id' => $period->id,
            'ctd_amount' => 12500,
            'ctc_amount' => 12500,
        ]);
    }

    public function test_updates_existing_forecast_records(): void
    {
        [$period, $item] = $this->seedData();

        LineItemForecast::create([
            'line_item_id' => $item->id,
            'forecast_period_id' => $period->id,
            'ctd_amount' => 5000,
            'ctc_amount' => 5000,
            'fcac_amount' => 10000,
        ]);

        $action = new SaveLineItemForecasts;
        $action->execute($period, [
            new LineItemForecastData(
                lineItemId: $item->id,
                ctdQty: 50,
            ),
        ]);

        $this->assertEquals(1, LineItemForecast::where('line_item_id', $item->id)->count());

        // ctdAmount = 50 * 250 = 12500, ctcAmount = 50 * 250 = 12500
        $forecast = LineItemForecast::where('line_item_id', $item->id)->first();
        $this->assertEquals(12500, (float) $forecast->ctd_amount);
        $this->assertEquals(12500, (float) $forecast->ctc_amount);
    }

    public function test_calculates_fcac_and_variance(): void
    {
        [$period, $item] = $this->seedData();

        // Create existing forecast with previous_amount
        LineItemForecast::create([
            'line_item_id' => $item->id,
            'forecast_period_id' => $period->id,
            'previous_amount' => 30000,
            'ctd_amount' => 0,
            'ctc_amount' => 0,
            'fcac_amount' => 0,
        ]);

        $action = new SaveLineItemForecasts;
        $action->execute($period, [
            new LineItemForecastData(
                lineItemId: $item->id,
                ctdQty: 50,
            ),
        ]);

        // fcac = 12500 + 12500 = 25000, variance = 30000 - 25000 = 5000
        $forecast = LineItemForecast::where('line_item_id', $item->id)->first();
        $this->assertEquals(25000, (float) $forecast->fcac_amount);
        $this->assertEquals(5000, (float) $forecast->variance);
    }
}
