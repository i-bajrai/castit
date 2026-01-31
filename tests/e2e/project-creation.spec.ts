import { test, expect } from '@playwright/test';
import { login, create } from './utils/laravel-helpers';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

test.describe('Project Creation', () => {
    test('should create a project and set up control accounts', async ({ page }) => {
        const user = await login(page);
        await create(page, 'App\\Models\\Company', {
            user_id: user.id,
            name: 'Test Company',
        });

        await page.goto('/dashboard');

        // Click "New Project" button to open the modal
        await page.getByTestId('new-project-button').click();

        // Fill in the project form within the modal
        const form = page.getByTestId('create-project-form');
        await form.locator('input[name="name"]').fill('Highway Bridge Project');
        await form.locator('input[name="project_number"]').fill('HWY-001');
        await form.locator('textarea[name="description"]').fill('A test construction project');
        await form.locator('input[name="original_budget"]').fill('500000');
        await page.getByTestId('start-date-input').fill('2026-03-01');
        await page.getByTestId('end-date-input').fill('2027-06-30');

        // Submit the form
        await page.getByTestId('submit-create-project').click();

        // Should redirect to the setup control accounts page
        await page.waitForURL('**/projects/*/setup');
        await expect(page).toHaveURL(/\/projects\/\d+\/setup/);
        await expect(page.getByText('Set Up Control Accounts')).toBeVisible();

        // Should have one empty row by default
        const setupForm = page.getByTestId('setup-control-accounts-form');
        await expect(setupForm.getByTestId('account-row')).toHaveCount(1);

        // Fill in the first control account
        const rows = setupForm.getByTestId('account-row');
        await rows.nth(0).getByTestId('account-code-input').fill('401CB00');
        await rows.nth(0).getByTestId('account-description-input').fill('Civil - Concrete Barriers');

        // Add another row
        await page.getByTestId('add-account-button').click();
        await expect(setupForm.getByTestId('account-row')).toHaveCount(2);

        // Fill in the second control account
        await rows.nth(1).getByTestId('account-code-input').fill('402ST00');
        await rows.nth(1).getByTestId('account-description-input').fill('Structural - Steel Works');

        // Submit the control accounts
        await page.getByTestId('save-accounts-button').click();

        // Should redirect to the project show page
        await page.waitForURL(/\/projects\/\d+$/);
        await expect(page.getByText('Highway Bridge Project')).toBeVisible();
    });

    test('should create a project without dates', async ({ page }) => {
        const user = await login(page);
        await create(page, 'App\\Models\\Company', {
            user_id: user.id,
            name: 'Test Company',
        });

        await page.goto('/dashboard');
        await page.getByTestId('new-project-button').click();

        const form = page.getByTestId('create-project-form');
        await form.locator('input[name="name"]').fill('No Dates Project');
        await form.locator('input[name="original_budget"]').fill('100000');

        // Leave start_date and end_date empty
        await expect(page.getByTestId('start-date-input')).toHaveValue('');
        await expect(page.getByTestId('end-date-input')).toHaveValue('');

        await page.getByTestId('submit-create-project').click();

        // Should redirect to the setup page
        await page.waitForURL('**/projects/*/setup');
        await expect(page.getByText('Set Up Control Accounts')).toBeVisible();
    });

    test('should allow skipping control account setup', async ({ page }) => {
        const user = await login(page);
        await create(page, 'App\\Models\\Company', {
            user_id: user.id,
            name: 'Test Company',
        });

        await page.goto('/dashboard');
        await page.getByTestId('new-project-button').click();

        const form = page.getByTestId('create-project-form');
        await form.locator('input[name="name"]').fill('Skip Setup Project');
        await form.locator('input[name="original_budget"]').fill('50000');

        await page.getByTestId('submit-create-project').click();
        await page.waitForURL('**/projects/*/setup');

        // Click "Skip for now"
        await page.getByTestId('skip-setup-link').click();

        // Should go to the project show page
        await page.waitForURL(/\/projects\/\d+$/);
        await expect(page.getByText('Skip Setup Project')).toBeVisible();
    });

    test('should show the empty state when no projects exist', async ({ page }) => {
        const user = await login(page);
        await create(page, 'App\\Models\\Company', {
            user_id: user.id,
            name: 'Empty Company',
        });

        await page.goto('/dashboard');

        const emptyState = page.getByTestId('empty-state');
        await expect(emptyState).toBeVisible();
        await expect(emptyState.getByText('No projects yet')).toBeVisible();
        await expect(emptyState.getByText('Get started by creating your first construction project')).toBeVisible();
    });

    test('should show validation errors when required fields are missing', async ({ page }) => {
        const user = await login(page);
        await create(page, 'App\\Models\\Company', {
            user_id: user.id,
            name: 'Test Company',
        });

        await page.goto('/dashboard');
        await page.getByTestId('new-project-button').click();

        const form = page.getByTestId('create-project-form');

        // Remove required attributes to bypass browser validation
        await form.locator('input[name="name"]').evaluate(el => el.removeAttribute('required'));
        await form.locator('input[name="original_budget"]').evaluate(el => el.removeAttribute('required'));

        // Submit empty form
        await page.getByTestId('submit-create-project').click();

        // After redirect back with errors, re-open the modal to see validation messages
        await page.getByTestId('new-project-button').click();
        await expect(page.getByTestId('create-project-form')).toBeVisible();

        // Should show validation errors inside the modal
        await expect(page.getByText('The name field is required')).toBeVisible();
    });

    test('should import control accounts from CSV', async ({ page }) => {
        const user = await login(page);
        await create(page, 'App\\Models\\Company', {
            user_id: user.id,
            name: 'Test Company',
        });

        await page.goto('/dashboard');
        await page.getByTestId('new-project-button').click();

        const form = page.getByTestId('create-project-form');
        await form.locator('input[name="name"]').fill('CSV Import Project');
        await form.locator('input[name="original_budget"]').fill('300000');

        await page.getByTestId('submit-create-project').click();
        await page.waitForURL('**/projects/*/setup');

        // Create a temporary CSV file
        const csvContent = 'code,description\n401CB00,Civil - Concrete Barriers\n402ST00,Structural - Steel Works\n403EL00,Electrical - Lighting';
        const csvPath = path.join(path.dirname(fileURLToPath(import.meta.url)), 'test-import.csv');
        fs.writeFileSync(csvPath, csvContent);

        // Upload the CSV file
        const fileInput = page.getByTestId('csv-file-input');
        await fileInput.setInputFiles(csvPath);

        // Verify the rows were populated
        const setupForm = page.getByTestId('setup-control-accounts-form');
        await expect(setupForm.getByTestId('account-row')).toHaveCount(3);

        const rows = setupForm.getByTestId('account-row');
        await expect(rows.nth(0).getByTestId('account-code-input')).toHaveValue('401CB00');
        await expect(rows.nth(0).getByTestId('account-description-input')).toHaveValue('Civil - Concrete Barriers');
        await expect(rows.nth(1).getByTestId('account-code-input')).toHaveValue('402ST00');
        await expect(rows.nth(1).getByTestId('account-description-input')).toHaveValue('Structural - Steel Works');
        await expect(rows.nth(2).getByTestId('account-code-input')).toHaveValue('403EL00');
        await expect(rows.nth(2).getByTestId('account-description-input')).toHaveValue('Electrical - Lighting');

        // Submit and verify
        await page.getByTestId('save-accounts-button').click();
        await page.waitForURL(/\/projects\/\d+$/);
        await expect(page.getByText('CSV Import Project')).toBeVisible();

        // Clean up temp file
        fs.unlinkSync(csvPath);
    });

    test('should display existing projects on the dashboard', async ({ page }) => {
        const user = await login(page);
        const company = await create(page, 'App\\Models\\Company', {
            user_id: user.id,
            name: 'Test Company',
        });

        await create(page, 'App\\Models\\Project', {
            company_id: company.id,
            name: 'Existing Project',
            original_budget: 250000,
        });

        await page.goto('/dashboard');

        const grid = page.getByTestId('projects-grid');
        await expect(grid).toBeVisible();
        await expect(grid.getByTestId('project-card').getByText('Existing Project')).toBeVisible();
        await expect(grid.getByText('$250,000.00')).toBeVisible();
    });
});
