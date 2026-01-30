<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\ControlAccountForecast;
use App\Models\ForecastPeriod;
use App\Models\Project;
use App\Models\User;
use Domain\Forecasting\Actions\GetControlAccountSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetControlAccountSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function seedProject(): array
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        $period = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4 - Construction',
            'code' => '401AN00',
            'description' => 'Anti Graffiti',
            'category' => '401C - Civil',
            'baseline_budget' => 339264,
            'approved_budget' => 790194,
            'sort_order' => 1,
        ]);

        ControlAccountForecast::create([
            'control_account_id' => $account->id,
            'forecast_period_id' => $period->id,
            'monthly_cost' => -61888,
            'cost_to_date' => 466750,
            'estimate_to_complete' => 525603,
            'estimated_final_cost' => 992352,
            'last_month_efc' => 948803,
            'efc_movement' => 43549,
        ]);

        return [$project, $period, $account];
    }

    public function test_returns_control_accounts_with_current_period(): void
    {
        [$project, $period] = $this->seedProject();

        $action = new GetControlAccountSummary;
        $result = $action->execute($project);

        $this->assertSame($period->id, $result['period']->id);
        $this->assertCount(1, $result['accounts']);
        $this->assertEquals('401AN00', $result['accounts']->first()->code);
    }

    public function test_loads_forecasts_for_current_period(): void
    {
        [$project] = $this->seedProject();

        $action = new GetControlAccountSummary;
        $result = $action->execute($project);

        $forecast = $result['accounts']->first()->forecasts->first();
        $this->assertNotNull($forecast);
        $this->assertEquals(992352, $forecast->estimated_final_cost);
        $this->assertEquals(43549, $forecast->efc_movement);
    }

    public function test_returns_specific_period_when_provided(): void
    {
        [$project, , $account] = $this->seedProject();

        $oldPeriod = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2023-12-01',
            'is_current' => false,
        ]);

        ControlAccountForecast::create([
            'control_account_id' => $account->id,
            'forecast_period_id' => $oldPeriod->id,
            'monthly_cost' => 0,
            'cost_to_date' => 400000,
            'estimate_to_complete' => 548803,
            'estimated_final_cost' => 948803,
            'last_month_efc' => 900000,
            'efc_movement' => 48803,
        ]);

        $action = new GetControlAccountSummary;
        $result = $action->execute($project, $oldPeriod);

        $this->assertSame($oldPeriod->id, $result['period']->id);
        $forecast = $result['accounts']->first()->forecasts->first();
        $this->assertEquals(948803, $forecast->estimated_final_cost);
    }

    public function test_handles_project_with_no_control_accounts(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Empty', 'original_budget' => 0]);

        $action = new GetControlAccountSummary;
        $result = $action->execute($project);

        $this->assertNull($result['period']);
        $this->assertCount(0, $result['accounts']);
    }

    public function test_accounts_ordered_by_sort_order(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Ordered', 'original_budget' => 100000]);

        ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4',
            'code' => 'B',
            'description' => 'Second',
            'category' => 'Cat',
            'baseline_budget' => 100,
            'approved_budget' => 100,
            'sort_order' => 2,
        ]);

        ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4',
            'code' => 'A',
            'description' => 'First',
            'category' => 'Cat',
            'baseline_budget' => 200,
            'approved_budget' => 200,
            'sort_order' => 1,
        ]);

        $action = new GetControlAccountSummary;
        $result = $action->execute($project);

        $this->assertEquals('A', $result['accounts']->first()->code);
        $this->assertEquals('B', $result['accounts']->last()->code);
    }
}
