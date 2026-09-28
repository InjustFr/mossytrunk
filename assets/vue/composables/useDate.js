const dateFormatter = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' });
const dateTimeFormatter = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' });

export function formatDate(iso) {
    return iso ? dateFormatter.format(new Date(iso)) : '';
}

export function formatDateTime(iso) {
    return iso ? dateTimeFormatter.format(new Date(iso)) : '';
}

const relativeFormatter = new Intl.RelativeTimeFormat('fr-FR', { numeric: 'auto' });

export function fromToday(isoDate) {
    const today = new Date();
    const todayUtc = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate());
    const [year, month, day] = isoDate.split('-').map(Number);
    const days = Math.round((Date.UTC(year, month - 1, day) - todayUtc) / 86_400_000);
    return relativeFormatter.format(days, 'day');
}

export function nowForInput() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
}
