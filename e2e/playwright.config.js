import { defineConfig, devices } from '@playwright/test';
import { AUTH_STATE } from './tests/support/account.js';

const WORKSPACE_WIDE = /(settings|sumup|etsy|language|references|channels)\.spec\.js/;

export default defineConfig({
    testDir: './tests',
    fullyParallel: false,
    workers: process.env.CI ? 4 : 2,
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
        { name: 'chromium', testIgnore: WORKSPACE_WIDE, use: { ...devices['Desktop Chrome'], storageState: AUTH_STATE }, dependencies: ['setup'] },
        { name: 'workspace-wide', testMatch: WORKSPACE_WIDE, workers: 1, use: { ...devices['Desktop Chrome'], storageState: AUTH_STATE }, dependencies: ['chromium'] },
    ],
});
