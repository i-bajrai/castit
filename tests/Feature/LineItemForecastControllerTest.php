<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CostPackage;
use App\Models\ForecastPeriod;
use App\Models\LineItem;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LineItemForecastControllerTest extends TestCase
{
    use RefreshDatabase;

    private function seedData(): array
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        $period = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => now()->startOfMonth()->toDateString(),
        ]);

        $package = CostPackage::create([
            'project_id' => $project->id,
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

        return [$user, $project, $period, $package, $item];
    }

    public function test_owner_can_save_line_item_forecasts(): void
    {
        [$user, $project, $period, , $item] = $this->seedData();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/data-entry/line-items", [
                'forecasts' => [
                    [
                        'line_item_id' => $item->id,
                        'ctd_qty' => 50,
                        'comments' => 'On track',
                    ],
                ],
            ])
            ->assertRedirect(route('projects.show', $project));

        // ctdAmount = 50 * 250 = 12500, ctcAmount = 50 * 250 = 12500, fcac = 25000
        $this->assertDatabaseHas('line_item_forecasts', [
            'line_item_id' => $item->id,
            'forecast_period_id' => $period->id,
            'ctd_amount' => 12500,
            'ctc_amount' => 12500,
            'fcac_amount' => 25000,
        ]);
    }

    public function test_cannot_save_for_locked_period(): void
    {
        [$user, $project, , , $item] = $this->seedData();

        // The current month period exists but we need a non-editable scenario.
        // Remove the current month period and create only a past one.
        ForecastPeriod::where('project_id', $project->id)->delete();

        ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2023-01-01',
        ]);

        $this->actingAs($user)
            ->post("/projects/{$project->id}/data-entry/line-items", [
                'forecasts' => [
                    [
                        'line_item_id' => $item->id,
                        'ctd_qty' => 50,
                    ],
                ],
            ])
            ->assertNotFound();
    }

    public function test_validation_errors_on_save(): void
    {
        [$user, $project] = $this->seedData();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/data-entry/line-items", [])
            ->assertSessionHasErrors('forecasts');
    }
}
