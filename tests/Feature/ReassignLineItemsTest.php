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

class ReassignLineItemsTest extends TestCase
{
    use RefreshDatabase;

    private function seedData(): array
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create([
            'company_id' => $company->id,
            'name' => 'Test',
            'original_budget' => 100000,
            'start_date' => '2024-01-01',
            'end_date' => '2024-03-01',
        ]);

        $period1 = ForecastPeriod::create(['project_id' => $project->id, 'period_date' => '2024-01-01']);
        $period2 = ForecastPeriod::create(['project_id' => $project->id, 'period_date' => '2024-02-01']);

        // Existing CA + package + item
        $ca = ControlAccount::create([
            'project_id' => $project->id,
            'code' => 'CA-001',
            'description' => 'Main CA',
            'phase' => 'Phase 1',
            'sort_order' => 1,
        ]);

        $package = CostPackage::create([
            'project_id' => $project->id,
            'control_account_id' => $ca->id,
            'name' => 'Foundation',
            'sort_order' => 1,
        ]);

        $existingItem = LineItem::create([
            'cost_package_id' => $package->id,
            'description' => 'Concrete',
            'original_qty' => 100,
            'original_rate' => 250,
            'original_amount' => 25000,
            'sort_order' => 1,
        ]);

        foreach ([$period1, $period2] as $period) {
            LineItemForecast::create([
                'line_item_id' => $existingItem->id,
                'forecast_period_id' => $period->id,
                'ctd_qty' => 10, 'ctd_rate' => 250, 'ctd_amount' => 2500,
                'ctc_qty' => 90, 'ctc_rate' => 250, 'ctc_amount' => 22500,
                'fcac_rate' => 250, 'fcac_amount' => 25000, 'variance' => 0,
            ]);
        }

        // Unassigned CA + package + item
        $unassignedCa = ControlAccount::create([
            'project_id' => $project->id,
            'code' => 'UNASSIGNED',
            'description' => 'Unassigned',
            'phase' => 'Unassigned',
            'sort_order' => 999,
        ]);

        $unassignedPkg = CostPackage::create([
            'project_id' => $project->id,
            'control_account_id' => $unassignedCa->id,
            'name' => 'Unassigned',
            'sort_order' => 999,
        ]);

        $unassignedItem = LineItem::create([
            'cost_package_id' => $unassignedPkg->id,
            'description' => 'Unknown Concrete',
            'original_qty' => 0,
            'original_rate' => 0,
            'original_amount' => 0,
        ]);

        foreach ([$period1, $period2] as $period) {
            LineItemForecast::create([
                'line_item_id' => $unassignedItem->id,
                'forecast_period_id' => $period->id,
                'ctd_qty' => 5, 'ctd_rate' => 0, 'ctd_amount' => 0,
                'ctc_qty' => 0, 'ctc_rate' => 0, 'ctc_amount' => 0,
                'fcac_rate' => 0, 'fcac_amount' => 0, 'variance' => 0,
            ]);
        }

        return [$user, $project, $package, $existingItem, $unassignedItem, $unassignedCa, $unassignedPkg, $period1, $period2];
    }

    public function test_unassigned_page_shows_items(): void
    {
        [$user, $project] = $this->seedData();

        $this->actingAs($user)
            ->get("/projects/{$project->id}/unassigned")
            ->assertOk()
            ->assertSee('Unknown Concrete')
            ->assertSee('unassigned line item');
    }

    public function test_unassigned_page_redirects_when_none(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create([
            'company_id' => $company->id,
            'name' => 'Test',
            'original_budget' => 100000,
        ]);

        $this->actingAs($user)
            ->get("/projects/{$project->id}/unassigned")
            ->assertRedirect(route('projects.settings', $project));
    }

    public function test_move_item_to_package(): void
    {
        [$user, $project, $package, , $unassignedItem, $unassignedCa] = $this->seedData();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/unassigned/reassign", [
                'operations' => [
                    [
                        'line_item_id' => $unassignedItem->id,
                        'action' => 'move',
                        'target_package_id' => $package->id,
                        'merge_into_id' => null,
                    ],
                ],
            ])
            ->assertRedirect(route('projects.settings', $project));

        // Item should now belong to the target package
        $this->assertDatabaseHas('line_items', [
            'id' => $unassignedItem->id,
            'cost_package_id' => $package->id,
        ]);

        // Unassigned CA should be cleaned up
        $this->assertDatabaseMissing('control_accounts', ['id' => $unassignedCa->id]);
    }

    public function test_merge_item_into_existing(): void
    {
        [$user, $project, , $existingItem, $unassignedItem, $unassignedCa, , $period1] = $this->seedData();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/unassigned/reassign", [
                'operations' => [
                    [
                        'line_item_id' => $unassignedItem->id,
                        'action' => 'merge',
                        'target_package_id' => null,
                        'merge_into_id' => $existingItem->id,
                    ],
                ],
            ])
            ->assertRedirect(route('projects.settings', $project));

        // Unassigned item should be deleted
        $this->assertDatabaseMissing('line_items', ['id' => $unassignedItem->id]);

        // Existing item should have merged ctd_qty (10 + 5 = 15)
        $this->assertDatabaseHas('line_item_forecasts', [
            'line_item_id' => $existingItem->id,
            'forecast_period_id' => $period1->id,
            'ctd_qty' => 15,
        ]);

        // Unassigned CA should be cleaned up
        $this->assertDatabaseMissing('control_accounts', ['id' => $unassignedCa->id]);
    }

    public function test_non_owner_cannot_access_unassigned(): void
    {
        [, $project] = $this->seedData();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->get("/projects/{$project->id}/unassigned")
            ->assertForbidden();
    }

    public function test_non_owner_cannot_reassign(): void
    {
        [, $project, $package, , $unassignedItem] = $this->seedData();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->post("/projects/{$project->id}/unassigned/reassign", [
                'operations' => [
                    [
                        'line_item_id' => $unassignedItem->id,
                        'action' => 'move',
                        'target_package_id' => $package->id,
                        'merge_into_id' => null,
                    ],
                ],
            ])
            ->assertForbidden();
    }

    public function test_import_redirects_to_unassigned_when_items_created(): void
    {
        [$user, $project] = $this->seedData();

        $csv = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'import.csv',
            "description,period,ctd_qty\nBrand New Item,2024-01,50\n"
        );

        $this->actingAs($user)
            ->post("/projects/{$project->id}/forecasts/import", ['csv_file' => $csv])
            ->assertRedirect(route('projects.unassigned', $project));
    }

    public function test_clean_import_stays_on_settings(): void
    {
        [$user, $project, , , , , , $period1] = $this->seedData();

        // Import an item that already exists ("Concrete" from seedData)
        $csv = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'import.csv',
            "description,period,ctd_qty\nConcrete,2024-01,80\n"
        );

        $this->actingAs($user)
            ->post("/projects/{$project->id}/forecasts/import", ['csv_file' => $csv])
            ->assertRedirect(route('projects.settings', $project));
    }

    public function test_settings_shows_unassigned_banner(): void
    {
        [$user, $project] = $this->seedData();

        $this->actingAs($user)
            ->get("/projects/{$project->id}/settings")
            ->assertOk()
            ->assertSee('unassigned line item');
    }
}
