<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\CostPackage;
use App\Models\ForecastPeriod;
use App\Models\LineItem;
use App\Models\LineItemForecast;
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
        $user->update(['company_id' => $company->id, 'company_role' => 'admin']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        $period = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => now()->startOfMonth()->toDateString(),
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
                        'period_qty' => 50,
                        'comments' => 'On track',
                    ],
                ],
            ])
            ->assertRedirect(route('projects.show', $project));

        // period_amount = 50 * 250 = 12500, fcac = 100 * 250 = 25000
        $this->assertDatabaseHas('line_item_forecasts', [
            'line_item_id' => $item->id,
            'forecast_period_id' => $period->id,
            'period_qty' => 50,
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
                        'period_qty' => 50,
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

    private function seedForecast(): array
    {
        [$user, $project, $period, $package, $item] = $this->seedData();

        $forecast = LineItemForecast::create([
            'line_item_id' => $item->id,
            'forecast_period_id' => $period->id,
            'period_qty' => 50, 'period_rate' => 250,
            'fcac_qty' => 100, 'fcac_rate' => 250,
        ]);

        return [$user, $project, $period, $item, $forecast];
    }

    public function test_owner_can_update_ctd_qty(): void
    {
        [$user, $project, , $item, $forecast] = $this->seedForecast();

        $this->actingAs($user)
            ->patchJson("/projects/{$project->id}/forecasts/{$forecast->id}/ctd-qty", [
                'period_qty' => 80,
            ])
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        // period_amount = 80 * 250 = 20000, fcac = 100 * 250 = 25000
        $this->assertDatabaseHas('line_item_forecasts', [
            'id' => $forecast->id,
            'period_qty' => 80,
            'fcac_amount' => 25000,
        ]);
    }

    public function test_cannot_update_ctd_qty_for_locked_period(): void
    {
        [$user, $project, $period, , $forecast] = $this->seedForecast();

        $period->update(['period_date' => '2023-01-01']);

        $this->actingAs($user)
            ->patchJson("/projects/{$project->id}/forecasts/{$forecast->id}/ctd-qty", [
                'period_qty' => 80,
            ])
            ->assertForbidden();
    }

    public function test_period_qty_requires_numeric_value(): void
    {
        [$user, $project, , , $forecast] = $this->seedForecast();

        $this->actingAs($user)
            ->patchJson("/projects/{$project->id}/forecasts/{$forecast->id}/ctd-qty", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('period_qty');
    }

    public function test_owner_can_update_comment(): void
    {
        [$user, $project, , , $forecast] = $this->seedForecast();

        $this->actingAs($user)
            ->patchJson("/projects/{$project->id}/forecasts/{$forecast->id}/comment", [
                'comments' => 'Updated note',
            ])
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('line_item_forecasts', [
            'id' => $forecast->id,
            'comments' => 'Updated note',
        ]);
    }

    public function test_can_clear_comment(): void
    {
        [$user, $project, , , $forecast] = $this->seedForecast();

        $forecast->update(['comments' => 'Old note']);

        $this->actingAs($user)
            ->patchJson("/projects/{$project->id}/forecasts/{$forecast->id}/comment", [
                'comments' => null,
            ])
            ->assertOk();

        $this->assertDatabaseHas('line_item_forecasts', [
            'id' => $forecast->id,
            'comments' => null,
        ]);
    }

    public function test_cannot_update_comment_for_locked_period(): void
    {
        [$user, $project, $period, , $forecast] = $this->seedForecast();

        $period->update(['period_date' => '2023-01-01']);

        $this->actingAs($user)
            ->patchJson("/projects/{$project->id}/forecasts/{$forecast->id}/comment", [
                'comments' => 'Should fail',
            ])
            ->assertForbidden();
    }

    public function test_non_owner_cannot_update_ctd_qty(): void
    {
        [, $project, , , $forecast] = $this->seedForecast();
        $otherUser = User::factory()->create();
        $otherCompany = Company::create(['user_id' => $otherUser->id, 'name' => 'Other Co']);
        $otherUser->update(['company_id' => $otherCompany->id, 'company_role' => 'admin']);

        $this->actingAs($otherUser)
            ->patchJson("/projects/{$project->id}/forecasts/{$forecast->id}/ctd-qty", [
                'period_qty' => 80,
            ])
            ->assertForbidden();
    }

    public function test_non_owner_cannot_update_comment(): void
    {
        [, $project, , , $forecast] = $this->seedForecast();
        $otherUser = User::factory()->create();
        $otherCompany2 = Company::create(['user_id' => $otherUser->id, 'name' => 'Other Co']);
        $otherUser->update(['company_id' => $otherCompany2->id, 'company_role' => 'admin']);

        $this->actingAs($otherUser)
            ->patchJson("/projects/{$project->id}/forecasts/{$forecast->id}/comment", [
                'comments' => 'Should fail',
            ])
            ->assertForbidden();
    }
}
