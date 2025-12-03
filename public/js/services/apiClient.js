// simple API client wrapper for the frontend
// Usage: import { request } from './apiClient.js'
// const resp = await request('/reservations', { method: 'GET', params: { page:1 } })

function buildUrl(path, params, baseOverride) {
    const base = (typeof baseOverride !== 'undefined') ? (baseOverride || '') : (window.API_BASE || '');
    const url = new URL(base + path, window.location.origin);
    if (params) {
        Object.keys(params).forEach(k => {
            const v = params[k];
            if (v === undefined || v === null || v === '') return;
            url.searchParams.append(k, String(v));
        });
    }
    return url.toString();
}

export async function request(path, { method = 'GET', body = null, params = null, headers = {} } = {}, _retryWithIndexPhp = false) {
    const url = buildUrl(path, params);
    const opts = { method, headers: { Accept: 'application/json', ...headers } };
    if (body != null) {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(body);
    }

    const doFetch = async (baseOverride) => {
        const u = buildUrl(path, params, baseOverride);
        const res = await fetch(u, opts);
        const text = await res.text();
        let data = null;
        try {
            data = text ? JSON.parse(text) : null;
        } catch (e) {
            // If server returned HTML (like a 404 page), include raw text for debugging
            const err = { status: res.status, message: 'Invalid JSON response', raw: text };
            throw err;
        }
        if (!res.ok) {
            const err = { status: res.status, data, raw: text };
            throw err;
        }
        return data;
    };

    try {
        return await doFetch();
    } catch (err) {
        // If first attempt returned 404 Not Found and we haven't tried index.php yet,
        // attempt a second request inserting /index.php after the detected API_BASE.
        if (err && err.status === 404 && !_retryWithIndexPhp) {
            try {
                const base = window.API_BASE || '';
                // avoid duplicate index.php
                const candidateBase = base.includes('/index.php') ? base : (base.replace(/\/$/, '') + '/index.php');
                return await request(path, { method, body, params, headers }, true).catch(async () => {
                    // fallback: call doFetch with explicit baseOverride
                    return await doFetch(candidateBase);
                });
            } catch (err2) {
                throw err2;
            }
        }
        throw err;
    }
}

export default { request };
