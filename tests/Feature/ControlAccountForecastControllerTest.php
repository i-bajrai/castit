<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\ForecastPeriod;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlAccountForecastControllerTest extends TestCase
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

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4',
            'code' => '401AN00',
            'description' => 'Civil',
            'category' => 'Civil',
            'baseline_budget' => 100000,
            'approved_budget' => 100000,
            'sort_order' => 1,
        ]);

        return [$user, $project, $period, $account];
    }

    public function test_owner_can_save_control_account_forecasts(): void
    {
        [$user, $project, $period, $account] = $this->seedData();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/data-entry/control-accounts", [
                'forecasts' => [
                    [
                        'control_account_id' => $account->id,
                        'monthly_cost' => 5000,
                        'cost_to_date' => 50000,
                        'estimate_to_complete' => 45000,
                        'monthly_comments' => 'Progressing well',
                    ],
                ],
            ])
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('control_account_forecasts', [
            'control_account_id' => $account->id,
            'forecast_period_id' => $period->id,
            'cost_to_date' => 50000,
            'estimate_to_complete' => 45000,
            'estimated_final_cost' => 95000,
        ]);
    }

    public function test_cannot_save_for_locked_period(): void
    {
        [$user, $project, , $account] = $this->seedData();

        // Remove the current month period so the controller's firstOrFail will 404
        ForecastPeriod::where('project_id', $project->id)->delete();

        ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2023-01-01',
        ]);

        $this->actingAs($user)
            ->post("/projects/{$project->id}/data-entry/control-accounts", [
                'forecasts' => [
                    [
                        'control_account_id' => $account->id,
                        'monthly_cost' => 5000,
                        'cost_to_date' => 50000,
                        'estimate_to_complete' => 45000,
                    ],
                ],
            ])
            ->assertNotFound();
    }
}
