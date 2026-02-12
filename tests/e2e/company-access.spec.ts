import { test, expect } from '@playwright/test';
import { login, create, update } from './utils/laravel-helpers';

test.describe('Company Access', () => {
    test('user without a company is redirected to no-company page', async ({ page }) => {
        await login(page);

        await page.goto('/dashboard');

        await page.waitForURL('**/no-company');
        await expect(page.getByText('Not assigned to a company')).toBeVisible();
        await expect(page.getByText("You're not currently assigned to a company")).toBeVisible();
    });

    test('user with revoked access is redirected to no-company page', async ({ page }) => {
        const company = await create(page, 'App\\Models\\Company', { name: 'Test Co' });
        const user = await login(page, {
            company_id: company.id,
            company_role: 'engineer',
            company_removed_at: new Date().toISOString(),
        });

        await page.goto('/dashboard');

        await page.waitForURL('**/no-company');
        await expect(page.getByText('Access revoked')).toBeVisible();
        await expect(page.getByText('Your access has been revoked')).toBeVisible();
    });

    test('user with a company can access the dashboard', async ({ page }) => {
        const company = await create(page, 'App\\Models\\Company', { name: 'Test Co' });
        await login(page, {
            company_id: company.id,
            company_role: 'engineer',
        });

        await page.goto('/dashboard');

        await expect(page).toHaveURL(/dashboard/);
        await expect(page.getByText('Not assigned to a company')).not.toBeVisible();
    });

    test('user with a company visiting no-company is redirected to dashboard', async ({ page }) => {
        const company = await create(page, 'App\\Models\\Company', { name: 'Test Co' });
        await login(page, {
            company_id: company.id,
            company_role: 'viewer',
        });

        await page.goto('/no-company');

        await page.waitForURL('**/dashboard');
    });
});
