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

        $action = app(SaveLineItemForecasts::class);
        $action->execute($period, [
            new LineItemForecastData(
                lineItemId: $item->id,
                periodQty: 50,
                comments: 'Test',
            ),
        ]);

        // period_amount = 50 * 250 = 12500, fcac = 100 * 250 = 25000
        $this->assertDatabaseHas('line_item_forecasts', [
            'line_item_id' => $item->id,
            'forecast_period_id' => $period->id,
            'period_qty' => 50,
            'period_rate' => 250,
            'fcac_amount' => 25000,
        ]);
    }

    public function test_updates_existing_forecast_records(): void
    {
        [$period, $item] = $this->seedData();

        LineItemForecast::create([
            'line_item_id' => $item->id,
            'forecast_period_id' => $period->id,
            'period_qty' => 20,
            'period_rate' => 250,
            'fcac_qty' => 100,
            'fcac_rate' => 250,
        ]);

        $action = app(SaveLineItemForecasts::class);
        $action->execute($period, [
            new LineItemForecastData(
                lineItemId: $item->id,
                periodQty: 50,
            ),
        ]);

        $this->assertEquals(1, LineItemForecast::where('line_item_id', $item->id)->count());

        // period_amount = 50 * 250 = 12500, fcac = 100 * 250 = 25000
        $forecast = LineItemForecast::where('line_item_id', $item->id)->first();
        $this->assertEquals(12500, (float) $forecast->period_amount);
        $this->assertEquals(25000, (float) $forecast->fcac_amount);
    }

    public function test_calculates_fcac(): void
    {
        [$period, $item] = $this->seedData();

        $action = app(SaveLineItemForecasts::class);
        $action->execute($period, [
            new LineItemForecastData(
                lineItemId: $item->id,
                periodQty: 50,
            ),
        ]);

        // fcac = orig_qty * rate = 100 * 250 = 25000
        $forecast = LineItemForecast::where('line_item_id', $item->id)->first();
        $this->assertEquals(25000, (float) $forecast->fcac_amount);
        $this->assertEquals(100, (float) $forecast->fcac_qty);
        $this->assertEquals(250, (float) $forecast->fcac_rate);
    }
}
