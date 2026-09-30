import { createI18n } from 'vue-i18n';
import { currentLanguage } from './locale.js';

const files = import.meta.webpackContext('./', { recursive: true, regExp: /^\.\/[a-z]{2}\/[a-z-]+\.json$/ });

const messages = {};
for (const path of files.keys()) {
    const [, language, namespace] = path.match(/^\.\/([a-z]{2})\/([a-z-]+)\.json$/);
    messages[language] = { ...messages[language], [namespace]: files(path) };
}

const twoFormsCountingZeroAsOne = (choice, choicesLength) => {
    if (choicesLength === 3) return Math.min(choice, 2);
    return choice <= 1 ? 0 : 1;
};

export const i18n = createI18n({
    legacy: false,
    locale: currentLanguage(),
    fallbackLocale: 'en',
    messages,
    pluralRules: { fr: twoFormsCountingZeroAsOne },
});

export const t = (...args) => i18n.global.t(...args);

document.addEventListener('vue:before-mount', (event) => {
    const language = currentLanguage();
    i18n.global.locale.value = language;
    document.documentElement.lang = language;
    event.detail.app.use(i18n);
});
