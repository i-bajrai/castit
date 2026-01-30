<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\Project;
use App\Models\User;
use Domain\Forecasting\Actions\UpdateControlAccount;
use Domain\Forecasting\DataTransferObjects\ControlAccountData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateControlAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_control_account(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4 - Construction',
            'code' => '401AN00',
            'description' => 'Old Description',
            'category' => '401C - Civil',
            'baseline_budget' => 100000,
            'approved_budget' => 100000,
            'sort_order' => 1,
        ]);

        $data = new ControlAccountData(
            phase: '5 - Commissioning',
            code: '501AN00',
            description: 'New Description',
            category: '501C - Civil',
            baselineBudget: 200000,
            approvedBudget: 250000,
            sortOrder: 2,
        );

        $action = new UpdateControlAccount;
        $updated = $action->execute($account, $data);

        $this->assertEquals('501AN00', $updated->code);
        $this->assertEquals('New Description', $updated->description);
        $this->assertEquals(250000, (float) $updated->approved_budget);
    }
}
