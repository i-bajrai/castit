import { defineConfig, devices } from '@playwright/test';

const testEnv = 'DB_DATABASE=castit_test APP_ENV=testing';
const migration = `${testEnv} php artisan migrate:fresh && ${testEnv} php artisan db:seed --class=PlaywrightTestSeeder`;

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: 'html',
    use: {
        baseURL: 'http://localhost:8100',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    webServer: {
        command: `${migration} && ${testEnv} php artisan serve --port=8100`,
        url: 'http://localhost:8100',
        reuseExistingServer: !process.env.CI,
        timeout: 120000,
    },
});
