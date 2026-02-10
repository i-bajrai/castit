<?php

namespace Tests\Feature;

use App\Enums\CompanyRole;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserControllerTest extends TestCase
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

    public function test_super_admin_can_view_users_page(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_regular_user_cannot_view_users_page(): void
    {
        $user = $this->createRegularUser();

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/users')
            ->assertRedirect('/login');
    }

    // --- STORE ---

    public function test_super_admin_can_create_user_with_all_fields(): void
    {
        $admin = $this->createSuperAdmin();
        $company = Company::create(['user_id' => $admin->id, 'name' => 'Acme Corp']);

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'user',
                'company_id' => $company->id,
                'company_role' => 'engineer',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => 'user',
            'company_id' => $company->id,
            'company_role' => 'engineer',
        ]);
    }

    public function test_super_admin_can_create_user_without_company(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'admin',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'role' => 'admin',
            'company_id' => null,
            'company_role' => null,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->post('/admin/users', [])
            ->assertSessionHasErrors(['name', 'email', 'password', 'role']);
    }

    public function test_store_validates_unique_email(): void
    {
        $admin = $this->createSuperAdmin();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Duplicate',
                'email' => 'taken@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'user',
            ])
            ->assertSessionHasErrors(['email']);
    }

    public function test_store_validates_password_confirmation(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'wrong_confirmation',
                'role' => 'user',
            ])
            ->assertSessionHasErrors(['password']);
    }

    public function test_store_validates_role_is_valid_enum(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'invalid_role',
            ])
            ->assertSessionHasErrors(['role']);
    }

    public function test_store_validates_company_role_required_when_company_id_provided(): void
    {
        $admin = $this->createSuperAdmin();
        $company = Company::create(['user_id' => $admin->id, 'name' => 'Acme Corp']);

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'user',
                'company_id' => $company->id,
            ])
            ->assertSessionHasErrors(['company_role']);
    }

    public function test_regular_user_cannot_create_users(): void
    {
        $user = $this->createRegularUser();

        $this->actingAs($user)
            ->post('/admin/users', [
                'name' => 'New User',
                'email' => 'new@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'user',
            ])
            ->assertForbidden();
    }

    // --- UPDATE ---

    public function test_super_admin_can_update_user_details(): void
    {
        $admin = $this->createSuperAdmin();
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $this->actingAs($admin)
            ->put("/admin/users/{$user->id}", [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'role' => 'user',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_super_admin_can_update_user_company_assignment(): void
    {
        $admin = $this->createSuperAdmin();
        $company = Company::create(['user_id' => $admin->id, 'name' => 'Acme Corp']);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->put("/admin/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'user',
                'company_id' => $company->id,
                'company_role' => 'admin',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'company_id' => $company->id,
            'company_role' => 'admin',
        ]);
    }

    public function test_cannot_demote_the_last_super_admin(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->put("/admin/users/{$admin->id}", [
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => 'user',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'Cannot demote the last super admin.');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
        ]);
    }

    public function test_can_demote_admin_when_other_admins_exist(): void
    {
        $admin1 = $this->createSuperAdmin();
        $admin2 = $this->createSuperAdmin();

        $this->actingAs($admin1)
            ->put("/admin/users/{$admin2->id}", [
                'name' => $admin2->name,
                'email' => $admin2->email,
                'role' => 'user',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $admin2->id,
            'role' => 'user',
        ]);
    }

    public function test_update_validates_unique_email_ignoring_self(): void
    {
        $admin = $this->createSuperAdmin();
        $user = User::factory()->create(['email' => 'user@example.com']);
        User::factory()->create(['email' => 'taken@example.com']);

        // Should fail when using another user's email
        $this->actingAs($admin)
            ->put("/admin/users/{$user->id}", [
                'name' => $user->name,
                'email' => 'taken@example.com',
                'role' => 'user',
            ])
            ->assertSessionHasErrors(['email']);

        // Should succeed when keeping the same email
        $this->actingAs($admin)
            ->put("/admin/users/{$user->id}", [
                'name' => 'Updated Name',
                'email' => 'user@example.com',
                'role' => 'user',
            ])
            ->assertRedirect(route('admin.users.index'));
    }

    public function test_regular_user_cannot_update_users(): void
    {
        $user = $this->createRegularUser();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->put("/admin/users/{$otherUser->id}", [
                'name' => 'Hacked',
                'email' => $otherUser->email,
                'role' => 'admin',
            ])
            ->assertForbidden();
    }

    // --- DESTROY ---

    public function test_super_admin_can_delete_another_user(): void
    {
        $admin = $this->createSuperAdmin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->delete("/admin/users/{$user->id}")
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_cannot_delete_yourself(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->delete("/admin/users/{$admin->id}")
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'You cannot delete your own account.');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_cannot_delete_the_last_super_admin(): void
    {
        // The DeleteUser action checks: is the target an admin AND are there no OTHER admins?
        // When the acting user is also an admin, the target is never truly the "last" admin.
        // We test this via the domain action directly to verify the safety guard works.
        $onlyAdmin = $this->createSuperAdmin();

        $action = app(\Domain\UserManagement\Actions\DeleteUser::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot delete the last super admin.');

        // Use a dummy user as the "acting" user to bypass the self-delete check
        $regularUser = $this->createRegularUser();
        $action->execute($onlyAdmin, $regularUser);
    }

    public function test_deleting_admin_succeeds_when_other_admins_exist(): void
    {
        $admin1 = $this->createSuperAdmin();
        $admin2 = $this->createSuperAdmin();

        $this->actingAs($admin1)
            ->delete("/admin/users/{$admin2->id}")
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $admin2->id]);
    }

    public function test_deleting_user_who_owns_company_nullifies_ownership(): void
    {
        $admin = $this->createSuperAdmin();
        $owner = User::factory()->create();
        $company = Company::create(['user_id' => $owner->id, 'name' => 'Owned Corp']);

        $this->actingAs($admin)
            ->delete("/admin/users/{$owner->id}")
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'user_id' => null,
        ]);
    }

    public function test_deleting_user_without_company_ownership_succeeds(): void
    {
        $admin = $this->createSuperAdmin();
        $user = User::factory()->create();

        // User does not own any company, so DeleteUser's nullify step is a no-op
        $this->actingAs($admin)
            ->delete("/admin/users/{$user->id}")
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_regular_user_cannot_delete_users(): void
    {
        $user = $this->createRegularUser();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->delete("/admin/users/{$otherUser->id}")
            ->assertForbidden();
    }

    // --- EDGE CASES ---

    public function test_setting_company_id_to_null_clears_company_role_via_model_boot(): void
    {
        $owner = User::factory()->create();
        $company = Company::create(['user_id' => $owner->id, 'name' => 'Test Co']);
        $user = User::factory()->engineer()->create([
            'company_id' => $company->id,
        ]);

        $this->assertNotNull($user->company_role);

        $user->update(['company_id' => null]);
        $user->refresh();

        $this->assertNull($user->company_id);
        $this->assertNull($user->company_role);
    }

    public function test_store_validates_company_role_is_valid_enum(): void
    {
        $admin = $this->createSuperAdmin();
        $company = Company::create(['user_id' => $admin->id, 'name' => 'Acme Corp']);

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'user',
                'company_id' => $company->id,
                'company_role' => 'invalid_company_role',
            ])
            ->assertSessionHasErrors(['company_role']);
    }

    public function test_store_validates_company_id_must_exist(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'user',
                'company_id' => 99999,
                'company_role' => 'engineer',
            ])
            ->assertSessionHasErrors(['company_id']);
    }

    public function test_store_validates_password_minimum_length(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'short',
                'password_confirmation' => 'short',
                'role' => 'user',
            ])
            ->assertSessionHasErrors(['password']);
    }

    public function test_update_password_is_optional(): void
    {
        $admin = $this->createSuperAdmin();
        $user = User::factory()->create();
        $originalPassword = $user->password;

        $this->actingAs($admin)
            ->put("/admin/users/{$user->id}", [
                'name' => 'Updated Name',
                'email' => $user->email,
                'role' => 'user',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals($originalPassword, $user->password);
    }

    public function test_super_admin_can_create_user_with_admin_role(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'New Admin',
                'email' => 'newadmin@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'admin',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'newadmin@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_update_can_change_user_password(): void
    {
        $admin = $this->createSuperAdmin();
        $user = User::factory()->create();
        $originalPassword = $user->password;

        $this->actingAs($admin)
            ->put("/admin/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'user',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertNotEquals($originalPassword, $user->password);
    }

    public function test_update_removing_company_clears_company_role(): void
    {
        $admin = $this->createSuperAdmin();
        $company = Company::create(['user_id' => $admin->id, 'name' => 'Acme Corp']);
        $user = User::factory()->engineer()->create([
            'company_id' => $company->id,
        ]);

        $this->actingAs($admin)
            ->put("/admin/users/{$user->id}", [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'user',
                'company_id' => null,
                'company_role' => null,
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertNull($user->company_id);
        $this->assertNull($user->company_role);
    }

    public function test_store_creates_user_with_success_flash_message(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'Flash User',
                'email' => 'flash@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'user',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User created successfully.');
    }

    public function test_update_returns_success_flash_message(): void
    {
        $admin = $this->createSuperAdmin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->put("/admin/users/{$user->id}", [
                'name' => 'Updated',
                'email' => $user->email,
                'role' => 'user',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User updated successfully.');
    }

    public function test_destroy_returns_success_flash_message(): void
    {
        $admin = $this->createSuperAdmin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->delete("/admin/users/{$user->id}")
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'User deleted successfully.');
    }
}
