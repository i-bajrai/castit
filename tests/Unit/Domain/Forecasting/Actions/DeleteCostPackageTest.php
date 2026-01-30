<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\CostPackage;
use App\Models\LineItem;
use App\Models\Project;
use App\Models\User;
use Domain\Forecasting\Actions\DeleteCostPackage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteCostPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_cost_package(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        $controlAccount = ControlAccount::create([
            'project_id' => $project->id,
            'code' => 'CA-001',
            'description' => 'Test Control Account',
            'phase' => 'Phase 1',
            'category' => 'Labor',
            'sort_order' => 1,
        ]);

        $package = CostPackage::create([
            'project_id' => $project->id,
            'control_account_id' => $controlAccount->id,
            'name' => 'To Delete',
            'sort_order' => 1,
        ]);

        (new DeleteCostPackage)->execute($package);

        $this->assertDatabaseMissing('cost_packages', ['id' => $package->id]);
    }

    public function test_cascades_to_line_items(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        $controlAccount = ControlAccount::create([
            'project_id' => $project->id,
            'code' => 'CA-001',
            'description' => 'Test Control Account',
            'phase' => 'Phase 1',
            'category' => 'Labor',
            'sort_order' => 1,
        ]);

        $package = CostPackage::create([
            'project_id' => $project->id,
            'control_account_id' => $controlAccount->id,
            'name' => 'Test',
            'sort_order' => 1,
        ]);

        $item = LineItem::create([
            'cost_package_id' => $package->id,
            'description' => 'Item',
            'original_qty' => 10,
            'original_rate' => 10,
            'original_amount' => 100,
            'sort_order' => 1,
        ]);

        (new DeleteCostPackage)->execute($package);

        $this->assertDatabaseMissing('line_items', ['id' => $item->id]);
    }
}
