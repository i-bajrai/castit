<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithProject(): array
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test Project', 'original_budget' => 100000]);

        return [$user, $company, $project];
    }

    public function test_owner_can_view_settings_page(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        $this->actingAs($user)
            ->get("/projects/{$project->id}/settings")
            ->assertOk()
            ->assertSee('Project Settings');
    }

    public function test_non_owner_cannot_view_settings_page(): void
    {
        [, , $project] = $this->createUserWithProject();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->get("/projects/{$project->id}/settings")
            ->assertForbidden();
    }

    public function test_guest_cannot_view_settings_page(): void
    {
        [, , $project] = $this->createUserWithProject();

        $this->get("/projects/{$project->id}/settings")
            ->assertRedirect('/login');
    }

    public function test_settings_page_shows_control_accounts(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4 - Construction',
            'code' => '401AN00',
            'description' => 'Anti Graffiti',
            'category' => '401C - Civil',
            'baseline_budget' => 339264,
            'approved_budget' => 790194,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->get("/projects/{$project->id}/settings")
            ->assertOk()
            ->assertSee('401AN00')
            ->assertSee('Anti Graffiti');
    }
}
