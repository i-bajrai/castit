<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\CostPackage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostPackageControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithProject(): array
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);
        $controlAccount = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => 'Construction',
            'code' => '001',
            'description' => 'Test Account',
            'category' => 'Civil',
            'baseline_budget' => 50000,
            'approved_budget' => 50000,
            'sort_order' => 1,
        ]);

        return [$user, $company, $project, $controlAccount];
    }

    public function test_owner_can_create_cost_package(): void
    {
        [$user, , $project, $controlAccount] = $this->createUserWithProject();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/cost-packages", [
                'control_account_id' => $controlAccount->id,
                'item_no' => '001',
                'name' => 'Foundation Works',
                'sort_order' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cost_packages', [
            'project_id' => $project->id,
            'control_account_id' => $controlAccount->id,
            'name' => 'Foundation Works',
            'item_no' => '001',
        ]);
    }

    public function test_owner_can_update_cost_package(): void
    {
        [$user, , $project, $controlAccount] = $this->createUserWithProject();

        $package = CostPackage::create([
            'project_id' => $project->id,
            'control_account_id' => $controlAccount->id,
            'item_no' => '001',
            'name' => 'Old Name',
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->put("/projects/{$project->id}/cost-packages/{$package->id}", [
                'item_no' => '002',
                'name' => 'Updated Name',
                'sort_order' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cost_packages', [
            'id' => $package->id,
            'name' => 'Updated Name',
            'item_no' => '002',
        ]);
    }

    public function test_owner_can_delete_cost_package(): void
    {
        [$user, , $project, $controlAccount] = $this->createUserWithProject();

        $package = CostPackage::create([
            'project_id' => $project->id,
            'control_account_id' => $controlAccount->id,
            'name' => 'To Delete',
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->delete("/projects/{$project->id}/cost-packages/{$package->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('cost_packages', ['id' => $package->id]);
    }

    public function test_non_owner_cannot_create_cost_package(): void
    {
        [, , $project, $controlAccount] = $this->createUserWithProject();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->post("/projects/{$project->id}/cost-packages", [
                'control_account_id' => $controlAccount->id,
                'name' => 'Test',
                'sort_order' => 1,
            ])
            ->assertForbidden();
    }

    public function test_validation_errors_on_create(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/cost-packages", [])
            ->assertSessionHasErrors(['name', 'sort_order', 'control_account_id']);
    }

    public function test_show_page_shows_cost_packages(): void
    {
        [$user, , $project, $controlAccount] = $this->createUserWithProject();

        CostPackage::create([
            'project_id' => $project->id,
            'control_account_id' => $controlAccount->id,
            'item_no' => '001',
            'name' => 'Foundation Works',
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->get("/projects/{$project->id}")
            ->assertOk()
            ->assertSee('Foundation Works');
    }
}
