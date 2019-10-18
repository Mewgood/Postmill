import { isObject } from 'lodash-es';

export function fetch(url = '', options = {}) {
    if (isObject(url)) {
        options = url;
        url = options.url;
    }

    options = { ...options };
    options.credentials = options.credentials || 'same-origin';
    options.redirect = options.redirect || 'error';

    return window.fetch(url, options);
}

export function ok(response) {
    if (!response.ok) {
        const e = new Error(response.statusText);
        e.response = response;

        throw e;
    }

    return response;
}
