<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\ForecastPeriod;
use App\Models\Project;
use App\Models\User;
use Domain\Forecasting\Actions\CreateBudgetAdjustment;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateBudgetAdjustmentTest extends TestCase
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

    public function test_creates_adjustment_and_updates_approved_budget(): void
    {
        [$user, , $period, $account] = $this->seedData();

        $action = new CreateBudgetAdjustment;
        $adjustment = $action->execute($account, $period, $user, 50000, 'Scope change approved');

        $account->refresh();

        $this->assertEquals(550000, (float) $account->approved_budget);
        $this->assertEquals(500000, (float) $adjustment->previous_approved_budget);
        $this->assertEquals(550000, (float) $adjustment->new_approved_budget);
        $this->assertEquals(50000, (float) $adjustment->amount);
        $this->assertEquals('Scope change approved', $adjustment->reason);
        $this->assertEquals($user->id, $adjustment->user_id);
    }

    public function test_handles_negative_adjustment(): void
    {
        [$user, , $period, $account] = $this->seedData();

        $action = new CreateBudgetAdjustment;
        $adjustment = $action->execute($account, $period, $user, -100000, 'Budget reduction');

        $account->refresh();

        $this->assertEquals(400000, (float) $account->approved_budget);
        $this->assertEquals(-100000, (float) $adjustment->amount);
    }

    public function test_throws_when_period_not_locked(): void
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
            'description' => 'Test',
            'category' => 'Civil',
            'baseline_budget' => 100000,
            'approved_budget' => 500000,
            'sort_order' => 1,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Adjustments can only be made to locked periods.');

        $action = new CreateBudgetAdjustment;
        $action->execute($account, $period, $user, 50000, 'Should fail');
    }

    public function test_throws_when_mismatched_project(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project1 = Project::create(['company_id' => $company->id, 'name' => 'P1', 'original_budget' => 100000]);
        $project2 = Project::create(['company_id' => $company->id, 'name' => 'P2', 'original_budget' => 100000]);

        $period = ForecastPeriod::create([
            'project_id' => $project1->id,
            'period_date' => '2024-01-01',
            'is_current' => false,
            'locked_at' => now(),
        ]);

        $account = ControlAccount::create([
            'project_id' => $project2->id,
            'phase' => '4',
            'code' => '401AN00',
            'description' => 'Test',
            'category' => 'Civil',
            'baseline_budget' => 100000,
            'approved_budget' => 500000,
            'sort_order' => 1,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Control account and period must belong to the same project.');

        $action = new CreateBudgetAdjustment;
        $action->execute($account, $period, $user, 50000, 'Should fail');
    }
}
