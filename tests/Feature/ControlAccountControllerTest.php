<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\CostPackage;
use App\Models\LineItem;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ControlAccountControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithProject(): array
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $user->update(['company_id' => $company->id, 'company_role' => 'admin']);
        $project = Project::create(['company_id' => $company->id, 'name' => 'Test', 'original_budget' => 100000]);

        return [$user, $company, $project];
    }

    public function test_owner_can_create_control_account(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/control-accounts", [
                'phase' => '4 - Construction',
                'code' => '401AN00',
                'description' => 'Anti Graffiti',
                'category' => '401C - Civil',
                'baseline_budget' => 339264,
                'approved_budget' => 790194,
                'sort_order' => 1,
            ])
            ->assertRedirect(route('projects.settings', $project));

        $this->assertDatabaseHas('control_accounts', [
            'project_id' => $project->id,
            'code' => '401AN00',
        ]);
    }

    public function test_owner_can_update_control_account(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4',
            'code' => '401AN00',
            'description' => 'Old',
            'category' => 'Civil',
            'baseline_budget' => 100000,
            'approved_budget' => 100000,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->put("/projects/{$project->id}/control-accounts/{$account->id}", [
                'phase' => '5',
                'code' => '501AN00',
                'description' => 'Updated',
                'category' => 'Civil',
                'baseline_budget' => 200000,
                'approved_budget' => 200000,
                'sort_order' => 2,
            ])
            ->assertRedirect(route('projects.settings', $project));

        $this->assertDatabaseHas('control_accounts', [
            'id' => $account->id,
            'code' => '501AN00',
            'description' => 'Updated',
        ]);
    }

    public function test_owner_can_delete_control_account(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4',
            'code' => '401AN00',
            'description' => 'To Delete',
            'category' => 'Civil',
            'baseline_budget' => 100000,
            'approved_budget' => 100000,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->delete("/projects/{$project->id}/control-accounts/{$account->id}")
            ->assertRedirect(route('projects.settings', $project));

        $this->assertDatabaseMissing('control_accounts', ['id' => $account->id]);
    }

    public function test_non_owner_cannot_create_control_account(): void
    {
        [, , $project] = $this->createUserWithProject();
        $otherUser = User::factory()->create();
        $otherCompany = Company::create(['user_id' => $otherUser->id, 'name' => 'Other Co']);
        $otherUser->update(['company_id' => $otherCompany->id, 'company_role' => 'admin']);

        $this->actingAs($otherUser)
            ->post("/projects/{$project->id}/control-accounts", [
                'phase' => '4',
                'code' => '401AN00',
                'description' => 'Test',
                'category' => 'Civil',
                'baseline_budget' => 100000,
                'approved_budget' => 100000,
                'sort_order' => 1,
            ])
            ->assertForbidden();
    }

    public function test_validation_errors_on_create(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        $this->actingAs($user)
            ->post("/projects/{$project->id}/control-accounts", [])
            ->assertSessionHasErrors(['phase', 'code', 'description', 'baseline_budget', 'approved_budget', 'sort_order']);
    }

    public function test_validation_errors_on_update(): void
    {
        [$user, , $project] = $this->createUserWithProject();

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

        $this->actingAs($user)
            ->put("/projects/{$project->id}/control-accounts/{$account->id}", [])
            ->assertSessionHasErrors(['phase', 'code', 'description', 'baseline_budget', 'approved_budget', 'sort_order']);
    }

    public function test_import_line_items_rejected_when_items_exist(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4',
            'code' => '401AN00',
            'description' => 'Anti Graffiti',
            'category' => 'Civil',
            'baseline_budget' => 100000,
            'approved_budget' => 100000,
            'sort_order' => 1,
        ]);

        $package = CostPackage::create([
            'project_id' => $project->id,
            'control_account_id' => $account->id,
            'name' => 'Package A',
            'sort_order' => 0,
        ]);

        LineItem::create([
            'cost_package_id' => $package->id,
            'description' => 'Existing Item',
            'original_qty' => 10,
            'original_rate' => 100,
            'original_amount' => 1000,
            'sort_order' => 0,
        ]);

        $csv = "control_account,cost_package,item_no,description,uom,qty,rate,amount\n";
        $csv .= "401AN00,Package B,001,New Item,EA,5,200,1000\n";
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $this->actingAs($user)
            ->post("/projects/{$project->id}/control-accounts/{$account->id}/line-items/import", [
                'csv_file' => $file,
            ])
            ->assertRedirect(route('projects.control-accounts.line-items', [$project, $account]))
            ->assertSessionHas('error', 'Cannot import CSV when line items already exist. Delete existing items first.');

        // Ensure no new items were created
        $this->assertDatabaseMissing('line_items', ['description' => 'New Item']);
    }

    public function test_import_line_items_succeeds_when_no_items_exist(): void
    {
        [$user, , $project] = $this->createUserWithProject();

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4',
            'code' => '401AN00',
            'description' => 'Anti Graffiti',
            'category' => 'Civil',
            'baseline_budget' => 100000,
            'approved_budget' => 100000,
            'sort_order' => 1,
        ]);

        $csv = "control_account,cost_package,item_no,description,uom,qty,rate,amount\n";
        $csv .= "401AN00,Package A,001,Concrete Pour,M3,100,250,25000\n";
        $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

        $this->actingAs($user)
            ->post("/projects/{$project->id}/control-accounts/{$account->id}/line-items/import", [
                'csv_file' => $file,
            ])
            ->assertRedirect(route('projects.control-accounts.line-items', [$project, $account]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('line_items', ['description' => 'Concrete Pour']);
    }
}
