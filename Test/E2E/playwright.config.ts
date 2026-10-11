import { defineConfig } from '@playwright/test';

export const BASE_URL = process.env.MAGENTO_URL ?? 'http://localhost:8080';
export const ADMIN_STATE = '.auth/admin.json';

export default defineConfig({
    testDir: './tests',
    // Magento runs on the PHP built-in server and all tests share one admin user: run serially.
    workers: 1,
    fullyParallel: false,
    retries: process.env.CI ? 1 : 0,
    timeout: 180_000,
    expect: { timeout: 30_000 },
    reporter: [['list'], ['html', { open: 'never', outputFolder: 'playwright-report' }]],
    use: {
        baseURL: BASE_URL,
        actionTimeout: 30_000,
        navigationTimeout: 120_000,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        viewport: { width: 1440, height: 900 },
    },
    projects: [
        { name: 'setup', testMatch: /global\.setup\.ts/ },
        {
            name: 'admin',
            testMatch: /admin\/.*\.spec\.ts/,
            dependencies: ['setup'],
            use: { storageState: ADMIN_STATE },
        },
        {
            name: 'storefront',
            testMatch: /storefront\/.*\.spec\.ts/,
            dependencies: ['setup'],
            use: { storageState: ADMIN_STATE },
        },
    ],
});
