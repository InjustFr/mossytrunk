import { test, expect } from './support/test.js';
import { E2E_EMAIL, signIn, signInWithAccount } from './support/account.js';

test.describe('signed out', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('pages send to the mossyleaf account sign-in', async ({ page }) => {
        await page.goto('/products');

        await expect(page).toHaveURL(/\/authorize\?/);
        await signInWithAccount(page, 'e2e');
        await expect(page).toHaveURL(/\/products$/);
        await expect(page.getByText(E2E_EMAIL)).toBeVisible();
    });

    test('a new mossyleaf account joins the default workspace', async ({ page }) => {
        const account = `fern-${Date.now().toString(36)}${Math.random().toString(36).slice(2, 5)}`;

        await page.goto('/login');
        await signInWithAccount(page, account, { email: `${account}@mossyleaf.test` });

        await expect(page).toHaveURL(/\/dashboard$/);
        await expect(page.getByText(`${account}@mossyleaf.test`)).toBeVisible();
        await expect(page.getByText('E2E', { exact: true })).toBeVisible();
    });

    test('a failed sign-in explains why and offers to try again', async ({ page }) => {
        await page.goto('/login/check?state=forged&code=forged');

        await expect(page.getByRole('heading', { name: 'Connexion' })).toBeVisible();
        await expect(page.getByRole('alert')).toHaveText('Cette connexion a pris trop de temps ou a été ouverte dans un autre onglet, réessayez.');
        await page.getByRole('link', { name: 'Se connecter avec mossyleaf' }).click();
        await expect(page.locator('input[name="username"]')).toBeVisible();
    });
});

test('signing out also signs out of the mossyleaf account', async ({ browser }) => {
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const page = await context.newPage();
    await signIn(page);

    await page.getByRole('button', { name: 'Se déconnecter' }).click();

    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page).toHaveURL(/\/authorize\?/);
    await page.goto('/orders');
    await expect(page).toHaveURL(/\/authorize\?/);
    await context.close();
});

test('the sidebar shows the signed-in account', async ({ page }) => {
    await page.goto('/dashboard');

    await expect(page.getByText(E2E_EMAIL)).toBeVisible();
    await expect(page.getByText('E2E', { exact: true })).toBeVisible();
});

test('the settings link to the mossyleaf account', async ({ page }) => {
    await page.goto('/settings');

    await expect(page.getByRole('link', { name: 'Gérer mon compte mossyleaf' })).toHaveAttribute('href', /\/mossyleaf$/);
});
