<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\CostPackage;
use App\Models\Project;
use App\Models\User;
use Domain\Forecasting\Actions\CreateLineItem;
use Domain\Forecasting\DataTransferObjects\LineItemData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateLineItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_line_item(): void
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
        $package = CostPackage::create(['project_id' => $project->id, 'control_account_id' => $controlAccount->id, 'name' => 'Foundation', 'sort_order' => 1]);

        $data = new LineItemData(
            description: 'Concrete Pour',
            itemNo: '001',
            unitOfMeasure: 'M3',
            originalQty: 100,
            originalRate: 250,
            originalAmount: 25000,
            sortOrder: 1,
        );

        $item = (new CreateLineItem)->execute($package, $data);

        $this->assertEquals('Concrete Pour', $item->description);
        $this->assertEquals('M3', $item->unit_of_measure);
        $this->assertEquals(25000, (float) $item->original_amount);
    }
}
