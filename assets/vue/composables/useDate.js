import { perLocale } from '../i18n/locale.js';

const dateFormatter = perLocale((locale) => new Intl.DateTimeFormat(locale, { dateStyle: 'medium' }));
const dateTimeFormatter = perLocale((locale) => new Intl.DateTimeFormat(locale, { dateStyle: 'medium', timeStyle: 'short' }));

export function formatDate(iso) {
    return iso ? dateFormatter().format(new Date(iso)) : '';
}

const dayFormatter = perLocale((locale) => new Intl.DateTimeFormat(locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Europe/Paris' }));
const timeFormatter = perLocale((locale) => new Intl.DateTimeFormat(locale, { timeStyle: 'short', timeZone: 'Europe/Paris' }));

export function formatDay(iso) {
    const day = dayFormatter().format(new Date(iso));
    return day.charAt(0).toUpperCase() + day.slice(1);
}

export function formatTime(iso) {
    return timeFormatter().format(new Date(iso));
}

const numericDayFormatter = perLocale((locale) => new Intl.DateTimeFormat(locale, { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'Europe/Paris' }));

export function formatNumericDay(iso) {
    return numericDayFormatter().format(new Date(iso));
}

export function formatDateTime(iso) {
    return iso ? dateTimeFormatter().format(new Date(iso)) : '';
}

const relativeFormatter = perLocale((locale) => new Intl.RelativeTimeFormat(locale, { numeric: 'auto' }));

export function fromToday(isoDate) {
    const today = new Date();
    const todayUtc = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate());
    const [year, month, day] = isoDate.split('-').map(Number);
    const days = Math.round((Date.UTC(year, month - 1, day) - todayUtc) / 86_400_000);
    return relativeFormatter().format(days, 'day');
}

export function nowForInput() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
}

export function localDay(date = new Date()) {
    return [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')].join('-');
}
