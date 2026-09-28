const formatter = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });

/** Amounts travel as integer cents between API and UI. */
export function formatCents(cents) {
    return formatter.format((cents ?? 0) / 100);
}

export function eurosToCents(value) {
    const normalized = String(value ?? '').replace(',', '.').trim();
    if (normalized === '') {
        return null;
    }
    const number = Number(normalized);
    return Number.isFinite(number) ? Math.round(number * 100) : null;
}

export function centsToEuros(cents) {
    return ((cents ?? 0) / 100).toFixed(2).replace('.', ',');
}

export function useMoney() {
    return { formatCents, eurosToCents, centsToEuros };
}
