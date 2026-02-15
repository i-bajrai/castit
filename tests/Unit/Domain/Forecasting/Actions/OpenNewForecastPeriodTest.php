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
use Domain\Forecasting\Actions\OpenNewForecastPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OpenNewForecastPeriodTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);

        return Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);
    }

    public function test_opens_new_period_and_locks_previous(): void
    {
        $project = $this->createProject();

        $oldPeriod = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        $action = app(OpenNewForecastPeriod::class);
        $newPeriod = $action->execute($project, Carbon::parse('2024-02-01'));

        $oldPeriod->refresh();

        $this->assertFalse($oldPeriod->is_current);
        $this->assertTrue($oldPeriod->isLocked());
        $this->assertTrue($newPeriod->is_current);
        $this->assertFalse($newPeriod->isLocked());
        $this->assertEquals('2024-02-01', $newPeriod->period_date->format('Y-m-d'));
    }

    public function test_opens_first_period_when_none_exists(): void
    {
        $project = $this->createProject();

        $action = app(OpenNewForecastPeriod::class);
        $period = $action->execute($project, Carbon::parse('2024-01-01'));

        $this->assertTrue($period->is_current);
        $this->assertEquals(1, $project->forecastPeriods()->count());
    }

    public function test_carries_forward_line_item_forecasts(): void
    {
        $project = $this->createProject();

        $oldPeriod = ForecastPeriod::create([
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
            'item_no' => '001',
            'name' => 'Package A',
            'sort_order' => 1,
        ]);

        $item = LineItem::create([
            'cost_package_id' => $package->id,
            'item_no' => '001',
            'description' => 'Item 1',
            'unit_of_measure' => 'EA',
            'original_qty' => 10,
            'original_rate' => 100,
            'original_amount' => 1000,
            'sort_order' => 1,
        ]);

        LineItemForecast::create([
            'line_item_id' => $item->id,
            'forecast_period_id' => $oldPeriod->id,
            'period_qty' => 6,
            'period_rate' => 100,
            'fcac_qty' => 10,
            'fcac_rate' => 100,
        ]);

        $action = app(OpenNewForecastPeriod::class);
        $newPeriod = $action->execute($project, Carbon::parse('2024-02-01'));

        $newForecast = LineItemForecast::where('forecast_period_id', $newPeriod->id)
            ->where('line_item_id', $item->id)
            ->first();

        $this->assertNotNull($newForecast);
        // New period starts with period_qty = 0, carries forward rate and FCAC
        $this->assertEquals(0, (float) $newForecast->period_qty);
        $this->assertEquals(100, (float) $newForecast->period_rate);
        $this->assertEquals(0, (float) $newForecast->period_amount);
        $this->assertEquals(10, (float) $newForecast->fcac_qty);
        $this->assertEquals(100, (float) $newForecast->fcac_rate);
        $this->assertEquals(1000, (float) $newForecast->fcac_amount);
    }
}
