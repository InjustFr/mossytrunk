import { defineConfig, devices } from '@playwright/test';
import { AUTH_STATE } from './tests/support/account.js';

export default defineConfig({
    testDir: './tests',
    fullyParallel: false,
    workers: 1,
    reporter: [['list'], ['html', { open: 'never', outputFolder: 'playwright-report' }]],
    use: {
        baseURL: process.env.BASE_URL ?? 'http://localhost:8080',
        testIdAttribute: 'data-test',
        locale: 'fr-FR',
        timezoneId: 'Europe/Paris',
        trace: 'retain-on-failure',
    },
    projects: [
        { name: 'setup', testMatch: /auth\.setup\.js/ },
        { name: 'chromium', use: { ...devices['Desktop Chrome'], storageState: AUTH_STATE }, dependencies: ['setup'] },
    ],
});
