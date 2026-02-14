import { test, expect } from '@playwright/test';
import { create, loginWithCompany } from './utils/laravel-helpers';

test.describe('Cost Detail Report', () => {
    /** Seed a project with two periods and forecasts at different rates to test rate change indicators */
    async function seedProjectWithRateChange(page) {
        const { user, company } = await loginWithCompany(page);

        const project = await create(page, 'App\\Models\\Project', {
            company_id: company.id,
            name: 'Rate Change Project',
            original_budget: 500000,
            start_date: '2024-01-01',
            end_date: '2024-06-01',
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
            name: 'Design Package',
            sort_order: 0,
        });

        const period1 = await create(page, 'App\\Models\\ForecastPeriod', {
            project_id: project.id,
            period_date: '2024-01-01',
        });

        const period2 = await create(page, 'App\\Models\\ForecastPeriod', {
            project_id: project.id,
            period_date: '2024-02-01',
        });

        // Item with rate change across periods
        const itemRateChanged = await create(page, 'App\\Models\\LineItem', {
            cost_package_id: pkg.id,
            item_no: '001',
            description: 'TL5 Barrier Rate Changed',
            unit_of_measure: 'LM',
            original_qty: 100,
            original_rate: 250,
            original_amount: 25000,
            sort_order: 0,
        });

        // Item with consistent rate
        const itemSameRate = await create(page, 'App\\Models\\LineItem', {
            cost_package_id: pkg.id,
            item_no: '002',
            description: 'W-Beam Same Rate',
            unit_of_measure: 'Each',
            original_qty: 4,
            original_rate: 6305,
            original_amount: 25220,
            sort_order: 1,
        });

        // Period 1 forecasts: original rate ($250)
        await create(page, 'App\\Models\\LineItemForecast', {
            line_item_id: itemRateChanged.id,
            forecast_period_id: period1.id,
            period_qty: 30,
            period_rate: 250,
            fcac_qty: 100,
            fcac_rate: 250,
        });

        // Period 2 forecasts: changed rate ($300)
        await create(page, 'App\\Models\\LineItemForecast', {
            line_item_id: itemRateChanged.id,
            forecast_period_id: period2.id,
            period_qty: 20,
            period_rate: 300,
            fcac_qty: 100,
            fcac_rate: 300,
        });

        // Same rate item forecasts
        await create(page, 'App\\Models\\LineItemForecast', {
            line_item_id: itemSameRate.id,
            forecast_period_id: period1.id,
            period_qty: 1,
            period_rate: 6305,
            fcac_qty: 4,
            fcac_rate: 6305,
        });

        await create(page, 'App\\Models\\LineItemForecast', {
            line_item_id: itemSameRate.id,
            forecast_period_id: period2.id,
            period_qty: 1,
            period_rate: 6305,
            fcac_qty: 4,
            fcac_rate: 6305,
        });

        return { user, project, ca, pkg, period1, period2, itemRateChanged, itemSameRate };
    }

    test('report shows project summary cards with correct totals', async ({ page }) => {
        const { project } = await seedProjectWithRateChange(page);

        await page.goto(`/projects/${project.id}/cost-detail-report`);

        // Summary cards should be visible
        await expect(page.getByText('Original Budget')).toBeVisible();
        await expect(page.getByText('Cost to Date')).toBeVisible();
        await expect(page.getByText('Cost to Complete')).toBeVisible();
        await expect(page.getByText('FCAC', { exact: false })).toBeVisible();
        await expect(page.getByText('Variance')).toBeVisible();
    });

    test('report shows control accounts with expandable line items', async ({ page }) => {
        const { project } = await seedProjectWithRateChange(page);

        await page.goto(`/projects/${project.id}/cost-detail-report`);

        // CA should be visible as collapsed accordion
        await expect(page.getByText('401CB00')).toBeVisible();
        await expect(page.getByText('Concrete Barriers')).toBeVisible();

        // Expand CA to see line items
        await page.getByText('401CB00').click();

        // Package and items should be visible
        await expect(page.getByText('Design Package')).toBeVisible();
        await expect(page.getByText('TL5 Barrier Rate Changed')).toBeVisible();
        await expect(page.getByText('W-Beam Same Rate')).toBeVisible();
    });

    test('rate change shows asterisk indicator on CTD rate column', async ({ page }) => {
        const { project } = await seedProjectWithRateChange(page);

        await page.goto(`/projects/${project.id}/cost-detail-report`);

        // Expand CA
        await page.getByText('401CB00').click();

        // The rate-changed item should have an asterisk button
        const rateChangedRow = page.locator('tr').filter({ hasText: 'TL5 Barrier Rate Changed' });
        await expect(rateChangedRow.locator('button[title="Rate changed during project"]')).toBeVisible();

        // The same-rate item should NOT have an asterisk button
        const sameRateRow = page.locator('tr').filter({ hasText: 'W-Beam Same Rate' });
        await expect(sameRateRow.locator('button[title="Rate changed during project"]')).toHaveCount(0);
    });

    test('clicking rate asterisk opens rate history modal', async ({ page }) => {
        const { project } = await seedProjectWithRateChange(page);

        await page.goto(`/projects/${project.id}/cost-detail-report`);

        // Expand CA
        await page.getByText('401CB00').click();

        // Click the asterisk on the rate-changed item
        const rateChangedRow = page.locator('tr').filter({ hasText: 'TL5 Barrier Rate Changed' });
        await rateChangedRow.locator('button[title="Rate changed during project"]').first().click();

        // Rate history modal should appear
        await expect(page.getByText('Rate History')).toBeVisible();
        await expect(page.getByText('TL5 Barrier Rate Changed')).toBeVisible();

        // Should show both periods with their rates
        await expect(page.locator('text=$250.00')).toBeVisible();
        await expect(page.locator('text=$300.00')).toBeVisible();

        // Should show "Total (CTD)" row
        await expect(page.getByText('Total (CTD)')).toBeVisible();
    });

    test('rate history modal highlights rows with changed rates in amber', async ({ page }) => {
        const { project } = await seedProjectWithRateChange(page);

        await page.goto(`/projects/${project.id}/cost-detail-report`);

        // Expand CA
        await page.getByText('401CB00').click();

        // Click asterisk
        const rateChangedRow = page.locator('tr').filter({ hasText: 'TL5 Barrier Rate Changed' });
        await rateChangedRow.locator('button[title="Rate changed during project"]').first().click();

        // The row with the changed rate ($300 vs original $250) should have amber bg
        const modalRows = page.locator('.p-6 tbody tr');
        const amberRow = modalRows.filter({ hasText: '$300.00' });
        await expect(amberRow).toHaveClass(/bg-amber-50/);

        // The row with the original rate ($250) should NOT have amber bg
        const normalRow = modalRows.filter({ hasText: '$250.00' });
        await expect(normalRow).not.toHaveClass(/bg-amber-50/);
    });

    test('report is read-only with no editable elements', async ({ page }) => {
        const { project } = await seedProjectWithRateChange(page);

        await page.goto(`/projects/${project.id}/cost-detail-report`);

        // Expand CA
        await page.getByText('401CB00').click();

        // Should NOT have any editable input fields
        await expect(page.locator('input[type="number"]')).toHaveCount(0);

        // Should NOT have any "Save" buttons
        await expect(page.getByRole('button', { name: 'Save' })).toHaveCount(0);
    });

    test('period selector changes displayed data', async ({ page }) => {
        const { project, period1, period2 } = await seedProjectWithRateChange(page);

        await page.goto(`/projects/${project.id}/cost-detail-report`);

        // Period selector should exist
        const periodSelector = page.locator('#period-selector');
        await expect(periodSelector).toBeVisible();

        // Should have 2 period options
        const options = periodSelector.locator('option');
        await expect(options).toHaveCount(2);
    });

    test('CTD values are cumulative across periods', async ({ page }) => {
        const { project } = await seedProjectWithRateChange(page);

        // Navigate to the latest period (Feb 2024)
        await page.goto(`/projects/${project.id}/cost-detail-report`);

        // Expand CA
        await page.getByText('401CB00').click();

        // For TL5 Barrier: CTD qty should be 30 (Jan) + 20 (Feb) = 50
        // CTD amount = 30*250 + 20*300 = 7500 + 6000 = 13500
        const rateChangedRow = page.locator('tr').filter({ hasText: 'TL5 Barrier Rate Changed' });

        // Check CTD Qty shows 50.0
        const ctdQtyCell = rateChangedRow.locator('td').filter({ hasText: '50.0' });
        await expect(ctdQtyCell.first()).toBeVisible();

        // Check CTD Amount shows $13,500.00
        const ctdAmountCell = rateChangedRow.locator('td').filter({ hasText: '$13,500.00' });
        await expect(ctdAmountCell.first()).toBeVisible();
    });
});
