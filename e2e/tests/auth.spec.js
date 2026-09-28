import { test, expect } from '@playwright/test';
import { E2E_EMAIL, E2E_PASSWORD, RESET_EMAIL, signIn } from './support/account.js';
import { clearEmailsTo, latestEmailTo, passwordLinkFrom } from './support/mailpit.js';

test.describe('signed out', () => {
    test.use({ storageState: { cookies: [], origins: [] } });

    test('pages require signing in', async ({ page }) => {
        await page.goto('/produits');

        await expect(page).toHaveURL(/\/connexion$/);
        await expect(page.getByRole('heading', { name: 'Connexion' })).toBeVisible();
    });

    test('a wrong password is refused', async ({ page }) => {
        await signIn(page, E2E_EMAIL, 'not the password');

        await expect(page.getByRole('alert')).toHaveText('Email ou mot de passe incorrect.');
    });

    test('forgot password sends a link that sets a new password', async ({ page, request }) => {
        await clearEmailsTo(request, RESET_EMAIL);
        await page.goto('/connexion');
        await page.getByRole('link', { name: 'Mot de passe oublié ?' }).click();
        await expect(page.getByRole('heading', { name: 'Mot de passe oublié' })).toBeVisible();
        await page.getByLabel('Email').fill(RESET_EMAIL);
        await page.getByRole('button', { name: 'Envoyer le lien' }).click();
        await expect(page.getByRole('status')).toContainText(`Si un compte existe pour ${RESET_EMAIL}`);

        await page.goto(await passwordLinkFrom(await latestEmailTo(request, RESET_EMAIL)));
        await expect(page.getByRole('heading', { name: 'Nouveau mot de passe' })).toBeVisible();
        await page.getByLabel('Mot de passe').fill(E2E_PASSWORD);
        await page.getByLabel('Confirmation').fill(E2E_PASSWORD);
        await page.getByRole('button', { name: 'Enregistrer le mot de passe' }).click();

        await signIn(page, RESET_EMAIL);
        await expect(page).toHaveURL(/\/tableau-de-bord$/);
    });
});

test('signing out returns to the login page', async ({ browser }) => {
    const context = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const page = await context.newPage();
    await signIn(page);

    await page.getByRole('button', { name: 'Se déconnecter' }).click();

    await expect(page).toHaveURL(/\/connexion$/);
    await page.goto('/commandes');
    await expect(page).toHaveURL(/\/connexion$/);
    await context.close();
});

test('the sidebar shows the signed-in account', async ({ page }) => {
    await page.goto('/tableau-de-bord');

    await expect(page.getByText(E2E_EMAIL)).toBeVisible();
    await expect(page.getByText('E2E', { exact: true })).toBeVisible();
});
