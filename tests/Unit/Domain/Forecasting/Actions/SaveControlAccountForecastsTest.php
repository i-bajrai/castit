<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\ControlAccountForecast;
use App\Models\ForecastPeriod;
use App\Models\Project;
use App\Models\User;
use Domain\Forecasting\Actions\SaveControlAccountForecasts;
use Domain\Forecasting\DataTransferObjects\ControlAccountForecastData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaveControlAccountForecastsTest extends TestCase
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

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4',
            'code' => '401AN00',
            'description' => 'Civil',
            'category' => 'Civil',
            'baseline_budget' => 100000,
            'approved_budget' => 100000,
            'sort_order' => 1,
        ]);

        return [$period, $account];
    }

    public function test_creates_new_forecast_records(): void
    {
        [$period, $account] = $this->seedData();

        $action = new SaveControlAccountForecasts;
        $action->execute($period, [
            new ControlAccountForecastData(
                controlAccountId: $account->id,
                monthlyCost: 5000,
                costToDate: 50000,
                estimateToComplete: 45000,
                monthlyComments: 'Test',
            ),
        ]);

        $this->assertDatabaseHas('control_account_forecasts', [
            'control_account_id' => $account->id,
            'forecast_period_id' => $period->id,
            'cost_to_date' => 50000,
            'estimate_to_complete' => 45000,
        ]);
    }

    public function test_updates_existing_records(): void
    {
        [$period, $account] = $this->seedData();

        ControlAccountForecast::create([
            'control_account_id' => $account->id,
            'forecast_period_id' => $period->id,
            'monthly_cost' => 1000,
            'cost_to_date' => 10000,
            'estimate_to_complete' => 90000,
            'estimated_final_cost' => 100000,
        ]);

        $action = new SaveControlAccountForecasts;
        $action->execute($period, [
            new ControlAccountForecastData(
                controlAccountId: $account->id,
                monthlyCost: 5000,
                costToDate: 50000,
                estimateToComplete: 45000,
            ),
        ]);

        $this->assertEquals(1, ControlAccountForecast::where('control_account_id', $account->id)->count());

        $forecast = ControlAccountForecast::where('control_account_id', $account->id)->first();
        $this->assertEquals(50000, (float) $forecast->cost_to_date);
        $this->assertEquals(45000, (float) $forecast->estimate_to_complete);
    }

    public function test_calculates_estimated_final_cost_and_efc_movement(): void
    {
        [$period, $account] = $this->seedData();

        ControlAccountForecast::create([
            'control_account_id' => $account->id,
            'forecast_period_id' => $period->id,
            'last_month_efc' => 90000,
            'cost_to_date' => 0,
            'estimate_to_complete' => 0,
            'estimated_final_cost' => 0,
        ]);

        $action = new SaveControlAccountForecasts;
        $action->execute($period, [
            new ControlAccountForecastData(
                controlAccountId: $account->id,
                costToDate: 50000,
                estimateToComplete: 45000,
            ),
        ]);

        $forecast = ControlAccountForecast::where('control_account_id', $account->id)->first();
        $this->assertEquals(95000, (float) $forecast->estimated_final_cost); // 50000 + 45000
        $this->assertEquals(5000, (float) $forecast->efc_movement); // 95000 - 90000
    }
}
