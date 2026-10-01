import { begin, end } from '../../progress-bar.js';
import { visit } from './useNavigation.js';
import { t } from '../i18n/index.js';

export class ApiError extends Error {
    constructor(message, status, violations = []) {
        super(message);
        this.status = status;
        this.violations = violations;
    }

    /** Field path => message, for inline form errors. */
    get fieldErrors() {
        return Object.fromEntries(this.violations.map((v) => [v.propertyPath, v.title ?? v.message]));
    }
}

const REFRESH_HEADER = 'X-Refresh';
const REFRESHED_HEADER = 'X-Refreshed';

const responses = new Map();
const fetching = new Map();
const loadedOnPage = new Set();
let generation = 0;
let preloaded = new Map();

document.addEventListener('turbo:visit', () => {
    loadedOnPage.clear();
    preloaded = new Map();
});

function takePreloaded(url) {
    const element = document.getElementById('app-preload');
    if (element) {
        preloaded = new Map(Object.entries(JSON.parse(element.textContent)));
        element.remove();
    }
    if (!preloaded.has(url)) return { found: false };
    const data = preloaded.get(url);
    preloaded.delete(url);
    return { found: true, data };
}

async function request(method, url, body, refresh = []) {
    begin();
    try {
        return await send(method, url, body, refresh);
    } finally {
        end();
    }
}

async function change(method, url, body) {
    let refreshed = {};
    try {
        const { data, refreshed: received = {} } = await request(method, url, body, [...loadedOnPage]);
        refreshed = received;
        return data;
    } finally {
        generation += 1;
        responses.clear();
        preloaded = new Map(Object.entries(refreshed));
        preloaded.forEach((data, refreshedUrl) => responses.set(refreshedUrl, data));
    }
}

function fresh(url, quietly) {
    if (!fetching.has(url)) {
        const startedAt = generation;
        const pending = (quietly ? send('GET', url) : request('GET', url))
            .then((data) => {
                if (startedAt === generation) responses.set(url, data);
                return data;
            })
            .finally(() => fetching.delete(url));
        fetching.set(url, pending);
    }
    return fetching.get(url);
}

async function load(url, target) {
    loadedOnPage.add(url);
    const preload = takePreloaded(url);
    if (preload.found) {
        responses.set(url, preload.data);
        target.value = preload.data;
        return preload.data;
    }
    const cached = responses.has(url);
    if (cached) target.value = responses.get(url);
    target.value = await fresh(url, cached);
    return target.value;
}

async function send(method, url, body, refresh = []) {
    const json = body !== undefined && !(body instanceof FormData);
    const response = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            ...(json ? { 'Content-Type': 'application/json' } : {}),
            ...(refresh.length > 0 ? { [REFRESH_HEADER]: JSON.stringify(refresh) } : {}),
        },
        body: json ? JSON.stringify(body) : body,
    });

    if (response.status === 401) {
        visit('/login');
    }

    const payload = response.status === 204 ? null : await response.json().catch(() => null);

    if (!response.ok) {
        throw new ApiError(
            payload?.detail ?? payload?.title ?? t('common.error'),
            response.status,
            payload?.violations ?? [],
        );
    }

    if (method === 'GET') {
        return payload;
    }

    return response.headers.has(REFRESHED_HEADER) ? payload : { data: payload };
}

export function useApi() {
    return {
        get: (url) => request('GET', url),
        load,
        peek: (url) => send('GET', url),
        query: async (url, body = {}) => (await request('POST', url, body)).data,
        post: (url, body = {}) => change('POST', url, body),
        put: (url, body = {}) => change('PUT', url, body),
        patch: (url, body = {}) => change('PATCH', url, body),
        del: (url) => change('DELETE', url),
    };
}
