<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\ControlAccountForecast;
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

        $action = new OpenNewForecastPeriod;
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

        $action = new OpenNewForecastPeriod;
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

        $package = CostPackage::create([
            'project_id' => $project->id,
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
            'previous_amount' => 1000,
            'ctd_qty' => 6,
            'ctd_rate' => 100,
            'ctd_amount' => 600,
            'ctc_qty' => 4,
            'ctc_rate' => 100,
            'ctc_amount' => 400,
            'fcac_rate' => 100,
            'fcac_amount' => 1000,
            'variance' => 0,
        ]);

        $action = new OpenNewForecastPeriod;
        $newPeriod = $action->execute($project, Carbon::parse('2024-02-01'));

        $newForecast = LineItemForecast::where('forecast_period_id', $newPeriod->id)
            ->where('line_item_id', $item->id)
            ->first();

        $this->assertNotNull($newForecast);
        $this->assertEquals(10, $newForecast->previous_qty); // ctd_qty + ctc_qty
        $this->assertEquals(100, (float) $newForecast->previous_rate); // fcac_rate
        $this->assertEquals(1000, (float) $newForecast->previous_amount); // fcac_amount
        $this->assertEquals(0, (float) $newForecast->ctd_amount);
        $this->assertEquals(0, (float) $newForecast->ctc_amount);
        $this->assertEquals(0, (float) $newForecast->fcac_amount);
    }

    public function test_carries_forward_control_account_forecasts(): void
    {
        $project = $this->createProject();

        $oldPeriod = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4',
            'code' => '401AN00',
            'description' => 'Test',
            'category' => 'Civil',
            'baseline_budget' => 339264,
            'approved_budget' => 790194,
            'sort_order' => 1,
        ]);

        ControlAccountForecast::create([
            'control_account_id' => $account->id,
            'forecast_period_id' => $oldPeriod->id,
            'monthly_cost' => -61888,
            'cost_to_date' => 466750,
            'estimate_to_complete' => 525603,
            'estimated_final_cost' => 992352,
            'last_month_efc' => 948803,
            'efc_movement' => 43549,
        ]);

        $action = new OpenNewForecastPeriod;
        $newPeriod = $action->execute($project, Carbon::parse('2024-02-01'));

        $newForecast = ControlAccountForecast::where('forecast_period_id', $newPeriod->id)
            ->where('control_account_id', $account->id)
            ->first();

        $this->assertNotNull($newForecast);
        $this->assertEquals(790194, (float) $newForecast->last_month_approved_budget);
        $this->assertEquals(992352, (float) $newForecast->last_month_efc);
        $this->assertEquals(0, (float) $newForecast->monthly_cost);
        $this->assertEquals(0, (float) $newForecast->cost_to_date);
        $this->assertEquals(0, (float) $newForecast->efc_movement);
    }
}
