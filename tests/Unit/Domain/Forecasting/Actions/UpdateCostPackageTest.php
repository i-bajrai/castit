<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\CostPackage;
use App\Models\Project;
use App\Models\User;
use Domain\Forecasting\Actions\UpdateCostPackage;
use Domain\Forecasting\DataTransferObjects\CostPackageData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateCostPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_cost_package(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        $package = CostPackage::create([
            'project_id' => $project->id,
            'name' => 'Old',
            'sort_order' => 1,
        ]);

        $data = new CostPackageData(name: 'Updated', itemNo: '002', sortOrder: 2);
        (new UpdateCostPackage)->execute($package, $data);

        $package->refresh();
        $this->assertEquals('Updated', $package->name);
        $this->assertEquals('002', $package->item_no);
        $this->assertEquals(2, $package->sort_order);
    }
}
