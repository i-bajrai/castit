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
            'period_date' => '2024-01-01',
            'is_current' => true,
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

    public function test_owner_can_view_ca_data_entry_page(): void
    {
        [$user, $project, , $account] = $this->seedData();

        $this->actingAs($user)
            ->get("/projects/{$project->id}/data-entry/control-accounts")
            ->assertOk()
            ->assertSee('Control Accounts')
            ->assertSee('401AN00');
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
            ->assertRedirect(route('projects.data-entry.control-accounts', $project));

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
        [$user, $project, $period, $account] = $this->seedData();

        $period->update(['locked_at' => now(), 'is_current' => false]);

        ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-02-01',
            'is_current' => true,
            'locked_at' => now(),
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
            ->assertForbidden();
    }

    public function test_non_owner_cannot_access(): void
    {
        [, $project] = $this->seedData();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->get("/projects/{$project->id}/data-entry/control-accounts")
            ->assertForbidden();
    }
}
