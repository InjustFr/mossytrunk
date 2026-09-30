import { perLocale } from '../i18n/locale.js';

const formatter = perLocale((locale) => new Intl.NumberFormat(locale, { style: 'currency', currency: 'EUR' }));
const signedFormatter = perLocale((locale) => new Intl.NumberFormat(locale, { style: 'currency', currency: 'EUR', signDisplay: 'exceptZero' }));
const wholeFormatter = perLocale((locale) => new Intl.NumberFormat(locale, { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }));
const percentFormatter = perLocale((locale) => new Intl.NumberFormat(locale, { style: 'percent', maximumFractionDigits: 0 }));

const withTrueMinus = (text) => text.replace('-', '−');

export function formatCents(cents) {
    return withTrueMinus(formatter().format((cents ?? 0) / 100));
}

export function formatWholeCents(cents) {
    return withTrueMinus(wholeFormatter().format((cents ?? 0) / 100));
}

export function formatSignedCents(cents) {
    return withTrueMinus(signedFormatter().format((cents ?? 0) / 100));
}

export function formatRatio(part, whole) {
    return whole ? withTrueMinus(percentFormatter().format(part / whole)) : '—';
}

export function useMoney() {
    return { formatCents, formatWholeCents, formatSignedCents, formatRatio };
}
