import fs from 'node:fs';
import { test as base, expect } from '@playwright/test';
import { E2E_EMAIL, E2E_PASSWORD, LANE_SESSIONS, signIn } from './account.js';
import { latestEmailTo, passwordLinkFrom } from './mailpit.js';

const FIRST_LANE_PORT = 8001;

async function acceptInvitation(page) {
    await page.goto(await passwordLinkFrom(await latestEmailTo(page.request, E2E_EMAIL)));
    await expect(page.getByRole('heading', { name: 'Bienvenue' })).toBeVisible();
    await page.getByLabel('Mot de passe').fill(E2E_PASSWORD);
    await page.getByLabel('Confirmation').fill(E2E_PASSWORD);
    await page.getByRole('button', { name: 'Enregistrer le mot de passe' }).click();
    await expect(page.getByText('Mot de passe enregistré')).toBeVisible();

    await signIn(page);
    await expect(page).toHaveURL(/\/dashboard$/);
}

export const test = base.extend({
    laneURL: [
        async ({}, use, workerInfo) => {
            await use(`${process.env.BASE_URL ?? 'http://localhost'}:${FIRST_LANE_PORT + workerInfo.parallelIndex}`);
        },
        { scope: 'worker' },
    ],
    laneSession: [
        async ({ browser, laneURL }, use, workerInfo) => {
            const session = `${LANE_SESSIONS}/lane-${workerInfo.parallelIndex + 1}.json`;
            if (!fs.existsSync(session)) {
                const page = await browser.newPage({ baseURL: laneURL, locale: workerInfo.project.use.locale, storageState: undefined });
                await acceptInvitation(page);
                await page.context().storageState({ path: session });
                expect((await page.request.post('/_e2e/baseline', { maxRedirects: 0 })).status()).toBe(204);
                await page.context().close();
            }
            await use(session);
        },
        { scope: 'worker' },
    ],
    baseURL: async ({ laneURL }, use) => use(laneURL),
    storageState: async ({ laneSession }, use) => use(laneSession),
    baselineDatabase: [
        async ({ playwright, laneURL, laneSession }, use) => {
            const api = await playwright.request.newContext({ baseURL: laneURL, storageState: laneSession });
            const response = await api.post('/_e2e/rollback', { maxRedirects: 0 });
            await api.dispose();
            if (response.status() !== 204) {
                throw new Error(`Rolling the database back answered ${response.status()}`);
            }
            await use();
        },
        { auto: true },
    ],
    request: async ({ playwright, baseURL, storageState, locale }, use) => {
        const request = await playwright.request.newContext({ baseURL, storageState, extraHTTPHeaders: { 'Accept-Language': locale } });
        await use(request);
        await request.dispose();
    },
});

export { expect };
