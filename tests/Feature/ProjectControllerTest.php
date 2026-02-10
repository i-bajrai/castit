<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\CostPackage;
use App\Models\ForecastPeriod;
use App\Models\LineItem;
use App\Models\LineItemForecast;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithProject(): array
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $user->update(['company_id' => $company->id, 'company_role' => 'admin']);
        $project = Project::create([
            'company_id' => $company->id,
            'name' => 'Test Project',
            'original_budget' => 100000,
        ]);

        return [$user, $company, $project];
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        [$user] = $this->createUserWithProject();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Test Project');
    }

    public function test_dashboard_only_shows_users_projects(): void
    {
        [$user] = $this->createUserWithProject();

        $otherUser = User::factory()->create();
        $otherCompany = Company::create(['user_id' => $otherUser->id, 'name' => 'Other Co']);
        Project::create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Project',
            'original_budget' => 50000,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Test Project')
            ->assertDontSee('Other Project');
    }

    public function test_user_can_view_own_project(): void
    {
        [$user, $company, $project] = $this->createUserWithProject();

        ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        $this->actingAs($user)
            ->get("/projects/{$project->id}")
            ->assertOk()
            ->assertSee('Test Project');
    }

    public function test_user_cannot_view_other_users_project(): void
    {
        [, , $project] = $this->createUserWithProject();

        $otherUser = User::factory()->create();
        $otherCompany = Company::create(['user_id' => $otherUser->id, 'name' => 'Other Co']);
        $otherUser->update(['company_id' => $otherCompany->id, 'company_role' => 'admin']);

        $this->actingAs($otherUser)
            ->get("/projects/{$project->id}")
            ->assertForbidden();
    }

    public function test_guest_cannot_view_project(): void
    {
        [, , $project] = $this->createUserWithProject();

        $this->get("/projects/{$project->id}")
            ->assertRedirect('/login');
    }

    public function test_project_show_displays_cost_packages(): void
    {
        [$user, $company, $project] = $this->createUserWithProject();

        $period = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => 'Construction',
            'code' => '001',
            'description' => 'Test Account',
            'category' => 'Civil',
            'baseline_budget' => 50000,
            'approved_budget' => 50000,
            'sort_order' => 1,
        ]);

        $package = CostPackage::create([
            'project_id' => $project->id,
            'control_account_id' => $account->id,
            'item_no' => '001',
            'name' => 'Foundation Works',
            'sort_order' => 1,
        ]);

        $item = LineItem::create([
            'cost_package_id' => $package->id,
            'item_no' => '001',
            'description' => 'Concrete Pour',
            'unit_of_measure' => 'M3',
            'original_qty' => 100,
            'original_rate' => 250.00,
            'original_amount' => 25000.00,
            'sort_order' => 1,
        ]);

        LineItemForecast::create([
            'line_item_id' => $item->id,
            'forecast_period_id' => $period->id,
            'previous_amount' => 25000,
            'ctd_amount' => 15000,
            'ctc_amount' => 10000,
            'fcac_amount' => 25000,
            'variance' => 0,
        ]);

        $this->actingAs($user)
            ->get("/projects/{$project->id}")
            ->assertOk()
            ->assertSee('Foundation Works')
            ->assertSee('Concrete Pour');
    }

    public function test_user_can_view_executive_summary(): void
    {
        [$user, $company, $project] = $this->createUserWithProject();

        $period = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4 - Construction',
            'code' => '401AN00',
            'description' => 'Civil - Anti Graffiti',
            'category' => '401C - Civil',
            'baseline_budget' => 339264,
            'approved_budget' => 790194,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->get("/projects/{$project->id}/executive-summary")
            ->assertOk()
            ->assertSee('Executive Summary')
            ->assertSee('401AN00')
            ->assertSee('Civil - Anti Graffiti');
    }

    public function test_user_cannot_view_other_users_executive_summary(): void
    {
        [, , $project] = $this->createUserWithProject();

        $otherUser = User::factory()->create();
        $otherCompany = Company::create(['user_id' => $otherUser->id, 'name' => 'Other Co']);
        $otherUser->update(['company_id' => $otherCompany->id, 'company_role' => 'admin']);

        $this->actingAs($otherUser)
            ->get("/projects/{$project->id}/executive-summary")
            ->assertForbidden();
    }

    public function test_dashboard_shows_empty_state_when_no_projects(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Empty Co']);
        $user->update(['company_id' => $company->id, 'company_role' => 'admin']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('No projects yet');
    }

    public function test_user_sees_projects_from_their_company(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Company A']);
        $user->update(['company_id' => $company->id, 'company_role' => 'admin']);

        Project::create(['company_id' => $company->id, 'name' => 'Project Alpha', 'original_budget' => 100000]);
        Project::create(['company_id' => $company->id, 'name' => 'Project Beta', 'original_budget' => 200000]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Project Alpha')
            ->assertSee('Project Beta');
    }

    public function test_owner_can_create_project(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $user->update(['company_id' => $company->id, 'company_role' => 'admin']);

        $this->actingAs($user)
            ->post('/projects', [
                'company_id' => $company->id,
                'name' => 'New Highway Project',
                'project_number' => 'HWY-001',
                'description' => 'A new highway construction project',
                'original_budget' => 500000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'company_id' => $company->id,
            'name' => 'New Highway Project',
            'project_number' => 'HWY-001',
        ]);
    }

    public function test_non_owner_cannot_create_project_for_other_company(): void
    {
        [$owner, $company] = $this->createUserWithProject();
        $otherUser = User::factory()->create();
        $otherCompany = Company::create(['user_id' => $otherUser->id, 'name' => 'Other Co']);
        $otherUser->update(['company_id' => $otherCompany->id, 'company_role' => 'admin']);

        $this->actingAs($otherUser)
            ->post('/projects', [
                'company_id' => $company->id,
                'name' => 'Unauthorized Project',
                'original_budget' => 100000,
            ])
            ->assertForbidden();
    }

    public function test_create_project_requires_name(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $user->update(['company_id' => $company->id, 'company_role' => 'admin']);

        $this->actingAs($user)
            ->post('/projects', [
                'company_id' => $company->id,
                'original_budget' => 100000,
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_create_project_requires_original_budget(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $user->update(['company_id' => $company->id, 'company_role' => 'admin']);

        $this->actingAs($user)
            ->post('/projects', [
                'company_id' => $company->id,
                'name' => 'Test Project',
            ])
            ->assertSessionHasErrors('original_budget');
    }

    public function test_create_project_requires_valid_company(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $user->update(['company_id' => $company->id, 'company_role' => 'admin']);

        $this->actingAs($user)
            ->post('/projects', [
                'company_id' => 9999,
                'name' => 'Test Project',
                'original_budget' => 100000,
            ])
            ->assertSessionHasErrors('company_id');
    }

    public function test_guest_cannot_create_project(): void
    {
        $this->post('/projects', [
            'company_id' => 1,
            'name' => 'Test',
            'original_budget' => 100000,
        ])->assertRedirect('/login');
    }

    public function test_user_can_view_cost_analysis_report(): void
    {
        [$user, $company, $project] = $this->createUserWithProject();

        $period = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4 - Construction',
            'code' => '401CB00',
            'description' => 'Civil - Concrete Barriers',
            'category' => '401S - Structure',
            'baseline_budget' => 962977,
            'approved_budget' => 1098258,
            'sort_order' => 1,
        ]);

        $this->actingAs($user)
            ->get("/projects/{$project->id}/cost-analysis")
            ->assertOk()
            ->assertSee('Detailed Value and Cost Analysis')
            ->assertSee('401CB00')
            ->assertSee('Civil - Concrete Barriers')
            ->assertSee('401S - Structure');
    }

    public function test_cost_analysis_shows_forecast_data(): void
    {
        [$user, $company, $project] = $this->createUserWithProject();

        $previousPeriod = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2023-12-01',
            'is_current' => false,
        ]);

        $currentPeriod = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        $account = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4 - Construction',
            'code' => '401CB00',
            'description' => 'Concrete Barriers',
            'category' => '401S - Structure',
            'baseline_budget' => 100000,
            'approved_budget' => 120000,
            'sort_order' => 1,
        ]);

        $package = CostPackage::create([
            'project_id' => $project->id,
            'control_account_id' => $account->id,
            'name' => 'Barriers',
            'sort_order' => 1,
        ]);

        $item = LineItem::create([
            'cost_package_id' => $package->id,
            'description' => 'TL5 Barrier',
            'unit_of_measure' => 'LM',
            'original_qty' => 100,
            'original_rate' => 1000,
            'original_amount' => 100000,
            'sort_order' => 1,
        ]);

        LineItemForecast::create([
            'line_item_id' => $item->id,
            'forecast_period_id' => $previousPeriod->id,
            'ctd_amount' => 30000,
            'ctc_amount' => 70000,
            'fcac_amount' => 100000,
        ]);

        LineItemForecast::create([
            'line_item_id' => $item->id,
            'forecast_period_id' => $currentPeriod->id,
            'ctd_amount' => 50000,
            'ctc_amount' => 60000,
            'fcac_amount' => 110000,
        ]);

        $response = $this->actingAs($user)
            ->get("/projects/{$project->id}/cost-analysis?period={$currentPeriod->id}");

        $response->assertOk();

        // Verify the report contains expected data
        $response->assertSee('401CB00');
        $response->assertSee('Concrete Barriers');
    }

    public function test_cost_analysis_with_period_selector(): void
    {
        [$user, $company, $project] = $this->createUserWithProject();

        $period1 = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => false,
        ]);

        $period2 = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-02-01',
            'is_current' => true,
        ]);

        $this->actingAs($user)
            ->get("/projects/{$project->id}/cost-analysis?period={$period1->id}")
            ->assertOk()
            ->assertSee('January 2024');
    }

    public function test_user_cannot_view_other_users_cost_analysis(): void
    {
        [, , $project] = $this->createUserWithProject();

        $otherUser = User::factory()->create();
        $otherCompany = Company::create(['user_id' => $otherUser->id, 'name' => 'Other Co']);
        $otherUser->update(['company_id' => $otherCompany->id, 'company_role' => 'admin']);

        $this->actingAs($otherUser)
            ->get("/projects/{$project->id}/cost-analysis")
            ->assertForbidden();
    }

    public function test_guest_cannot_view_cost_analysis(): void
    {
        [, , $project] = $this->createUserWithProject();

        $this->get("/projects/{$project->id}/cost-analysis")
            ->assertRedirect('/login');
    }

    public function test_reports_page_has_cost_analysis_link(): void
    {
        [$user, $company, $project] = $this->createUserWithProject();

        $this->actingAs($user)
            ->get("/projects/{$project->id}/reports")
            ->assertOk()
            ->assertSee('Detailed Value and Cost Analysis');
    }

    public function test_dashboard_shows_new_project_button(): void
    {
        $user = User::factory()->create();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);
        $user->update(['company_id' => $company->id, 'company_role' => 'admin']);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('New Project');
    }
}
