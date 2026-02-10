<?php

namespace Tests\Feature;

use App\Enums\CompanyRole;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCompanyControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createSuperAdmin(): User
    {
        return User::factory()->admin()->create();
    }

    private function createRegularUser(): User
    {
        return User::factory()->create();
    }

    // --- INDEX ---

    public function test_super_admin_can_view_companies_page(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->get('/admin/companies')
            ->assertOk();
    }

    public function test_regular_user_cannot_view_companies_page(): void
    {
        $user = $this->createRegularUser();

        $this->actingAs($user)
            ->get('/admin/companies')
            ->assertForbidden();
    }

    public function test_guest_cannot_view_companies_page(): void
    {
        $this->get('/admin/companies')
            ->assertRedirect('/login');
    }

    public function test_companies_page_shows_member_and_project_counts(): void
    {
        $admin = $this->createSuperAdmin();

        $company = Company::create(['user_id' => $admin->id, 'name' => 'Acme Corp']);

        User::factory()->count(3)->create([
            'company_id' => $company->id,
            'company_role' => CompanyRole::Engineer,
        ]);

        Project::create([
            'company_id' => $company->id,
            'name' => 'Test Project',
            'original_budget' => 100000,
        ]);

        $this->actingAs($admin)
            ->get('/admin/companies')
            ->assertOk()
            ->assertSee('Acme Corp');
    }

    // --- FILTERS ---

    public function test_companies_page_can_filter_by_search(): void
    {
        $admin = $this->createSuperAdmin();
        Company::create(['user_id' => $admin->id, 'name' => 'Acme Corp']);
        Company::create(['user_id' => $admin->id, 'name' => 'Beta Inc']);

        $this->actingAs($admin)
            ->get('/admin/companies?search=Acme')
            ->assertOk()
            ->assertSee('Acme Corp')
            ->assertDontSee('Beta Inc');
    }

    public function test_companies_page_empty_search_shows_all(): void
    {
        $admin = $this->createSuperAdmin();
        Company::create(['user_id' => $admin->id, 'name' => 'Acme Corp']);
        Company::create(['user_id' => $admin->id, 'name' => 'Beta Inc']);

        $this->actingAs($admin)
            ->get('/admin/companies?search=')
            ->assertOk()
            ->assertSee('Acme Corp')
            ->assertSee('Beta Inc');
    }

    // --- SHOW ---

    public function test_super_admin_can_view_company_detail_page(): void
    {
        $admin = $this->createSuperAdmin();
        $company = Company::create(['user_id' => $admin->id, 'name' => 'Acme Corp']);

        $member = User::factory()->create([
            'company_id' => $company->id,
            'company_role' => CompanyRole::Engineer,
        ]);

        $project = Project::create([
            'company_id' => $company->id,
            'name' => 'Big Project',
            'original_budget' => 500000,
        ]);

        $this->actingAs($admin)
            ->get("/admin/companies/{$company->id}")
            ->assertOk()
            ->assertSee('Acme Corp')
            ->assertSee($member->name)
            ->assertSee($member->email)
            ->assertSee('Big Project');
    }

    public function test_regular_user_cannot_view_company_detail_page(): void
    {
        $user = $this->createRegularUser();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);

        $this->actingAs($user)
            ->get("/admin/companies/{$company->id}")
            ->assertForbidden();
    }

    public function test_company_detail_shows_empty_state_when_no_members_or_projects(): void
    {
        $admin = $this->createSuperAdmin();
        $company = Company::create(['user_id' => $admin->id, 'name' => 'Empty Co']);

        $this->actingAs($admin)
            ->get("/admin/companies/{$company->id}")
            ->assertOk()
            ->assertSee('Empty Co')
            ->assertSee('No members in this company')
            ->assertSee('No projects in this company');
    }

    public function test_company_detail_shows_member_and_project_counts(): void
    {
        $admin = $this->createSuperAdmin();
        $company = Company::create(['user_id' => $admin->id, 'name' => 'Counted Co']);

        User::factory()->count(2)->create([
            'company_id' => $company->id,
            'company_role' => CompanyRole::Engineer,
        ]);

        Project::create([
            'company_id' => $company->id,
            'name' => 'Project A',
            'original_budget' => 100000,
        ]);

        $this->actingAs($admin)
            ->get("/admin/companies/{$company->id}")
            ->assertOk()
            ->assertSee('Members (2)')
            ->assertSee('Projects (1)');
    }

    // --- STORE ---

    public function test_super_admin_can_create_company(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->post('/admin/companies', [
                'name' => 'New Company',
            ])
            ->assertRedirect(route('admin.companies.index'));

        $this->assertDatabaseHas('companies', [
            'name' => 'New Company',
        ]);
    }

    public function test_store_validates_name_required(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->post('/admin/companies', [])
            ->assertSessionHasErrors(['name']);
    }

    public function test_regular_user_cannot_create_company(): void
    {
        $user = $this->createRegularUser();

        $this->actingAs($user)
            ->post('/admin/companies', ['name' => 'New Company'])
            ->assertForbidden();
    }

    // --- UPDATE ---

    public function test_super_admin_can_update_company(): void
    {
        $admin = $this->createSuperAdmin();
        $company = Company::create(['user_id' => $admin->id, 'name' => 'Old Name']);

        $this->actingAs($admin)
            ->put("/admin/companies/{$company->id}", [
                'name' => 'New Name',
            ])
            ->assertRedirect(route('admin.companies.index'));

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'New Name',
        ]);
    }

    public function test_regular_user_cannot_update_company(): void
    {
        $user = $this->createRegularUser();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);

        $this->actingAs($user)
            ->put("/admin/companies/{$company->id}", ['name' => 'Hacked'])
            ->assertForbidden();
    }

    // --- DESTROY ---

    public function test_super_admin_can_delete_company_without_projects(): void
    {
        $admin = $this->createSuperAdmin();
        $company = Company::create(['user_id' => $admin->id, 'name' => 'Delete Me']);

        $this->actingAs($admin)
            ->delete("/admin/companies/{$company->id}")
            ->assertRedirect(route('admin.companies.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('companies', ['id' => $company->id]);
    }

    public function test_cannot_delete_company_with_projects(): void
    {
        $admin = $this->createSuperAdmin();
        $company = Company::create(['user_id' => $admin->id, 'name' => 'Has Projects']);

        Project::create([
            'company_id' => $company->id,
            'name' => 'Important Project',
            'original_budget' => 100000,
        ]);

        $this->actingAs($admin)
            ->delete("/admin/companies/{$company->id}")
            ->assertRedirect(route('admin.companies.index'))
            ->assertSessionHas('error', 'Cannot delete a company that has projects. Delete the projects first.');

        $this->assertDatabaseHas('companies', ['id' => $company->id]);
    }

    public function test_deleting_company_nullifies_member_associations(): void
    {
        $admin = $this->createSuperAdmin();
        $company = Company::create(['user_id' => $admin->id, 'name' => 'Bye Co']);

        $member = User::factory()->engineer()->create([
            'company_id' => $company->id,
        ]);

        $this->actingAs($admin)
            ->delete("/admin/companies/{$company->id}");

        $member->refresh();
        $this->assertNull($member->company_id);
        $this->assertNull($member->company_role);
    }

    public function test_regular_user_cannot_delete_company(): void
    {
        $user = $this->createRegularUser();
        $company = Company::create(['user_id' => $user->id, 'name' => 'Test Co']);

        $this->actingAs($user)
            ->delete("/admin/companies/{$company->id}")
            ->assertForbidden();
    }
}
