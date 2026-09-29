import { test, expect } from '@playwright/test';

test('switch URSSAF periodicity and download the orders of a period as CSV', async ({ page }) => {
    await page.goto('/comptabilite');
    await expect(page.getByRole('heading', { name: 'Déclarations URSSAF' })).toBeVisible();

    await page.getByRole('button', { name: 'Chaque trimestre' }).click();
    await expect(page.getByTestId('toast').last()).toContainText('Déclarations trimestrielles.');
    await expect(page.getByRole('list', { name: "Périodes de l'année" }).getByRole('button')).toHaveCount(4);

    await page.getByRole('button', { name: 'Chaque mois' }).click();
    await expect(page.getByRole('list', { name: "Périodes de l'année" }).getByRole('button')).toHaveCount(12);

    await page.getByRole('button', { name: 'Trimestre dernier' }).click();
    const download = page.getByRole('link', { name: 'Télécharger le CSV' });
    const href = await download.getAttribute('href');
    expect(href).toMatch(/^\/api\/accounting\/orders\.csv\?from=\d{4}-\d{2}-01&to=\d{4}-\d{2}-\d{2}$/);

    const response = await page.request.get(href);
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('text/csv');
    expect(await response.text()).toContain('Référence;Date;Heure;Source;Événement');
});
