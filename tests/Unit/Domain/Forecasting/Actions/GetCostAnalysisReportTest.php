<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\BudgetAdjustment;
use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\CostPackage;
use App\Models\ForecastPeriod;
use App\Models\LineItem;
use App\Models\LineItemForecast;
use App\Models\Project;
use App\Models\User;
use Domain\Forecasting\Actions\GetCostAnalysisReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetCostAnalysisReportTest extends TestCase
{
    use RefreshDatabase;

    private function seedProject(): array
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        $previousPeriod = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2023-12-01',
            'is_current' => false,
        ]);

        $currentPeriod = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4 - Construction',
            'code' => '401CB00',
            'description' => 'Concrete Barriers',
            'category' => '401S - Structure',
            'baseline_budget' => 100000,
            'approved_budget' => 120000,
            'sort_order' => 1,
        ]);

        $package = CostPackage::create([
            'project_id' => $project->id,
            'control_account_id' => $account->id,
            'name' => 'Barriers',
            'sort_order' => 1,
        ]);

        $item = LineItem::create([
            'cost_package_id' => $package->id,
            'description' => 'TL5 Barrier',
            'unit_of_measure' => 'LM',
            'original_qty' => 100,
            'original_rate' => 1000,
            'original_amount' => 100000,
            'sort_order' => 1,
        ]);

        LineItemForecast::create([
            'line_item_id' => $item->id,
            'forecast_period_id' => $previousPeriod->id,
            'ctd_amount' => 30000,
            'ctc_amount' => 70000,
            'fcac_amount' => 100000,
        ]);

        LineItemForecast::create([
            'line_item_id' => $item->id,
            'forecast_period_id' => $currentPeriod->id,
            'ctd_amount' => 50000,
            'ctc_amount' => 60000,
            'fcac_amount' => 110000,
            'comments' => 'Scope growth',
        ]);

        return [$project, $previousPeriod, $currentPeriod, $account, $item];
    }

    public function test_returns_correct_structure(): void
    {
        [$project, , $currentPeriod] = $this->seedProject();

        $action = new GetCostAnalysisReport;
        $result = $action->execute($project, $currentPeriod);

        $this->assertArrayHasKey('project', $result);
        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('previousPeriod', $result);
        $this->assertArrayHasKey('rows', $result);
        $this->assertArrayHasKey('totals', $result);
    }

    public function test_calculates_value_columns(): void
    {
        [$project, , $currentPeriod] = $this->seedProject();

        $action = new GetCostAnalysisReport;
        $result = $action->execute($project, $currentPeriod);

        $row = $result['rows'][0];

        $this->assertEquals(100000.0, $row['baseline_budget']);
        $this->assertEquals(120000.0, $row['approved_budget']);
    }

    public function test_calculates_cost_columns(): void
    {
        [$project, , $currentPeriod] = $this->seedProject();

        $action = new GetCostAnalysisReport;
        $result = $action->execute($project, $currentPeriod);

        $row = $result['rows'][0];

        $this->assertEquals(50000.0, $row['cost_to_date']);
        $this->assertEquals(60000.0, $row['estimate_to_complete']);
        $this->assertEquals(110000.0, $row['estimated_final_cost']);
    }

    public function test_calculates_monthly_cost_from_period_difference(): void
    {
        [$project, , $currentPeriod] = $this->seedProject();

        $action = new GetCostAnalysisReport;
        $result = $action->execute($project, $currentPeriod);

        $row = $result['rows'][0];

        // monthly_cost = current CTD (50000) - previous CTD (30000) = 20000
        $this->assertEquals(20000.0, $row['monthly_cost']);
    }

    public function test_calculates_last_month_efc_and_movement(): void
    {
        [$project, , $currentPeriod] = $this->seedProject();

        $action = new GetCostAnalysisReport;
        $result = $action->execute($project, $currentPeriod);

        $row = $result['rows'][0];

        // last_month_efc = previous period's fcac_amount = 100000
        $this->assertEquals(100000.0, $row['last_month_efc']);

        // monthly_efc_movement = current fcac (110000) - previous fcac (100000) = 10000
        $this->assertEquals(10000.0, $row['monthly_efc_movement']);
    }

    public function test_includes_monthly_comments(): void
    {
        [$project, , $currentPeriod] = $this->seedProject();

        $action = new GetCostAnalysisReport;
        $result = $action->execute($project, $currentPeriod);

        $row = $result['rows'][0];

        $this->assertEquals('Scope growth', $row['monthly_comments']);
    }

    public function test_finds_previous_period_automatically(): void
    {
        [$project, $previousPeriod, $currentPeriod] = $this->seedProject();

        $action = new GetCostAnalysisReport;
        $result = $action->execute($project, $currentPeriod);

        $this->assertNotNull($result['previousPeriod']);
        $this->assertEquals($previousPeriod->id, $result['previousPeriod']->id);
    }

    public function test_handles_no_previous_period(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 0]);

        $period = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '',
            'code' => '001',
            'description' => 'Test',
            'baseline_budget' => 50000,
            'approved_budget' => 50000,
            'sort_order' => 1,
        ]);

        $action = new GetCostAnalysisReport;
        $result = $action->execute($project, $period);

        $this->assertNull($result['previousPeriod']);
        $this->assertEquals(0.0, $result['rows'][0]['monthly_cost']);
        $this->assertEquals(0.0, $result['rows'][0]['last_month_efc']);
    }

    public function test_aggregates_totals_correctly(): void
    {
        [$project, , $currentPeriod] = $this->seedProject();

        $action = new GetCostAnalysisReport;
        $result = $action->execute($project, $currentPeriod);

        $this->assertEquals(100000.0, $result['totals']['baseline_budget']);
        $this->assertEquals(120000.0, $result['totals']['approved_budget']);
        $this->assertEquals(50000.0, $result['totals']['cost_to_date']);
        $this->assertEquals(60000.0, $result['totals']['estimate_to_complete']);
        $this->assertEquals(110000.0, $result['totals']['estimated_final_cost']);
    }

    public function test_handles_project_with_no_periods(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Empty', 'original_budget' => 0]);

        $action = new GetCostAnalysisReport;
        $result = $action->execute($project);

        $this->assertNull($result['period']);
        $this->assertNull($result['previousPeriod']);
        $this->assertEmpty($result['rows']);
    }

    public function test_budget_movement_reflects_adjustment(): void
    {
        [$project, , $currentPeriod, $account] = $this->seedProject();

        BudgetAdjustment::create([
            'control_account_id' => $account->id,
            'forecast_period_id' => $currentPeriod->id,
            'user_id' => User::factory()->create()->id,
            'amount' => 20000,
            'previous_approved_budget' => 100000,
            'new_approved_budget' => 120000,
            'reason' => 'Scope change',
        ]);

        $action = new GetCostAnalysisReport;
        $result = $action->execute($project, $currentPeriod);

        $row = $result['rows'][0];

        // last_month_approved_budget should be 100000 (from adjustment)
        $this->assertEquals(100000.0, $row['last_month_approved_budget']);
        // month_budget_movement = 120000 - 100000 = 20000
        $this->assertEquals(20000.0, $row['month_budget_movement']);
    }
}
