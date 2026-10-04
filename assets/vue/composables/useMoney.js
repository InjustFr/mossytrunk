import { perLocale } from '../i18n/locale.js';

const formatter = perLocale((locale) => new Intl.NumberFormat(locale, { style: 'currency', currency: 'EUR' }));
const dollarFormatter = perLocale((locale) => new Intl.NumberFormat(locale, { style: 'currency', currency: 'USD' }));
const signedFormatter = perLocale((locale) => new Intl.NumberFormat(locale, { style: 'currency', currency: 'EUR', signDisplay: 'exceptZero' }));
const wholeFormatter = perLocale((locale) => new Intl.NumberFormat(locale, { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }));
const percentFormatters = new Map();

const withTrueMinus = (text) => text.replace('-', '−');

export function formatCents(cents, currency = 'EUR') {
    return withTrueMinus((currency === 'USD' ? dollarFormatter() : formatter()).format((cents ?? 0) / 100));
}

export function formatWholeCents(cents) {
    return withTrueMinus(wholeFormatter().format((cents ?? 0) / 100));
}

export function formatSignedCents(cents) {
    return withTrueMinus(signedFormatter().format((cents ?? 0) / 100));
}

function percentFormatter(digits) {
    if (!percentFormatters.has(digits)) {
        percentFormatters.set(digits, perLocale((locale) => new Intl.NumberFormat(locale, { style: 'percent', maximumFractionDigits: digits })));
    }
    return percentFormatters.get(digits)();
}

export function formatPercent(fraction, digits = 0) {
    return withTrueMinus(percentFormatter(digits).format(fraction));
}

export function formatRatio(part, whole, digits = 0) {
    return whole ? formatPercent(part / whole, digits) : '—';
}

export function useMoney() {
    return { formatCents, formatWholeCents, formatSignedCents, formatPercent, formatRatio };
}
