import { test, expect } from '@playwright/test';
import { create, loginWithCompany } from './utils/laravel-helpers';

test.describe('Column Filter', () => {
    async function seedProjectWithLineItems(page) {
        const { user, company } = await loginWithCompany(page);

        const project = await create(page, 'App\\Models\\Project', {
            company_id: company.id,
            name: 'Column Filter Project',
            original_budget: 500000,
            start_date: '2024-01-01',
            end_date: '2024-06-01',
        });

        const ca = await create(page, 'App\\Models\\ControlAccount', {
            project_id: project.id,
            code: '401CB00',
            description: 'Civil - Concrete Barriers',
            phase: 'Phase 1',
            sort_order: 0,
        });

        const pkg = await create(page, 'App\\Models\\CostPackage', {
            project_id: project.id,
            control_account_id: ca.id,
            name: 'Design Package 02',
            sort_order: 0,
        });

        const period = await create(page, 'App\\Models\\ForecastPeriod', {
            project_id: project.id,
            period_date: '2024-01-01',
        });

        const lineItem = await create(page, 'App\\Models\\LineItem', {
            cost_package_id: pkg.id,
            item_no: '006-001',
            description: 'Concrete',
            original_qty: 100,
            original_rate: 250,
            original_amount: 25000,
            sort_order: 0,
        });

        return { user, project, ca, pkg, period, lineItem };
    }

    /** Navigate to the project page with a clean localStorage */
    async function gotoProject(page, projectId: number) {
        await page.goto(`/projects/${projectId}`);
        await page.evaluate(() => localStorage.removeItem('projectColumnFilter'));
        await page.reload();
    }

    test('columns button opens the filter dropdown', async ({ page }) => {
        const { project } = await seedProjectWithLineItems(page);
        await gotoProject(page, project.id);

        // Dropdown should not be visible initially
        await expect(page.getByText('Toggle Columns')).toBeHidden();

        // Click the Columns button
        await page.getByRole('button', { name: 'Columns' }).click();

        // Dropdown should now be visible
        await expect(page.getByText('Toggle Columns')).toBeVisible();

        // Should show all 12 column checkboxes
        const checkboxes = page.locator('label').filter({ has: page.locator('input[type="checkbox"]') }).filter({ hasText: /Orig Qty|Orig Rate|Orig Amount|Prev FCAC|CTD Qty|CTD Rate|CTD Amount|CTC Qty|CTC Amount|FCAC|Variance|Comments/ });
        await expect(checkboxes).toHaveCount(12);
    });

    test('default columns are visible and hidden correctly', async ({ page }) => {
        const { project } = await seedProjectWithLineItems(page);
        await gotoProject(page, project.id);

        // Expand the control account accordion to reveal the table
        await page.getByText('401CB00').click();

        // Columns that should be visible by default
        const visibleHeaders = ['Orig Amount', 'Prev FCAC', 'CTD Qty', 'CTD Rate', 'CTD Amount', 'CTC Amount', 'FCAC', 'Variance', 'Comments'];
        for (const header of visibleHeaders) {
            await expect(page.locator('th').filter({ hasText: header }).first()).toBeVisible();
        }

        // Columns that should be hidden by default
        const hiddenHeaders = ['Orig Qty', 'Orig Rate', 'CTC Qty'];
        for (const header of hiddenHeaders) {
            await expect(page.locator('th').filter({ hasText: header }).first()).toBeHidden();
        }
    });

    test('toggling a column off hides it from the table', async ({ page }) => {
        const { project } = await seedProjectWithLineItems(page);
        await gotoProject(page, project.id);

        // Expand the control account accordion
        await page.getByText('401CB00').click();

        // Verify "Variance" header is visible
        await expect(page.locator('th').filter({ hasText: 'Variance' }).first()).toBeVisible();

        // Open the dropdown and uncheck "Variance"
        await page.getByRole('button', { name: 'Columns' }).click();
        await page.locator('label').filter({ hasText: 'Variance' }).locator('input[type="checkbox"]').click();

        // "Variance" header should now be hidden
        await expect(page.locator('th').filter({ hasText: 'Variance' }).first()).toBeHidden();
    });

    test('toggling a column on makes it visible in the table', async ({ page }) => {
        const { project } = await seedProjectWithLineItems(page);
        await gotoProject(page, project.id);

        // Expand the control account accordion
        await page.getByText('401CB00').click();

        // "Orig Qty" should be hidden by default
        await expect(page.locator('th').filter({ hasText: 'Orig Qty' }).first()).toBeHidden();

        // Open the dropdown and check "Orig Qty"
        await page.getByRole('button', { name: 'Columns' }).click();
        await page.locator('label').filter({ hasText: 'Orig Qty' }).locator('input[type="checkbox"]').click();

        // "Orig Qty" header should now be visible
        await expect(page.locator('th').filter({ hasText: 'Orig Qty' }).first()).toBeVisible();
    });

    test('column preferences persist after page reload', async ({ page }) => {
        const { project } = await seedProjectWithLineItems(page);
        await gotoProject(page, project.id);

        // Expand the control account accordion
        await page.getByText('401CB00').click();

        // Turn on "Orig Qty" (hidden by default)
        await page.getByRole('button', { name: 'Columns' }).click();
        await page.locator('label').filter({ hasText: 'Orig Qty' }).locator('input[type="checkbox"]').click();
        await expect(page.locator('th').filter({ hasText: 'Orig Qty' }).first()).toBeVisible();

        // Turn off "Variance" (visible by default)
        await page.locator('label').filter({ hasText: 'Variance' }).locator('input[type="checkbox"]').click();
        await expect(page.locator('th').filter({ hasText: 'Variance' }).first()).toBeHidden();

        // Reload the page (without clearing localStorage)
        await page.reload();

        // Expand the accordion again after reload
        await page.getByText('401CB00').click();

        // Preferences should persist
        await expect(page.locator('th').filter({ hasText: 'Orig Qty' }).first()).toBeVisible();
        await expect(page.locator('th').filter({ hasText: 'Variance' }).first()).toBeHidden();
    });

    test('clicking away from dropdown closes it', async ({ page }) => {
        const { project } = await seedProjectWithLineItems(page);
        await gotoProject(page, project.id);

        // Open dropdown
        await page.getByRole('button', { name: 'Columns' }).click();
        await expect(page.getByText('Toggle Columns')).toBeVisible();

        // Click away from the dropdown
        await page.locator('body').click({ position: { x: 10, y: 10 } });

        // Dropdown should close
        await expect(page.getByText('Toggle Columns')).toBeHidden();
    });
});
