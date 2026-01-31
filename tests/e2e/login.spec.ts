import { test, expect } from '@playwright/test';

const TEST_USER_EMAIL = 'playwright@test.com';
const TEST_USER_PASSWORD = 'password';

test.describe('Login Page', () => {
    test('should display the login form', async ({ page }) => {
        await page.goto('/login');

        await expect(page.getByTestId('email-input')).toBeVisible();
        await expect(page.getByTestId('password-input')).toBeVisible();
        await expect(page.getByTestId('remember-me-checkbox')).toBeVisible();
        await expect(page.getByTestId('login-button')).toBeVisible();
        await expect(page.getByText('Forgot your password?')).toBeVisible();
    });

    test('should login successfully with valid credentials', async ({ page }) => {
        await page.goto('/login');

        await page.getByTestId('email-input').fill(TEST_USER_EMAIL);
        await page.getByTestId('password-input').fill(TEST_USER_PASSWORD);
        await page.getByTestId('login-button').click();

        await page.waitForURL('**/dashboard');
        await expect(page).toHaveURL(/dashboard/);
    });

    test('should show error with invalid credentials', async ({ page }) => {
        await page.goto('/login');

        await page.getByTestId('email-input').fill(TEST_USER_EMAIL);
        await page.getByTestId('password-input').fill('wrong-password');
        await page.getByTestId('login-button').click();

        await expect(page.getByText('These credentials do not match our records')).toBeVisible();
    });

    test('should show validation errors for empty form submission', async ({ page }) => {
        await page.goto('/login');

        // Remove required attributes to bypass browser validation
        await page.getByTestId('email-input').evaluate(el => el.removeAttribute('required'));
        await page.getByTestId('password-input').evaluate(el => el.removeAttribute('required'));

        await page.getByTestId('login-button').click();

        await expect(page.getByText('The email field is required.')).toBeVisible();
    });

    test('should have a working remember me checkbox', async ({ page }) => {
        await page.goto('/login');

        const checkbox = page.getByTestId('remember-me-checkbox');
        await expect(checkbox).not.toBeChecked();

        await checkbox.check();
        await expect(checkbox).toBeChecked();

        await checkbox.uncheck();
        await expect(checkbox).not.toBeChecked();
    });
});
