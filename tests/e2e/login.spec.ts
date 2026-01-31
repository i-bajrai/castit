import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const TEST_USER_EMAIL = 'playwright@test.com';
const TEST_USER_PASSWORD = 'password';

test.describe('Login Page', () => {
    test.beforeAll(() => {
        // Create a test user via artisan tinker (upsert to avoid duplicates)
        // Note: password cast on User model handles hashing automatically
        execSync(
            `php artisan tinker --execute="\\App\\Models\\User::query()->where('email', '${TEST_USER_EMAIL}')->delete(); \\App\\Models\\User::factory()->create(['email' => '${TEST_USER_EMAIL}', 'password' => '${TEST_USER_PASSWORD}'])"`,
            { cwd: process.cwd(), stdio: 'pipe' }
        );
    });

    test.afterAll(() => {
        // Clean up test user
        execSync(
            `php artisan tinker --execute="\\App\\Models\\User::where('email', '${TEST_USER_EMAIL}')->delete()"`,
            { cwd: process.cwd(), stdio: 'pipe' }
        );
    });

    test('should display the login form', async ({ page }) => {
        await page.goto('/login');

        await expect(page.locator('input#email')).toBeVisible();
        await expect(page.locator('input#password')).toBeVisible();
        await expect(page.locator('input#remember_me')).toBeVisible();
        await expect(page.getByRole('button', { name: 'Log in' })).toBeVisible();
        await expect(page.getByText('Forgot your password?')).toBeVisible();
    });

    test('should login successfully with valid credentials', async ({ page }) => {
        await page.goto('/login');

        await page.locator('input#email').fill(TEST_USER_EMAIL);
        await page.locator('input#password').fill(TEST_USER_PASSWORD);
        await page.getByRole('button', { name: 'Log in' }).click();

        // Debug: capture where we end up
        await page.waitForLoadState('networkidle');
        await page.screenshot({ path: 'test-results/debug-login.png' });
        console.log('Current URL after login:', page.url());

        await page.waitForURL('**/dashboard');
        await expect(page).toHaveURL(/dashboard/);
    });

    test('should show error with invalid credentials', async ({ page }) => {
        await page.goto('/login');

        await page.locator('input#email').fill(TEST_USER_EMAIL);
        await page.locator('input#password').fill('wrong-password');
        await page.getByRole('button', { name: 'Log in' }).click();

        await expect(page.locator('.mt-2')).toContainText('These credentials do not match our records');
    });

    test('should show validation errors for empty form submission', async ({ page }) => {
        await page.goto('/login');

        // The email and password fields have the "required" attribute,
        // so the browser prevents submission. We remove it to test server-side validation.
        await page.locator('input#email').evaluate(el => el.removeAttribute('required'));
        await page.locator('input#password').evaluate(el => el.removeAttribute('required'));

        await page.getByRole('button', { name: 'Log in' }).click();

        await expect(page.getByText('The email field is required.')).toBeVisible();
    });

    test('should have a working remember me checkbox', async ({ page }) => {
        await page.goto('/login');

        const checkbox = page.locator('input#remember_me');
        await expect(checkbox).not.toBeChecked();

        await checkbox.check();
        await expect(checkbox).toBeChecked();

        await checkbox.uncheck();
        await expect(checkbox).not.toBeChecked();
    });
});
