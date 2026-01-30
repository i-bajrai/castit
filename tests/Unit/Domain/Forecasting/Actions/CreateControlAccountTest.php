<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Domain\Forecasting\Actions\CreateControlAccount;
use Domain\Forecasting\DataTransferObjects\ControlAccountData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateControlAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_control_account_for_project(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        $data = new ControlAccountData(
            phase: '4 - Construction',
            code: '401AN00',
            description: 'Civil - Anti Graffiti',
            category: '401C - Civil',
            baselineBudget: 339264,
            approvedBudget: 790194,
            sortOrder: 1,
        );

        $action = new CreateControlAccount;
        $account = $action->execute($project, $data);

        $this->assertDatabaseHas('control_accounts', [
            'project_id' => $project->id,
            'code' => '401AN00',
            'description' => 'Civil - Anti Graffiti',
            'baseline_budget' => 339264,
            'approved_budget' => 790194,
        ]);

        $this->assertTrue($account->project->is($project));
    }

    public function test_creates_with_nullable_category(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        $data = new ControlAccountData(
            phase: '4 - Construction',
            code: '401XX00',
            description: 'Uncategorised',
            category: null,
        );

        $action = new CreateControlAccount;
        $account = $action->execute($project, $data);

        $this->assertNull($account->category);
        $this->assertEquals(0, (float) $account->baseline_budget);
    }
}
