import { test, expect } from '@playwright/test';
import { login, create } from './utils/laravel-helpers';

test.describe('Project Creation', () => {
    test('should create a new project from the dashboard', async ({ page }) => {
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

        // Should redirect to the project show page
        await page.waitForURL('**/projects/*');
        await expect(page).toHaveURL(/\/projects\/\d+/);

        // Verify project details are visible on the show page
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

        await page.waitForURL('**/projects/*');
        await expect(page.getByText('No Dates Project')).toBeVisible();
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

        // Should show validation errors inside the modal
        await expect(page.getByText('The name field is required')).toBeVisible();
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
