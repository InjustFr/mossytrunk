import { expect } from '@playwright/test';

export async function closeToast(page, message) {
    const toast = page.getByTestId('toast').filter({ hasText: message });
    await toast.getByRole('button', { name: 'Fermer' }).click();
    await expect(toast).toHaveCount(0);
}
