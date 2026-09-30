import { ref, watch } from 'vue';

const LIST_URLS_KEY = 'mossytrunk.list-urls';

function rememberedListUrls() {
    try {
        return JSON.parse(window.sessionStorage.getItem(LIST_URLS_KEY) ?? '{}');
    } catch {
        return {};
    }
}

function rememberListUrl(url) {
    try {
        window.sessionStorage.setItem(LIST_URLS_KEY, JSON.stringify({ ...rememberedListUrls(), [url.pathname]: `${url.pathname}${url.search}` }));
    } catch {
        return;
    }
}

export function listUrl(path) {
    return rememberedListUrls()[path] ?? path;
}

function queryState(name, fallback, parse, format) {
    const values = new URLSearchParams(window.location.search).getAll(name);
    const state = ref(values.length === 0 ? fallback : parse(values));
    rememberListUrl(new URL(window.location.href));

    watch(state, (value) => {
        const url = new URL(window.location.href);
        url.searchParams.delete(name);
        for (const formatted of format(value)) {
            url.searchParams.append(name, formatted);
        }
        window.history.replaceState(window.history.state, '', url);
        rememberListUrl(url);
    }, { deep: true });

    return state;
}

export const queryText = (name, fallback = '') => queryState(name, fallback, (values) => values[0], (value) => (value === fallback || value === '' ? [] : [value]));

export const queryFlag = (name, on) => queryState(name, false, (values) => values[0] === on, (value) => (value ? [on] : []));

export const queryList = (name) => queryState(name, [], (values) => values, (value) => value);
