import { test, expect } from '@playwright/test';

const TEST_USER_EMAIL = 'playwright@test.com';
const TEST_USER_PASSWORD = 'password';

test.describe('Login Page', () => {
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
