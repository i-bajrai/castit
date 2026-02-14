import { test, expect } from '@playwright/test';
import { create, loginWithCompany } from './utils/laravel-helpers';

test.describe('Forecast Data Entry', () => {
    /** Seed a project with a CA, package, line items, period (current month), and forecasts */
    async function seedProject(page) {
        const { user, company } = await loginWithCompany(page);

        const project = await create(page, 'App\\Models\\Project', {
            company_id: company.id,
            name: 'Data Entry Project',
            original_budget: 500000,
            start_date: '2024-01-01',
            end_date: '2026-12-01',
        });

        const ca = await create(page, 'App\\Models\\ControlAccount', {
            project_id: project.id,
            code: '401CB00',
            description: 'Concrete Barriers',
            phase: 'Phase 1',
            sort_order: 0,
        });

        const pkg = await create(page, 'App\\Models\\CostPackage', {
            project_id: project.id,
            control_account_id: ca.id,
            name: 'Design Package 02',
            sort_order: 0,
        });

        // Current month period (editable)
        const now = new Date();
        const currentMonthDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
        const currentPeriod = await create(page, 'App\\Models\\ForecastPeriod', {
            project_id: project.id,
            period_date: currentMonthDate,
        });

        const item1 = await create(page, 'App\\Models\\LineItem', {
            cost_package_id: pkg.id,
            item_no: '001',
            description: 'TL5 Barrier',
            unit_of_measure: 'LM',
            original_qty: 100,
            original_rate: 250,
            original_amount: 25000,
            sort_order: 0,
        });

        const item2 = await create(page, 'App\\Models\\LineItem', {
            cost_package_id: pkg.id,
            item_no: '002',
            description: 'W-Beam Transition',
            unit_of_measure: 'Each',
            original_qty: 4,
            original_rate: 6305,
            original_amount: 25220,
            sort_order: 1,
        });

        // Create forecasts for the current period
        const forecast1 = await create(page, 'App\\Models\\LineItemForecast', {
            line_item_id: item1.id,
            forecast_period_id: currentPeriod.id,
            period_qty: 0,
            period_rate: 250,
            fcac_qty: 100,
            fcac_rate: 250,
        });

        const forecast2 = await create(page, 'App\\Models\\LineItemForecast', {
            line_item_id: item2.id,
            forecast_period_id: currentPeriod.id,
            period_qty: 0,
            period_rate: 6305,
            fcac_qty: 4,
            fcac_rate: 6305,
        });

        return { user, project, ca, pkg, currentPeriod, item1, item2, forecast1, forecast2 };
    }

    test('project show page displays CA list with Enter data links', async ({ page }) => {
        const { project, ca } = await seedProject(page);

        await page.goto(`/projects/${project.id}`);

        // Should see CA code and description
        await expect(page.getByText('401CB00')).toBeVisible();
        await expect(page.getByText('Concrete Barriers')).toBeVisible();

        // Should see "Enter data" link
        await expect(page.getByRole('link', { name: 'Enter data' })).toBeVisible();
    });

    test('clicking Enter data navigates to per-CA forecast page', async ({ page }) => {
        const { project, ca } = await seedProject(page);

        await page.goto(`/projects/${project.id}`);
        await page.getByRole('link', { name: 'Enter data' }).click();

        // Should navigate to the per-CA forecast page
        await page.waitForURL(`**/projects/${project.id}/control-accounts/${ca.id}/forecast**`);

        // Should see the CA code in the header
        await expect(page.getByText('401CB00')).toBeVisible();
        await expect(page.getByText('Concrete Barriers')).toBeVisible();
    });

    test('per-CA forecast page shows line items grouped by package', async ({ page }) => {
        const { project, ca } = await seedProject(page);

        await page.goto(`/projects/${project.id}/control-accounts/${ca.id}/forecast`);

        // Package name visible
        await expect(page.getByText('Design Package 02')).toBeVisible();

        // Line items visible
        await expect(page.getByText('TL5 Barrier')).toBeVisible();
        await expect(page.getByText('W-Beam Transition')).toBeVisible();

        // Table headers visible
        await expect(page.locator('th').filter({ hasText: 'Item' }).first()).toBeVisible();
        await expect(page.locator('th').filter({ hasText: 'Description' }).first()).toBeVisible();
        await expect(page.locator('th').filter({ hasText: 'UoM' }).first()).toBeVisible();
        await expect(page.locator('th').filter({ hasText: 'Orig Qty' }).first()).toBeVisible();
        await expect(page.locator('th').filter({ hasText: 'This Month Qty' }).first()).toBeVisible();
        await expect(page.locator('th').filter({ hasText: 'Rate' }).first()).toBeVisible();
        await expect(page.locator('th').filter({ hasText: 'Comments' }).first()).toBeVisible();
    });

    test('current month period shows editable badge', async ({ page }) => {
        const { project, ca } = await seedProject(page);

        await page.goto(`/projects/${project.id}/control-accounts/${ca.id}/forecast`);

        await expect(page.getByText('Editable')).toBeVisible();
    });

    test('can edit period qty via modal', async ({ page }) => {
        const { project, ca, item1 } = await seedProject(page);

        await page.goto(`/projects/${project.id}/control-accounts/${ca.id}/forecast`);

        // Click the qty button for TL5 Barrier (should show "0")
        const qtyButton = page.locator('tr').filter({ hasText: 'TL5 Barrier' }).locator('button').first();
        await qtyButton.click();

        // Modal should appear with the item name
        await expect(page.getByText('This Month - TL5 Barrier')).toBeVisible();

        // Fill in the quantity
        const qtyInput = page.locator('input[type="number"]').first();
        await qtyInput.fill('25');

        // Click Save
        await page.getByRole('button', { name: 'Save' }).click();

        // Modal should close and qty should update
        await expect(page.getByText('This Month - TL5 Barrier')).toBeHidden();
        // The button text should now show 25
        await expect(qtyButton).toHaveText('25');
    });

    test('can edit comment via modal', async ({ page }) => {
        const { project, ca } = await seedProject(page);

        await page.goto(`/projects/${project.id}/control-accounts/${ca.id}/forecast`);

        // Click the comment button for TL5 Barrier
        const commentButton = page.locator('tr').filter({ hasText: 'TL5 Barrier' }).getByText('Add comment...');
        await commentButton.click();

        // Modal should appear
        await expect(page.getByText('Comment - TL5 Barrier')).toBeVisible();

        // Fill in a comment
        await page.locator('textarea').fill('Test comment for barrier');

        // Click Save
        await page.getByRole('button', { name: 'Save' }).click();

        // Modal should close and comment should be visible
        await expect(page.getByText('Comment - TL5 Barrier')).toBeHidden();
        await expect(page.locator('tr').filter({ hasText: 'TL5 Barrier' }).getByText('Test comment for barrier')).toBeVisible();
    });

    test('read-only period shows non-editable values', async ({ page }) => {
        const { user, company } = await loginWithCompany(page);

        const project = await create(page, 'App\\Models\\Project', {
            company_id: company.id,
            name: 'Read Only Project',
            original_budget: 100000,
            start_date: '2024-01-01',
            end_date: '2026-12-01',
        });

        const ca = await create(page, 'App\\Models\\ControlAccount', {
            project_id: project.id,
            code: '100A',
            description: 'Test Account',
            phase: 'Phase 1',
            sort_order: 0,
        });

        const pkg = await create(page, 'App\\Models\\CostPackage', {
            project_id: project.id,
            control_account_id: ca.id,
            name: 'Package A',
            sort_order: 0,
        });

        // Past period (not editable)
        const pastPeriod = await create(page, 'App\\Models\\ForecastPeriod', {
            project_id: project.id,
            period_date: '2024-01-01',
        });

        const item = await create(page, 'App\\Models\\LineItem', {
            cost_package_id: pkg.id,
            item_no: '001',
            description: 'Past Item',
            original_qty: 50,
            original_rate: 100,
            original_amount: 5000,
            sort_order: 0,
        });

        await create(page, 'App\\Models\\LineItemForecast', {
            line_item_id: item.id,
            forecast_period_id: pastPeriod.id,
            period_qty: 10,
            period_rate: 100,
            fcac_qty: 50,
            fcac_rate: 100,
        });

        await page.goto(`/projects/${project.id}/control-accounts/${ca.id}/forecast`);

        // Should show "Read Only" badge
        await expect(page.getByText('Read Only')).toBeVisible();

        // Should NOT have editable buttons (no clickable qty cells)
        await expect(page.locator('button').filter({ hasText: /^\d/ })).toHaveCount(0);
    });

    test('period selector changes the displayed period', async ({ page }) => {
        const { user, company } = await loginWithCompany(page);

        const project = await create(page, 'App\\Models\\Project', {
            company_id: company.id,
            name: 'Period Selector Project',
            original_budget: 100000,
            start_date: '2024-01-01',
            end_date: '2026-12-01',
        });

        const ca = await create(page, 'App\\Models\\ControlAccount', {
            project_id: project.id,
            code: '200A',
            description: 'Test CA',
            phase: 'Phase 1',
            sort_order: 0,
        });

        const pkg = await create(page, 'App\\Models\\CostPackage', {
            project_id: project.id,
            control_account_id: ca.id,
            name: 'Package B',
            sort_order: 0,
        });

        // Create two periods
        const period1 = await create(page, 'App\\Models\\ForecastPeriod', {
            project_id: project.id,
            period_date: '2024-01-01',
        });

        const now = new Date();
        const currentMonthDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
        const period2 = await create(page, 'App\\Models\\ForecastPeriod', {
            project_id: project.id,
            period_date: currentMonthDate,
        });

        const item = await create(page, 'App\\Models\\LineItem', {
            cost_package_id: pkg.id,
            item_no: '001',
            description: 'Test Item',
            original_qty: 50,
            original_rate: 100,
            original_amount: 5000,
            sort_order: 0,
        });

        await create(page, 'App\\Models\\LineItemForecast', {
            line_item_id: item.id,
            forecast_period_id: period1.id,
            period_qty: 10,
            period_rate: 100,
            fcac_qty: 50,
            fcac_rate: 100,
        });

        await create(page, 'App\\Models\\LineItemForecast', {
            line_item_id: item.id,
            forecast_period_id: period2.id,
            period_qty: 15,
            period_rate: 100,
            fcac_qty: 50,
            fcac_rate: 100,
        });

        // Navigate to the current month period (editable)
        await page.goto(`/projects/${project.id}/control-accounts/${ca.id}/forecast`);

        // Period selector should be visible with both periods
        const periodSelector = page.locator('#period-selector');
        await expect(periodSelector).toBeVisible();

        // Select the past period via the dropdown
        await periodSelector.selectOption({ value: await periodSelector.locator('option').last().getAttribute('value') as string });

        // Should navigate and show Read Only badge for the past period
        await page.waitForURL(`**/forecast?period=${period1.id}`);
        await expect(page.getByText('Read Only')).toBeVisible();
    });
});
