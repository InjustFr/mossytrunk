const dateFormatter = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' });
const dateTimeFormatter = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' });

export function formatDate(iso) {
    return iso ? dateFormatter.format(new Date(iso)) : '';
}

export function formatDateTime(iso) {
    return iso ? dateTimeFormatter.format(new Date(iso)) : '';
}

/** Value for <input type="datetime-local"> in local time. */
export function nowForInput() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
}
