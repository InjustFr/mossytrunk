export const LANGUAGES = [
    { value: 'fr', label: 'Français', intl: 'fr-FR' },
    { value: 'en', label: 'English', intl: 'en-GB' },
];

const DEFAULT_LANGUAGE = 'en';

export function currentLanguage() {
    const language = document.body?.dataset.locale;
    return LANGUAGES.some((known) => known.value === language) ? language : DEFAULT_LANGUAGE;
}

export function intlLocale() {
    return LANGUAGES.find((known) => known.value === currentLanguage()).intl;
}

export function perLocale(build) {
    const cache = new Map();
    return () => {
        const locale = intlLocale();
        if (!cache.has(locale)) cache.set(locale, build(locale));
        return cache.get(locale);
    };
}
