import { defineConfig, devices } from '@playwright/test';

const testDatabase = 'DB_DATABASE=castit_test';
const migration = `${testDatabase} php artisan migrate:fresh && ${testDatabase} php artisan db:seed --class=PlaywrightTestSeeder`;

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
        command: `${migration} && ${testDatabase} php artisan serve --port=8100`,
        url: 'http://localhost:8100',
        reuseExistingServer: !process.env.CI,
        timeout: 120000,
    },
});
