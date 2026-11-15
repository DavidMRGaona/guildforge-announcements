import { afterEach, describe, expect, it, vi } from 'vitest';
import { reportPopup } from '../../resources/js/utils/popupReports';

describe('reportPopup', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
        document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT';
    });

    it('posts the id alone, with the CSRF token, to the route of each notice', () => {
        document.cookie = 'XSRF-TOKEN=abc%3D';
        const fetchMock = vi.fn(() => Promise.resolve(new Response(null, { status: 204 })));
        vi.stubGlobal('fetch', fetchMock);

        reportPopup('0b1f', 'shown');
        reportPopup('0b1f', 'dismissed');

        const calls = fetchMock.mock.calls as unknown as [string, RequestInit][];
        expect(calls.map(([url]) => url)).toEqual([
            '/anuncios/ventanas/0b1f/mostrada',
            '/anuncios/ventanas/0b1f/cerrada',
        ]);

        const init = calls[0]?.[1];
        expect(init?.method).toBe('POST');
        expect(init?.body).toBeUndefined();
        expect(init?.credentials).toBe('same-origin');
        expect(init?.headers).toMatchObject({ 'X-XSRF-TOKEN': 'abc=', Accept: 'application/json' });
    });

    it('never throws, whatever goes wrong', async () => {
        vi.stubGlobal('fetch', () => Promise.reject(new Error('offline')));
        expect(() => reportPopup('a', 'shown')).not.toThrow();

        vi.stubGlobal('fetch', () => {
            throw new Error('no fetch');
        });
        expect(() => reportPopup('a', 'dismissed')).not.toThrow();

        await Promise.resolve();
    });
});
