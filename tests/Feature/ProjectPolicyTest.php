<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_project_belonging_to_their_company(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'My Project', 'original_budget' => 100000]);

        $this->actingAs($user);
        $this->assertTrue(Gate::allows('view', $project));
    }

    public function test_user_cannot_view_project_belonging_to_another_users_company(): void
    {
        $owner = User::factory()->create();
        $company = Company::create(['user_id' => $owner->id, 'name' => 'Owner Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Their Project', 'original_budget' => 100000]);

        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);
        $this->assertFalse(Gate::allows('view', $project));
    }

    public function test_user_can_update_project_belonging_to_their_company(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'My Project', 'original_budget' => 100000]);

        $this->actingAs($user);
        $this->assertTrue(Gate::allows('update', $project));
    }

    public function test_user_cannot_update_project_belonging_to_another_users_company(): void
    {
        $owner = User::factory()->create();
        $company = Company::create(['user_id' => $owner->id, 'name' => 'Owner Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Their Project', 'original_budget' => 100000]);

        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);
        $this->assertFalse(Gate::allows('update', $project));
    }

    public function test_user_can_delete_project_belonging_to_their_company(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'My Project', 'original_budget' => 100000]);

        $this->actingAs($user);
        $this->assertTrue(Gate::allows('delete', $project));
    }

    public function test_user_cannot_delete_project_belonging_to_another_users_company(): void
    {
        $owner = User::factory()->create();
        $company = Company::create(['user_id' => $owner->id, 'name' => 'Owner Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Their Project', 'original_budget' => 100000]);

        $otherUser = User::factory()->create();

        $this->actingAs($otherUser);
        $this->assertFalse(Gate::allows('delete', $project));
    }
}
