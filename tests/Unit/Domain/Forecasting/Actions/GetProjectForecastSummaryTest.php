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
use Domain\Forecasting\Actions\GetProjectForecastSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetProjectForecastSummaryTest extends TestCase
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
            'phase' => 'Construction',
            'code' => '001',
            'description' => 'Test Account',
            'category' => 'Civil',
            'baseline_budget' => 50000,
            'approved_budget' => 50000,
            'sort_order' => 1,
        ]);

        $package = CostPackage::create([
            'project_id' => $project->id,
            'control_account_id' => $account->id,
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
            'forecast_period_id' => $period->id,
            'previous_amount' => 1000,
            'ctd_amount' => 600,
            'ctc_amount' => 400,
            'fcac_amount' => 1000,
            'variance' => 0,
        ]);

        return [$project, $period, $package, $item];
    }

    public function test_returns_project_summary_with_current_period(): void
    {
        [$project] = $this->seedProject();

        $action = new GetProjectForecastSummary;
        $result = $action->execute($project);

        $this->assertSame($project->id, $result['project']->id);
        $this->assertNotNull($result['period']);
        $this->assertCount(1, $result['accounts']);
        $this->assertEquals(1000.0, $result['totals']['original_budget']);
        $this->assertEquals(1000.0, $result['totals']['previous_fcac']);
        $this->assertEquals(600.0, $result['totals']['ctd']);
        $this->assertEquals(400.0, $result['totals']['ctc']);
        $this->assertEquals(1000.0, $result['totals']['fcac']);
        $this->assertEquals(0.0, $result['totals']['variance']);
    }

    public function test_returns_specific_period_when_provided(): void
    {
        [$project, , , $item] = $this->seedProject();

        $oldPeriod = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2023-12-01',
            'is_current' => false,
        ]);

        LineItemForecast::create([
            'line_item_id' => $item->id,
            'forecast_period_id' => $oldPeriod->id,
            'previous_amount' => 800,
            'ctd_amount' => 300,
            'ctc_amount' => 500,
            'fcac_amount' => 800,
            'variance' => -200,
        ]);

        $action = new GetProjectForecastSummary;
        $result = $action->execute($project, $oldPeriod);

        $this->assertSame($oldPeriod->id, $result['period']->id);
        $this->assertEquals(800.0, $result['totals']['previous_fcac']);
        $this->assertEquals(-200.0, $result['totals']['variance']);
    }

    public function test_handles_project_with_no_forecast_periods(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Empty', 'original_budget' => 0]);

        $action = new GetProjectForecastSummary;
        $result = $action->execute($project);

        $this->assertNull($result['period']);
        $this->assertCount(0, $result['accounts']);
        $this->assertEquals(0.0, $result['totals']['fcac']);
    }

    public function test_aggregates_totals_across_multiple_packages_and_items(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Multi', 'original_budget' => 500000]);

        $period = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => 'Construction',
            'code' => '001',
            'description' => 'Test Account',
            'category' => 'Civil',
            'baseline_budget' => 100000,
            'approved_budget' => 100000,
            'sort_order' => 1,
        ]);

        foreach (['Pkg A', 'Pkg B'] as $i => $name) {
            $pkg = CostPackage::create([
                'project_id' => $project->id,
                'control_account_id' => $account->id,
                'item_no' => (string) ($i + 1),
                'name' => $name,
                'sort_order' => $i + 1,
            ]);

            $item = LineItem::create([
                'cost_package_id' => $pkg->id,
                'item_no' => (string) ($i + 1),
                'description' => "Item in {$name}",
                'unit_of_measure' => 'EA',
                'original_qty' => 10,
                'original_rate' => 100,
                'original_amount' => 1000,
                'sort_order' => 1,
            ]);

            LineItemForecast::create([
                'line_item_id' => $item->id,
                'forecast_period_id' => $period->id,
                'previous_amount' => 1000,
                'ctd_amount' => 500,
                'ctc_amount' => 500,
                'fcac_amount' => 1000,
                'variance' => 0,
            ]);
        }

        $action = new GetProjectForecastSummary;
        $result = $action->execute($project);

        $this->assertCount(1, $result['accounts']);
        $this->assertCount(2, $result['accounts']->first()->costPackages);
        $this->assertEquals(2000.0, $result['totals']['original_budget']);
        $this->assertEquals(2000.0, $result['totals']['fcac']);
    }
}
