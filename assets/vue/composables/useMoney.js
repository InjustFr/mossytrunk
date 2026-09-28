const formatter = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });

export function formatCents(cents) {
    return formatter.format((cents ?? 0) / 100);
}

export function useMoney() {
    return { formatCents };
}
