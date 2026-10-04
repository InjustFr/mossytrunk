export const E2E_EMAIL = 'e2e@mossytrunk.local';
export const RESET_EMAIL = 'e2e-reset@mossytrunk.local';
export const E2E_PASSWORD = 'mossytrunk-e2e-password';
export const LANE_SESSIONS = '.auth';

export async function signIn(page, email = E2E_EMAIL, password = E2E_PASSWORD) {
    await page.goto('/login');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Mot de passe').fill(password);
    await page.getByRole('button', { name: 'Se connecter' }).click();
}
