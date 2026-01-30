<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ForecastPeriod;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForecastPeriodControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithProject(): array
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        return [$user, $company, $project];
    }

    public function test_owner_can_open_new_period(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/periods", [
                'period_date' => '2024-01-01',
            ])
            ->assertRedirect(route('projects.settings', $project));

        $this->assertDatabaseHas('forecast_periods', [
            'project_id' => $project->id,
            'is_current' => true,
        ]);
    }

    public function test_owner_can_lock_period(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        $period = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        $this->actingAs($user)
            ->patch("/projects/{$project->id}/periods/{$period->id}/lock")
            ->assertRedirect(route('projects.settings', $project));

        $period->refresh();
        $this->assertTrue($period->isLocked());
    }

    public function test_non_owner_cannot_open_period(): void
    {
        [, , $project] = $this->createUserWithProject();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->post("/projects/{$project->id}/periods", [
                'period_date' => '2024-01-01',
            ])
            ->assertForbidden();
    }

    public function test_cannot_open_duplicate_period_date(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        $this->actingAs($user)
            ->post("/projects/{$project->id}/periods", [
                'period_date' => '2024-01-01',
            ])
            ->assertSessionHasErrors('period_date');
    }

    public function test_opening_period_locks_previous(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        $oldPeriod = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        $this->actingAs($user)
            ->post("/projects/{$project->id}/periods", [
                'period_date' => '2024-02-01',
            ])
            ->assertRedirect();

        $oldPeriod->refresh();
        $this->assertFalse($oldPeriod->is_current);
        $this->assertTrue($oldPeriod->isLocked());
    }
}
