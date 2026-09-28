export class ApiError extends Error {
    constructor(message, status, violations = []) {
        super(message);
        this.status = status;
        this.violations = violations;
    }

    /** Field path => message, for inline form errors. */
    get fieldErrors() {
        return Object.fromEntries(this.violations.map((v) => [v.propertyPath, v.message]));
    }
}

async function request(method, url, body) {
    const response = await fetch(url, {
        method,
        headers: { Accept: 'application/json', ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}) },
        body: body !== undefined ? JSON.stringify(body) : undefined,
    });

    if (response.status === 204) {
        return null;
    }

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        throw new ApiError(
            payload?.detail ?? payload?.title ?? 'Une erreur est survenue.',
            response.status,
            payload?.violations ?? [],
        );
    }

    return payload;
}

export function useApi() {
    return {
        get: (url) => request('GET', url),
        post: (url, body = {}) => request('POST', url, body),
        put: (url, body = {}) => request('PUT', url, body),
        patch: (url, body = {}) => request('PATCH', url, body),
        del: (url) => request('DELETE', url),
    };
}
