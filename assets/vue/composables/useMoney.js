const formatter = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });
const signedFormatter = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', signDisplay: 'exceptZero' });
const percentFormatter = new Intl.NumberFormat('fr-FR', { style: 'percent', maximumFractionDigits: 0 });

const withTrueMinus = (text) => text.replace('-', '−');

export function formatCents(cents) {
    return withTrueMinus(formatter.format((cents ?? 0) / 100));
}

export function formatSignedCents(cents) {
    return withTrueMinus(signedFormatter.format((cents ?? 0) / 100));
}

export function formatRatio(part, whole) {
    return whole ? withTrueMinus(percentFormatter.format(part / whole)) : '—';
}

export function useMoney() {
    return { formatCents, formatSignedCents, formatRatio };
}
