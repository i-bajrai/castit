import { test, expect } from '@playwright/test';
import { create, loginWithCompany } from './utils/laravel-helpers';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

test.describe('Unassigned Items', () => {
    async function seedProject(page) {
        const { user, company } = await loginWithCompany(page);

        const project = await create(page, 'App\\Models\\Project', {
            company_id: company.id,
            name: 'Barrier Project',
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

    test('import with unknown items redirects to unassigned page', async ({ page }) => {
        const { project, period } = await seedProject(page);

        // Go to settings
        await page.goto(`/projects/${project.id}/settings`);
        await expect(page.getByText('Import Historical Data')).toBeVisible();

        // Create CSV with one known + one unknown item
        const csvContent = [
            'description,period,period_qty',
            'Concrete,2024-01,80',
            'Unknown Barrier Item,2024-01,50',
        ].join('\n');
        const csvPath = path.join(path.dirname(fileURLToPath(import.meta.url)), 'test-unassigned-import.csv');
        fs.writeFileSync(csvPath, csvContent);

        // Upload CSV
        const fileInput = page.locator('input[name="csv_file"]');
        await fileInput.setInputFiles(csvPath);
        await page.locator('form[action*="forecasts/import"] button[type="submit"], form[action*="forecasts/import"] button:has-text("Import")').click();

        // Should redirect to the unassigned page
        await page.waitForURL(`**/projects/${project.id}/unassigned`);
        await expect(page.getByText('unassigned line item')).toBeVisible();
        await expect(page.getByText('Unknown Barrier Item')).toBeVisible();

        // Clean up temp file
        fs.unlinkSync(csvPath);
    });

    test('clean import stays on settings page', async ({ page }) => {
        const { project, period } = await seedProject(page);

        await page.goto(`/projects/${project.id}/settings`);

        // CSV with only known items
        const csvContent = 'description,period,period_qty\nConcrete,2024-01,80\n';
        const csvPath = path.join(path.dirname(fileURLToPath(import.meta.url)), 'test-clean-import.csv');
        fs.writeFileSync(csvPath, csvContent);

        const fileInput = page.locator('input[name="csv_file"]');
        await fileInput.setInputFiles(csvPath);
        await page.locator('form[action*="forecasts/import"] button[type="submit"], form[action*="forecasts/import"] button:has-text("Import")').click();

        // Should stay on settings
        await page.waitForURL(`**/projects/${project.id}/settings`);
        await expect(page.getByText('forecast(s) imported')).toBeVisible();

        fs.unlinkSync(csvPath);
    });

    test('can move unassigned item to existing package', async ({ page }) => {
        const { project, pkg, period } = await seedProject(page);

        // First import to create unassigned items
        await page.goto(`/projects/${project.id}/settings`);
        const csvContent = 'description,period,period_qty\nNew Barrier Type,2024-01,30\n';
        const csvPath = path.join(path.dirname(fileURLToPath(import.meta.url)), 'test-move-import.csv');
        fs.writeFileSync(csvPath, csvContent);

        const fileInput = page.locator('input[name="csv_file"]');
        await fileInput.setInputFiles(csvPath);
        await page.locator('form[action*="forecasts/import"] button[type="submit"], form[action*="forecasts/import"] button:has-text("Import")').click();
        await page.waitForURL(`**/projects/${project.id}/unassigned`);

        // Should see the unassigned item
        await expect(page.getByText('New Barrier Type')).toBeVisible();

        // Select "Move to package" (default) and pick target package
        const targetSelect = page.locator('select[name="operations[0][target_package_id]"]');
        await targetSelect.selectOption({ value: String(pkg.id) });

        // Submit
        await page.getByTestId('reassign-items-button').click();

        // Should redirect to settings since all items are now assigned
        await page.waitForURL(`**/projects/${project.id}/settings`);
        await expect(page.getByText('item(s) moved')).toBeVisible();

        fs.unlinkSync(csvPath);
    });

    test('can merge unassigned item into existing line item', async ({ page }) => {
        const { project, lineItem, period } = await seedProject(page);

        // Import to create unassigned items
        await page.goto(`/projects/${project.id}/settings`);
        const csvContent = 'description,period,period_qty\nConcrete Duplicate,2024-01,20\n';
        const csvPath = path.join(path.dirname(fileURLToPath(import.meta.url)), 'test-merge-import.csv');
        fs.writeFileSync(csvPath, csvContent);

        const fileInput = page.locator('input[name="csv_file"]');
        await fileInput.setInputFiles(csvPath);
        await page.locator('form[action*="forecasts/import"] button[type="submit"], form[action*="forecasts/import"] button:has-text("Import")').click();
        await page.waitForURL(`**/projects/${project.id}/unassigned`);

        await expect(page.getByText('Concrete Duplicate')).toBeVisible();

        // Switch to "Merge into line item"
        const actionSelect = page.locator('select').filter({ hasText: 'Move to package' }).first();
        await actionSelect.selectOption('merge');

        // Select the target line item
        const mergeSelect = page.locator('select[name="operations[0][merge_into_id]"]');
        await mergeSelect.selectOption({ value: String(lineItem.id) });

        // Submit
        await page.getByTestId('reassign-items-button').click();

        // Should redirect to settings since all items are now assigned
        await page.waitForURL(`**/projects/${project.id}/settings`);
        await expect(page.getByText('item(s) merged')).toBeVisible();

        fs.unlinkSync(csvPath);
    });

    test('settings page shows unassigned banner after import', async ({ page }) => {
        const { project, period } = await seedProject(page);

        // Import unknown items first
        await page.goto(`/projects/${project.id}/settings`);
        const csvContent = 'description,period,period_qty\nSome Unknown Item,2024-01,10\n';
        const csvPath = path.join(path.dirname(fileURLToPath(import.meta.url)), 'test-banner-import.csv');
        fs.writeFileSync(csvPath, csvContent);

        const fileInput = page.locator('input[name="csv_file"]');
        await fileInput.setInputFiles(csvPath);
        await page.locator('form[action*="forecasts/import"] button[type="submit"], form[action*="forecasts/import"] button:has-text("Import")').click();
        await page.waitForURL(`**/projects/${project.id}/unassigned`);

        // Go back to settings
        await page.goto(`/projects/${project.id}/settings`);

        // Should show the amber banner
        await expect(page.getByText('unassigned line item')).toBeVisible();
        await expect(page.getByRole('link', { name: 'Review & Assign' })).toBeVisible();

        // Click the link to go to unassigned page
        await page.getByRole('link', { name: 'Review & Assign' }).click();
        await page.waitForURL(`**/projects/${project.id}/unassigned`);
        await expect(page.getByText('Some Unknown Item')).toBeVisible();

        fs.unlinkSync(csvPath);
    });
});
