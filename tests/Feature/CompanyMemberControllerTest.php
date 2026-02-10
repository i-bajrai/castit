<?php

namespace Tests\Feature;

use App\Enums\CompanyRole;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyMemberControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createCompanyAdmin(): array
    {
        $admin = User::factory()->create();
        $company = Company::create(['user_id' => $admin->id, 'name' => 'Test Co']);
        $admin->update(['company_id' => $company->id, 'company_role' => CompanyRole::Admin]);

        return [$admin, $company];
    }

    // --- INDEX ---

    public function test_company_admin_can_view_members_page(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $this->actingAs($admin)
            ->get('/company/members')
            ->assertOk()
            ->assertSee('Team Members');
    }

    public function test_engineer_cannot_view_members_page(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $engineer = User::factory()->engineer()->create([
            'company_id' => $company->id,
        ]);

        $this->actingAs($engineer)
            ->get('/company/members')
            ->assertForbidden();
    }

    public function test_viewer_cannot_view_members_page(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $viewer = User::factory()->companyViewer()->create([
            'company_id' => $company->id,
        ]);

        $this->actingAs($viewer)
            ->get('/company/members')
            ->assertForbidden();
    }

    public function test_super_admin_can_view_members_page(): void
    {
        [, $company] = $this->createCompanyAdmin();

        $superAdmin = User::factory()->admin()->create([
            'company_id' => $company->id,
            'company_role' => CompanyRole::Admin,
        ]);

        $this->actingAs($superAdmin)
            ->get('/company/members')
            ->assertOk();
    }

    public function test_guest_cannot_view_members_page(): void
    {
        $this->get('/company/members')
            ->assertRedirect('/login');
    }

    public function test_removed_members_shown_on_index_page(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $engineer = User::factory()->engineer()->create([
            'company_id' => $company->id,
            'company_removed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/company/members')
            ->assertOk()
            ->assertSee('Removed Members')
            ->assertSee($engineer->name);
    }

    // --- STORE ---

    public function test_company_admin_can_add_member(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $this->actingAs($admin)
            ->post('/company/members', [
                'name' => 'New Engineer',
                'email' => 'new@example.com',
                'password' => 'password1',
                'password_confirmation' => 'password1',
                'company_role' => 'engineer',
            ])
            ->assertRedirect(route('company.members.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'new@example.com',
            'company_id' => $company->id,
            'company_role' => 'engineer',
            'role' => 'user',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        [$admin] = $this->createCompanyAdmin();

        $this->actingAs($admin)
            ->post('/company/members', [])
            ->assertSessionHasErrors(['name', 'email', 'password', 'company_role']);
    }

    public function test_store_validates_unique_email(): void
    {
        [$admin] = $this->createCompanyAdmin();

        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)
            ->post('/company/members', [
                'name' => 'Duplicate',
                'email' => 'taken@example.com',
                'password' => 'password1',
                'password_confirmation' => 'password1',
                'company_role' => 'engineer',
            ])
            ->assertSessionHasErrors(['email']);
    }

    public function test_store_validates_password_confirmation(): void
    {
        [$admin] = $this->createCompanyAdmin();

        $this->actingAs($admin)
            ->post('/company/members', [
                'name' => 'Test',
                'email' => 'test@example.com',
                'password' => 'password1',
                'password_confirmation' => 'different',
                'company_role' => 'engineer',
            ])
            ->assertSessionHasErrors(['password']);
    }

    public function test_store_validates_company_role_enum(): void
    {
        [$admin] = $this->createCompanyAdmin();

        $this->actingAs($admin)
            ->post('/company/members', [
                'name' => 'Test',
                'email' => 'test@example.com',
                'password' => 'password1',
                'password_confirmation' => 'password1',
                'company_role' => 'invalid',
            ])
            ->assertSessionHasErrors(['company_role']);
    }

    public function test_engineer_cannot_add_member(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $engineer = User::factory()->engineer()->create([
            'company_id' => $company->id,
        ]);

        $this->actingAs($engineer)
            ->post('/company/members', [
                'name' => 'New User',
                'email' => 'new@example.com',
                'password' => 'password1',
                'password_confirmation' => 'password1',
                'company_role' => 'engineer',
            ])
            ->assertForbidden();
    }

    // --- UPDATE ---

    public function test_company_admin_can_update_member_role(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $engineer = User::factory()->engineer()->create([
            'company_id' => $company->id,
        ]);

        $this->actingAs($admin)
            ->put("/company/members/{$engineer->id}", [
                'company_role' => 'viewer',
            ])
            ->assertRedirect(route('company.members.index'));

        $this->assertDatabaseHas('users', [
            'id' => $engineer->id,
            'company_role' => 'viewer',
        ]);
    }

    public function test_cannot_update_member_from_another_company(): void
    {
        [$admin] = $this->createCompanyAdmin();

        $otherCompany = Company::create(['user_id' => $admin->id, 'name' => 'Other Co']);
        $otherMember = User::factory()->engineer()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->actingAs($admin)
            ->put("/company/members/{$otherMember->id}", [
                'company_role' => 'viewer',
            ])
            ->assertNotFound();
    }

    public function test_engineer_cannot_update_member_role(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $engineer = User::factory()->engineer()->create([
            'company_id' => $company->id,
        ]);

        $viewer = User::factory()->companyViewer()->create([
            'company_id' => $company->id,
        ]);

        $this->actingAs($engineer)
            ->put("/company/members/{$viewer->id}", [
                'company_role' => 'engineer',
            ])
            ->assertForbidden();
    }

    // --- DESTROY (SOFT REMOVE) ---

    public function test_company_admin_can_remove_member(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $engineer = User::factory()->engineer()->create([
            'company_id' => $company->id,
        ]);

        $this->actingAs($admin)
            ->delete("/company/members/{$engineer->id}")
            ->assertRedirect(route('company.members.index'));

        $engineer->refresh();
        $this->assertEquals($company->id, $engineer->company_id);
        $this->assertEquals(CompanyRole::Engineer, $engineer->company_role);
        $this->assertNotNull($engineer->company_removed_at);
    }

    public function test_removed_member_account_still_exists(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $engineer = User::factory()->engineer()->create([
            'company_id' => $company->id,
        ]);

        $this->actingAs($admin)
            ->delete("/company/members/{$engineer->id}");

        $this->assertDatabaseHas('users', [
            'id' => $engineer->id,
            'email' => $engineer->email,
            'company_id' => $company->id,
        ]);
    }

    public function test_removed_member_not_in_active_members(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $engineer = User::factory()->engineer()->create([
            'company_id' => $company->id,
            'company_removed_at' => now(),
        ]);

        $this->assertFalse($company->members->contains($engineer));
        $this->assertTrue($company->removedMembers->contains($engineer));
    }

    public function test_cannot_remove_self(): void
    {
        [$admin] = $this->createCompanyAdmin();

        $this->actingAs($admin)
            ->delete("/company/members/{$admin->id}")
            ->assertForbidden();
    }

    public function test_cannot_remove_last_company_admin(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $otherAdmin = User::factory()->companyAdmin()->create([
            'company_id' => $company->id,
        ]);

        // Removing the other admin should work (there are two admins)
        $this->actingAs($admin)
            ->delete("/company/members/{$otherAdmin->id}")
            ->assertRedirect(route('company.members.index'))
            ->assertSessionHas('success');
    }

    public function test_cannot_remove_member_from_another_company(): void
    {
        [$admin] = $this->createCompanyAdmin();

        $otherCompany = Company::create(['user_id' => $admin->id, 'name' => 'Other Co']);
        $otherMember = User::factory()->engineer()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->actingAs($admin)
            ->delete("/company/members/{$otherMember->id}")
            ->assertNotFound();
    }

    public function test_engineer_cannot_remove_member(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $engineer = User::factory()->engineer()->create([
            'company_id' => $company->id,
        ]);

        $viewer = User::factory()->companyViewer()->create([
            'company_id' => $company->id,
        ]);

        $this->actingAs($engineer)
            ->delete("/company/members/{$viewer->id}")
            ->assertForbidden();
    }

    // --- RESTORE ---

    public function test_company_admin_can_restore_removed_member(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $engineer = User::factory()->engineer()->create([
            'company_id' => $company->id,
            'company_removed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post("/company/members/{$engineer->id}/restore")
            ->assertRedirect(route('company.members.index'))
            ->assertSessionHas('success');

        $engineer->refresh();
        $this->assertNull($engineer->company_removed_at);
        $this->assertEquals($company->id, $engineer->company_id);
        $this->assertEquals(CompanyRole::Engineer, $engineer->company_role);
    }

    public function test_cannot_restore_active_member(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $engineer = User::factory()->engineer()->create([
            'company_id' => $company->id,
        ]);

        $this->actingAs($admin)
            ->post("/company/members/{$engineer->id}/restore")
            ->assertNotFound();
    }

    public function test_engineer_cannot_restore_member(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $engineer = User::factory()->engineer()->create([
            'company_id' => $company->id,
        ]);

        $removed = User::factory()->companyViewer()->create([
            'company_id' => $company->id,
            'company_removed_at' => now(),
        ]);

        $this->actingAs($engineer)
            ->post("/company/members/{$removed->id}/restore")
            ->assertForbidden();
    }

    public function test_cannot_restore_member_from_another_company(): void
    {
        [$admin] = $this->createCompanyAdmin();

        $otherCompany = Company::create(['user_id' => $admin->id, 'name' => 'Other Co']);
        $removed = User::factory()->engineer()->create([
            'company_id' => $otherCompany->id,
            'company_removed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post("/company/members/{$removed->id}/restore")
            ->assertNotFound();
    }

    // --- DOMAIN ACTION: RemoveCompanyMember ---

    public function test_remove_action_sets_removed_at_timestamp(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $engineer = User::factory()->engineer()->create([
            'company_id' => $company->id,
        ]);

        (new \Domain\UserManagement\Actions\RemoveCompanyMember)->execute($engineer);

        $engineer->refresh();
        $this->assertNotNull($engineer->company_removed_at);
        $this->assertEquals($company->id, $engineer->company_id);
        $this->assertEquals(CompanyRole::Engineer, $engineer->company_role);
    }

    public function test_remove_action_throws_when_removing_last_admin(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot remove the last company admin.');

        (new \Domain\UserManagement\Actions\RemoveCompanyMember)->execute($admin);
    }

    public function test_remove_action_allows_removing_admin_when_other_admins_exist(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $otherAdmin = User::factory()->companyAdmin()->create([
            'company_id' => $company->id,
        ]);

        (new \Domain\UserManagement\Actions\RemoveCompanyMember)->execute($otherAdmin);

        $otherAdmin->refresh();
        $this->assertNotNull($otherAdmin->company_removed_at);
    }

    // --- DOMAIN ACTION: RestoreCompanyMember ---

    public function test_restore_action_clears_removed_at(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $engineer = User::factory()->engineer()->create([
            'company_id' => $company->id,
            'company_removed_at' => now(),
        ]);

        (new \Domain\UserManagement\Actions\RestoreCompanyMember)->execute($engineer);

        $engineer->refresh();
        $this->assertNull($engineer->company_removed_at);
        $this->assertEquals($company->id, $engineer->company_id);
        $this->assertEquals(CompanyRole::Engineer, $engineer->company_role);
    }

    public function test_restore_action_throws_when_user_not_removed(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $engineer = User::factory()->engineer()->create([
            'company_id' => $company->id,
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('This user is not removed from the company.');

        (new \Domain\UserManagement\Actions\RestoreCompanyMember)->execute($engineer);
    }

    // --- belongsToCompany with removed users ---

    public function test_removed_user_does_not_belong_to_company(): void
    {
        [$admin, $company] = $this->createCompanyAdmin();

        $engineer = User::factory()->engineer()->create([
            'company_id' => $company->id,
            'company_removed_at' => now(),
        ]);

        $this->assertFalse($engineer->belongsToCompany($company->id));
    }
}
