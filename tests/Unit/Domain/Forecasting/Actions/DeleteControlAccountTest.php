<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\Project;
use App\Models\User;
use Domain\Forecasting\Actions\DeleteControlAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteControlAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_control_account(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

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

        $action = new DeleteControlAccount;
        $action->execute($account);

        $this->assertDatabaseMissing('control_accounts', ['id' => $account->id]);
    }
}
