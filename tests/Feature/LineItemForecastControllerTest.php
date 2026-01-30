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
            'period_date' => '2024-01-01',
            'is_current' => true,
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

    public function test_owner_can_view_data_entry_page(): void
    {
        [$user, $project] = $this->seedData();

        $this->actingAs($user)
            ->get("/projects/{$project->id}/data-entry")
            ->assertOk()
            ->assertSee('Data Entry')
            ->assertSee('Concrete');
    }

    public function test_redirects_when_no_current_period(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        $this->actingAs($user)
            ->get("/projects/{$project->id}/data-entry")
            ->assertRedirect(route('projects.settings', $project));
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
                        'ctd_rate' => 260,
                        'ctd_amount' => 13000,
                        'ctc_qty' => 50,
                        'ctc_rate' => 240,
                        'ctc_amount' => 12000,
                        'comments' => 'On track',
                    ],
                ],
            ])
            ->assertRedirect(route('projects.data-entry.line-items', $project));

        $this->assertDatabaseHas('line_item_forecasts', [
            'line_item_id' => $item->id,
            'forecast_period_id' => $period->id,
            'ctd_amount' => 13000,
            'ctc_amount' => 12000,
            'fcac_amount' => 25000,
        ]);
    }

    public function test_cannot_save_for_locked_period(): void
    {
        [$user, $project, $period, , $item] = $this->seedData();

        $period->update(['locked_at' => now(), 'is_current' => false]);

        // Create a new current period that is also locked to test the abort
        $lockedPeriod = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-02-01',
            'is_current' => true,
            'locked_at' => now(),
        ]);

        $this->actingAs($user)
            ->post("/projects/{$project->id}/data-entry/line-items", [
                'forecasts' => [
                    [
                        'line_item_id' => $item->id,
                        'ctd_qty' => 50,
                        'ctd_rate' => 260,
                        'ctd_amount' => 13000,
                        'ctc_qty' => 50,
                        'ctc_rate' => 240,
                        'ctc_amount' => 12000,
                    ],
                ],
            ])
            ->assertForbidden();
    }

    public function test_non_owner_cannot_access_data_entry(): void
    {
        [, $project] = $this->seedData();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->get("/projects/{$project->id}/data-entry")
            ->assertForbidden();
    }

    public function test_validation_errors_on_save(): void
    {
        [$user, $project] = $this->seedData();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/data-entry/line-items", [])
            ->assertSessionHasErrors('forecasts');
    }
}
