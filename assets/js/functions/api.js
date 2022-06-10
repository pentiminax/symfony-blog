/**
 * @param {RequestInfo} url
 * @param {RequestInit} init
 * @returns {Promise<Object|null>}
 */
export async function jsonFetch(url, init = {}) {
    init = {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
        ...init
    }

    const response = await fetch(url, init);

    if (!response.ok) {
        return null;
    }

    return await response.json();
}