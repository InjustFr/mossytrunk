import { test as setup, expect } from '@playwright/test';
import { AUTH_STATE, E2E_EMAIL, E2E_PASSWORD, signIn } from './support/account.js';
import { latestEmailTo, passwordLinkFrom } from './support/mailpit.js';

setup('the invited e2e user chooses a password and signs in', async ({ page, request }) => {
    await page.goto(await passwordLinkFrom(await latestEmailTo(request, E2E_EMAIL)));
    await expect(page.getByRole('heading', { name: 'Bienvenue' })).toBeVisible();
    await page.getByLabel('Mot de passe').fill(E2E_PASSWORD);
    await page.getByLabel('Confirmation').fill(E2E_PASSWORD);
    await page.getByRole('button', { name: 'Enregistrer le mot de passe' }).click();
    await expect(page.getByText('Mot de passe enregistré')).toBeVisible();

    await signIn(page);
    await expect(page).toHaveURL(/\/tableau-de-bord$/);

    await page.context().storageState({ path: AUTH_STATE });
});
