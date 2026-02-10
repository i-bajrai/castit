<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\CostPackage;
use App\Models\LineItem;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LineItemControllerTest extends TestCase
{
    use RefreshDatabase;

    private function seedData(): array
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $user->update(['company_id' => $company->id, 'company_role' => 'admin']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);
        $controlAccount = ControlAccount::create([
            'project_id' => $project->id,
            'code' => 'CA-001',
            'description' => 'Test Control Account',
            'phase' => 'Phase 1',
            'category' => 'Labor',
            'sort_order' => 1,
        ]);
        $package = CostPackage::create(['project_id' => $project->id, 'control_account_id' => $controlAccount->id, 'name' => 'Foundation', 'sort_order' => 1]);

        return [$user, $project, $package];
    }

    public function test_owner_can_create_line_item(): void
    {
        [$user, $project, $package] = $this->seedData();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/cost-packages/{$package->id}/line-items", [
                'item_no' => '001',
                'description' => 'Concrete Pour',
                'unit_of_measure' => 'M3',
                'original_qty' => 100,
                'original_rate' => 250,
                'original_amount' => 25000,
                'sort_order' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('line_items', [
            'cost_package_id' => $package->id,
            'description' => 'Concrete Pour',
        ]);
    }

    public function test_owner_can_update_line_item(): void
    {
        [$user, $project, $package] = $this->seedData();

        $item = LineItem::create([
            'cost_package_id' => $package->id,
            'description' => 'Old',
            'original_qty' => 10,
            'original_rate' => 10,
            'original_amount' => 100,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->put("/projects/{$project->id}/cost-packages/{$package->id}/line-items/{$item->id}", [
                'description' => 'Updated',
                'original_qty' => 20,
                'original_rate' => 20,
                'original_amount' => 400,
                'sort_order' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('line_items', [
            'id' => $item->id,
            'description' => 'Updated',
        ]);
    }

    public function test_owner_can_delete_line_item(): void
    {
        [$user, $project, $package] = $this->seedData();

        $item = LineItem::create([
            'cost_package_id' => $package->id,
            'description' => 'To Delete',
            'original_qty' => 10,
            'original_rate' => 10,
            'original_amount' => 100,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->delete("/projects/{$project->id}/cost-packages/{$package->id}/line-items/{$item->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('line_items', ['id' => $item->id]);
    }

    public function test_non_owner_cannot_create_line_item(): void
    {
        [, $project, $package] = $this->seedData();
        $otherUser = User::factory()->create();
        $otherCompany = Company::create(['user_id' => $otherUser->id, 'name' => 'Other Co']);
        $otherUser->update(['company_id' => $otherCompany->id, 'company_role' => 'admin']);

        $this->actingAs($otherUser)
            ->post("/projects/{$project->id}/cost-packages/{$package->id}/line-items", [
                'description' => 'Test',
                'original_qty' => 10,
                'original_rate' => 10,
                'original_amount' => 100,
                'sort_order' => 1,
            ])
            ->assertForbidden();
    }

    public function test_validation_errors_on_create(): void
    {
        [$user, $project, $package] = $this->seedData();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/cost-packages/{$package->id}/line-items", [])
            ->assertSessionHasErrors(['description', 'original_qty', 'original_rate', 'original_amount', 'sort_order']);
    }
}
