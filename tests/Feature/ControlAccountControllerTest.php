<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlAccountControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithProject(): array
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        return [$user, $company, $project];
    }

    public function test_owner_can_create_control_account(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/control-accounts", [
                'phase' => '4 - Construction',
                'code' => '401AN00',
                'description' => 'Anti Graffiti',
                'category' => '401C - Civil',
                'baseline_budget' => 339264,
                'approved_budget' => 790194,
                'sort_order' => 1,
            ])
            ->assertRedirect(route('projects.settings', $project));

        $this->assertDatabaseHas('control_accounts', [
            'project_id' => $project->id,
            'code' => '401AN00',
        ]);
    }

    public function test_owner_can_update_control_account(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4',
            'code' => '401AN00',
            'description' => 'Old',
            'category' => 'Civil',
            'baseline_budget' => 100000,
            'approved_budget' => 100000,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->put("/projects/{$project->id}/control-accounts/{$account->id}", [
                'phase' => '5',
                'code' => '501AN00',
                'description' => 'Updated',
                'category' => 'Civil',
                'baseline_budget' => 200000,
                'approved_budget' => 200000,
                'sort_order' => 2,
            ])
            ->assertRedirect(route('projects.settings', $project));

        $this->assertDatabaseHas('control_accounts', [
            'id' => $account->id,
            'code' => '501AN00',
            'description' => 'Updated',
        ]);
    }

    public function test_owner_can_delete_control_account(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4',
            'code' => '401AN00',
            'description' => 'To Delete',
            'category' => 'Civil',
            'baseline_budget' => 100000,
            'approved_budget' => 100000,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->delete("/projects/{$project->id}/control-accounts/{$account->id}")
            ->assertRedirect(route('projects.settings', $project));

        $this->assertDatabaseMissing('control_accounts', ['id' => $account->id]);
    }

    public function test_non_owner_cannot_create_control_account(): void
    {
        [, , $project] = $this->createUserWithProject();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->post("/projects/{$project->id}/control-accounts", [
                'phase' => '4',
                'code' => '401AN00',
                'description' => 'Test',
                'category' => 'Civil',
                'baseline_budget' => 100000,
                'approved_budget' => 100000,
                'sort_order' => 1,
            ])
            ->assertForbidden();
    }

    public function test_validation_errors_on_create(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/control-accounts", [])
            ->assertSessionHasErrors(['phase', 'code', 'description', 'baseline_budget', 'approved_budget', 'sort_order']);
    }

    public function test_validation_errors_on_update(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4',
            'code' => '401AN00',
            'description' => 'Test',
            'category' => 'Civil',
            'baseline_budget' => 100000,
            'approved_budget' => 100000,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->put("/projects/{$project->id}/control-accounts/{$account->id}", [])
            ->assertSessionHasErrors(['phase', 'code', 'description', 'baseline_budget', 'approved_budget', 'sort_order']);
    }
}
