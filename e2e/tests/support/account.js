export const E2E_ACCOUNT = 'e2e';
export const E2E_EMAIL = 'e2e@mossytrunk.local';
export const E2E_WORKSPACE = 'E2E';
export const LANE_SESSIONS = '.auth';

export async function signInWithAccount(page, account, claims = null) {
    await page.locator('input[name="username"]').fill(account);
    if (claims) await page.locator('textarea[name="claims"]').fill(JSON.stringify(claims));
    await page.getByRole('button', { name: 'Sign-in' }).click();
}

export async function signIn(page, account = E2E_ACCOUNT, claims = { email: E2E_EMAIL, mossytrunk_workspace: E2E_WORKSPACE }) {
    await page.goto('/login');
    await signInWithAccount(page, account, claims);
    await page.waitForURL(/\/dashboard$/);
}
