<?php

namespace Tests\Unit\Domain\Forecasting\Actions;

use App\Models\Company;
use App\Models\CostPackage;
use App\Models\LineItem;
use App\Models\Project;
use App\Models\User;
use Domain\Forecasting\Actions\UpdateLineItem;
use Domain\Forecasting\DataTransferObjects\LineItemData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateLineItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_line_item(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);
        $package = CostPackage::create(['project_id' => $project->id, 'name' => 'Foundation', 'sort_order' => 1]);

        $item = LineItem::create([
            'cost_package_id' => $package->id,
            'description' => 'Old',
            'original_qty' => 10,
            'original_rate' => 10,
            'original_amount' => 100,
            'sort_order' => 1,
        ]);

        $data = new LineItemData(
            description: 'Updated',
            itemNo: '002',
            unitOfMeasure: 'KG',
            originalQty: 20,
            originalRate: 20,
            originalAmount: 400,
            sortOrder: 2,
        );

        (new UpdateLineItem)->execute($item, $data);

        $item->refresh();
        $this->assertEquals('Updated', $item->description);
        $this->assertEquals('KG', $item->unit_of_measure);
    }
}
