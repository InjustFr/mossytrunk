const formatter = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' });
const signedFormatter = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', signDisplay: 'exceptZero' });
const wholeFormatter = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });
const percentFormatter = new Intl.NumberFormat('fr-FR', { style: 'percent', maximumFractionDigits: 0 });

const withTrueMinus = (text) => text.replace('-', '−');

export function formatCents(cents) {
    return withTrueMinus(formatter.format((cents ?? 0) / 100));
}

export function formatWholeCents(cents) {
    return withTrueMinus(wholeFormatter.format((cents ?? 0) / 100));
}

export function formatSignedCents(cents) {
    return withTrueMinus(signedFormatter.format((cents ?? 0) / 100));
}

export function formatRatio(part, whole) {
    return whole ? withTrueMinus(percentFormatter.format(part / whole)) : '—';
}

export function useMoney() {
    return { formatCents, formatWholeCents, formatSignedCents, formatRatio };
}
