<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\ForecastPeriod;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetAdjustmentControllerTest extends TestCase
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
            'period_date' => '2024-01-01',
            'is_current' => false,
            'locked_at' => now(),
        ]);

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4',
            'code' => '401AN00',
            'description' => 'Test',
            'category' => 'Civil',
            'baseline_budget' => 100000,
            'approved_budget' => 500000,
            'sort_order' => 1,
        ]);

        return [$user, $project, $period, $account];
    }

    public function test_owner_can_create_budget_adjustment(): void
    {
        [$user, $project, $period, $account] = $this->seedData();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/budget-adjustments", [
                'control_account_id' => $account->id,
                'forecast_period_id' => $period->id,
                'amount' => 50000,
                'reason' => 'Scope change approved by client',
            ])
            ->assertRedirect(route('projects.settings', $project));

        $this->assertDatabaseHas('budget_adjustments', [
            'control_account_id' => $account->id,
            'amount' => 50000,
            'reason' => 'Scope change approved by client',
        ]);

        $account->refresh();
        $this->assertEquals(550000, (float) $account->approved_budget);
    }

    public function test_non_owner_cannot_create_adjustment(): void
    {
        [, $project, $period, $account] = $this->seedData();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->post("/projects/{$project->id}/budget-adjustments", [
                'control_account_id' => $account->id,
                'forecast_period_id' => $period->id,
                'amount' => 50000,
                'reason' => 'Should fail',
            ])
            ->assertForbidden();
    }

    public function test_validation_requires_reason(): void
    {
        [$user, $project, $period, $account] = $this->seedData();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/budget-adjustments", [
                'control_account_id' => $account->id,
                'forecast_period_id' => $period->id,
                'amount' => 50000,
            ])
            ->assertSessionHasErrors('reason');
    }

    public function test_validation_rejects_zero_amount(): void
    {
        [$user, $project, $period, $account] = $this->seedData();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/budget-adjustments", [
                'control_account_id' => $account->id,
                'forecast_period_id' => $period->id,
                'amount' => 0,
                'reason' => 'Zero amount',
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_cannot_adjust_unlocked_period(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $user->update(['company_id' => $company->id, 'company_role' => 'admin']);
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
            'description' => 'Test',
            'category' => 'Civil',
            'baseline_budget' => 100000,
            'approved_budget' => 500000,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->post("/projects/{$project->id}/budget-adjustments", [
                'control_account_id' => $account->id,
                'forecast_period_id' => $period->id,
                'amount' => 50000,
                'reason' => 'Should fail',
            ])
            ->assertStatus(500); // DomainException from action
    }
}
