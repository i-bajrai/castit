<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\Project;
use App\Models\User;
use Domain\Forecasting\Actions\CreateCostPackage;
use Domain\Forecasting\DataTransferObjects\CostPackageData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCostPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_cost_package(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);
        $controlAccount = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => 'Construction',
            'code' => '001',
            'description' => 'Test Account',
            'category' => 'Civil',
            'baseline_budget' => 50000,
            'approved_budget' => 50000,
            'sort_order' => 1,
        ]);

        $data = new CostPackageData(name: 'Foundation Works', itemNo: '001', sortOrder: 1);
        $package = (new CreateCostPackage)->execute($controlAccount, $data);

        $this->assertEquals('Foundation Works', $package->name);
        $this->assertEquals('001', $package->item_no);
        $this->assertEquals(1, $package->sort_order);
        $this->assertEquals($project->id, $package->project_id);
        $this->assertEquals($controlAccount->id, $package->control_account_id);
    }
}
