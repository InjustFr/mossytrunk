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

const responses = new Map();
const fetching = new Map();
let generation = 0;
let preloaded = new Map();

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

async function request(method, url, body) {
    begin();
    try {
        return await send(method, url, body);
    } finally {
        end();
    }
}

async function change(method, url, body) {
    try {
        return await request(method, url, body);
    } finally {
        generation += 1;
        responses.clear();
        preloaded.clear();
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

async function send(method, url, body) {
    const response = await fetch(url, {
        method,
        headers: { Accept: 'application/json', ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}) },
        body: body !== undefined ? JSON.stringify(body) : undefined,
    });

    if (response.status === 204) {
        return null;
    }

    if (response.status === 401) {
        visit('/login');
    }

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        throw new ApiError(
            payload?.detail ?? payload?.title ?? t('common.error'),
            response.status,
            payload?.violations ?? [],
        );
    }

    return payload;
}

export function useApi() {
    return {
        get: (url) => request('GET', url),
        load,
        peek: (url) => send('GET', url),
        post: (url, body = {}) => change('POST', url, body),
        put: (url, body = {}) => change('PUT', url, body),
        patch: (url, body = {}) => change('PATCH', url, body),
        del: (url) => change('DELETE', url),
    };
}
