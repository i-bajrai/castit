import { test, expect } from '@playwright/test';
import { login, create } from './utils/laravel-helpers';

test.describe('Company Member Management', () => {
    async function setupCompanyAdmin(page: import('@playwright/test').Page) {
        const company = await create(page, 'App\\Models\\Company', { name: 'Test Co' });
        const admin = await login(page, {
            company_id: company.id,
            company_role: 'admin',
        });
        return { admin, company };
    }

    test('should display team members page', async ({ page }) => {
        const { admin, company } = await setupCompanyAdmin(page);

        await page.goto('/company/members');

        await expect(page.getByText('Team Members')).toBeVisible();
        await expect(page.getByText(company.name)).toBeVisible();
        await expect(page.getByTestId('add-member-button')).toBeVisible();
        await expect(page.getByTestId('member-row')).toHaveCount(1);
        const myRow = page.getByTestId('member-row').filter({ hasText: admin.name });
        await expect(myRow).toBeVisible();
        await expect(myRow.getByText('(you)')).toBeVisible();
    });

    test('should add a new member', async ({ page }) => {
        await setupCompanyAdmin(page);

        await page.goto('/company/members');
        await page.getByTestId('add-member-button').click();

        const form = page.getByTestId('create-member-form');
        await expect(form).toBeVisible();

        await form.locator('input[name="name"]').fill('New Engineer');
        await form.locator('input[name="email"]').fill('new-engineer@test.com');
        await form.locator('select[name="company_role"]').selectOption('engineer');
        await form.locator('input[name="password"]').fill('password123');
        await form.locator('input[name="password_confirmation"]').fill('password123');

        await page.getByTestId('submit-add-member').click();

        await page.waitForURL('**/company/members');
        await expect(page.getByText('Member added successfully.')).toBeVisible();
        await expect(page.getByText('New Engineer')).toBeVisible();
        await expect(page.getByText('new-engineer@test.com')).toBeVisible();
        await expect(page.getByTestId('member-row')).toHaveCount(2);
    });

    test('should edit a member role', async ({ page }) => {
        const { company } = await setupCompanyAdmin(page);

        await create(page, 'App\\Models\\User', {
            name: 'Test Engineer',
            company_id: company.id,
            company_role: 'engineer',
        });

        await page.goto('/company/members');
        await expect(page.getByTestId('member-row')).toHaveCount(2);

        // Find the engineer's row and click Edit Role
        const engineerRow = page.getByTestId('member-row').filter({ hasText: 'Test Engineer' });
        await engineerRow.getByText('Edit Role').click();

        // Change role to Viewer
        await page.locator('#edit-member-role').selectOption('viewer');
        await page.getByTestId('submit-update-role').click();

        await page.waitForURL('**/company/members');
        await expect(page.getByText('Member role updated.')).toBeVisible();

        // Verify the badge changed
        const updatedRow = page.getByTestId('member-row').filter({ hasText: 'Test Engineer' });
        await expect(updatedRow.getByText('Viewer')).toBeVisible();
    });

    test('should remove a member and show in removed section', async ({ page }) => {
        const { company } = await setupCompanyAdmin(page);

        await create(page, 'App\\Models\\User', {
            name: 'Soon Removed',
            company_id: company.id,
            company_role: 'viewer',
        });

        await page.goto('/company/members');

        // Click Remove on the viewer
        const viewerRow = page.getByTestId('member-row').filter({ hasText: 'Soon Removed' });
        await viewerRow.getByTestId('remove-member-button').click();

        // Confirm removal in the modal
        await expect(page.getByText('Are you sure you want to remove')).toBeVisible();
        await page.getByTestId('confirm-remove-member').click();

        await page.waitForURL('**/company/members');
        await expect(page.getByText('Member removed from company.')).toBeVisible();

        // Should now appear in the Removed Members section
        await expect(page.getByText('Removed Members')).toBeVisible();
        await expect(page.getByTestId('removed-member-row')).toHaveCount(1);
        await expect(page.getByTestId('removed-member-row').getByText('Soon Removed')).toBeVisible();
        await expect(page.getByTestId('restore-member-button')).toBeVisible();

        // Should no longer appear in active members
        await expect(page.getByTestId('member-row')).toHaveCount(1);
    });

    test('should restore a removed member', async ({ page }) => {
        const { company } = await setupCompanyAdmin(page);

        await create(page, 'App\\Models\\User', {
            name: 'Removed User',
            company_id: company.id,
            company_role: 'engineer',
            company_removed_at: new Date().toISOString(),
        });

        await page.goto('/company/members');

        // Removed Members section should be visible
        await expect(page.getByText('Removed Members')).toBeVisible();
        await expect(page.getByTestId('removed-member-row').getByText('Removed User')).toBeVisible();

        // Click Restore
        await page.getByTestId('restore-member-button').click();

        // Confirm restore in the modal
        await expect(page.getByText('Are you sure you want to restore')).toBeVisible();
        await page.getByTestId('confirm-restore-member').click();

        await page.waitForURL('**/company/members');
        await expect(page.getByText('Member restored to company.')).toBeVisible();

        // Should now appear in active members
        await expect(page.getByTestId('member-row').filter({ hasText: 'Removed User' })).toBeVisible();

        // Removed Members section should be gone (no more removed members)
        await expect(page.getByText('Removed Members')).not.toBeVisible();
    });

    test('should not show Remove button for yourself', async ({ page }) => {
        const { admin } = await setupCompanyAdmin(page);

        await page.goto('/company/members');

        const myRow = page.getByTestId('member-row').filter({ hasText: admin.name });
        await expect(myRow.getByText('Edit Role')).toBeVisible();
        await expect(myRow.getByTestId('remove-member-button')).not.toBeVisible();
    });

    test('should show removed member with their previous role badge', async ({ page }) => {
        const { company } = await setupCompanyAdmin(page);

        await create(page, 'App\\Models\\User', {
            name: 'Ex Admin',
            company_id: company.id,
            company_role: 'admin',
            company_removed_at: new Date().toISOString(),
        });

        await page.goto('/company/members');

        const removedRow = page.getByTestId('removed-member-row').filter({ hasText: 'Ex Admin' });
        await expect(removedRow.getByText('Admin', { exact: true })).toBeVisible();
    });
});
