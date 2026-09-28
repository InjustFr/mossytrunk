export async function choose(page, combobox, option) {
    await combobox.click();
    await page.getByRole('option', { name: option, exact: true }).click();
}
