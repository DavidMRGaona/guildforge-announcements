import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
    countPageView,
    forgetShown,
    isDue,
    markDismissed,
    markShown,
    readRecord,
    resetPageMemory,
    setVolatileMemory,
    wasShownThisVisit,
} from '../../resources/js/utils/popupMemory';

const DAY = 86_400_000;
const NOW = Date.parse('2026-11-15T10:00:00Z');

const once = { frequency: 'once', frequency_days: null } as const;
const session = { frequency: 'session', frequency_days: null } as const;
const days = (count: number): { frequency: 'days'; frequency_days: number } => ({
    frequency: 'days',
    frequency_days: count,
});

/** A new visit in the same browser: its session storage is empty again */
function newVisit(): void {
    window.sessionStorage.clear();
    resetPageMemory();
}

describe('popupMemory', () => {
    beforeEach(() => {
        window.localStorage.clear();
        window.sessionStorage.clear();
        resetPageMemory();
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('frequency', () => {
        it('lets a window nobody saw open, whatever its frequency', () => {
            for (const popup of [once, session, days(7)]) {
                expect(isDue('a', popup, NOW)).toBe(true);
            }
        });

        it('never opens a window «once» again, also in a later visit', () => {
            markShown('a', NOW - 400 * DAY);
            newVisit();

            expect(isDue('a', once, NOW)).toBe(false);
        });

        it('counts closing as seeing it', () => {
            markDismissed('a', NOW - DAY);
            newVisit();

            expect(isDue('a', once, NOW)).toBe(false);
            expect(readRecord('a')).toEqual({ shown: NOW - DAY, dismissed: NOW - DAY });
        });

        it('opens a window «per session» in a new visit but not in the same', () => {
            markShown('a', NOW - 60_000);
            expect(isDue('a', session, NOW)).toBe(false);

            newVisit();
            expect(isDue('a', session, NOW)).toBe(true);
        });

        it('opens a window «every N days» when they have passed since it was shown', () => {
            markShown('a', NOW - 7 * DAY + 1);
            newVisit();
            expect(isDue('a', days(7), NOW)).toBe(false);

            expect(isDue('a', days(7), NOW + 1)).toBe(true);
            expect(isDue('a', days(7), NOW + 30 * DAY)).toBe(true);
        });

        it('does not move the days forward when the window is closed', () => {
            markShown('a', NOW - 7 * DAY);
            markDismissed('a', NOW - 5 * DAY);
            newVisit();

            expect(isDue('a', days(7), NOW)).toBe(true);
        });

        it('lets a window opened in this visit open once at most, whatever its frequency', () => {
            markShown('a', NOW - 30 * DAY);

            expect(wasShownThisVisit('a')).toBe(true);
            expect(isDue('a', days(1), NOW)).toBe(false);
        });

        it('does not trust a note from the future', () => {
            window.localStorage.setItem(
                'guildforge_popup_views',
                JSON.stringify({ a: { shown: NOW + 100 * DAY, dismissed: null } })
            );

            expect(isDue('a', days(7), NOW)).toBe(true);
        });
    });

    describe('what it keeps', () => {
        it('keeps a time stamp per window in the local storage', () => {
            const recent = Date.now();
            markShown('a', recent - 3000);
            markShown('b', recent - 2000);
            markDismissed('b', recent - 1000);

            expect(
                JSON.parse(window.localStorage.getItem('guildforge_popup_views') ?? '{}')
            ).toEqual({
                a: { shown: recent - 3000, dismissed: null },
                b: { shown: recent - 2000, dismissed: recent - 1000 },
            });
            expect(
                JSON.parse(window.sessionStorage.getItem('guildforge_shown_popups') ?? '[]')
            ).toEqual(['a', 'b']);
        });

        it('forgets a window opened by mistake, giving back what it knew before', () => {
            markDismissed('a', NOW - 10 * DAY);
            newVisit();

            const before = markShown('a', NOW);
            expect(wasShownThisVisit('a')).toBe(true);
            forgetShown('a', before);

            expect(wasShownThisVisit('a')).toBe(false);
            expect(readRecord('a')).toEqual({ shown: NOW - 10 * DAY, dismissed: NOW - 10 * DAY });

            // A window never seen before leaves nothing behind
            const first = markShown('b', NOW);
            forgetShown('b', first);
            expect(readRecord('b')).toEqual({ shown: null, dismissed: null });
        });

        it('drops the notes of windows nobody has seen for over a year', () => {
            const old = Date.now() - 500 * DAY;
            window.localStorage.setItem(
                'guildforge_popup_views',
                JSON.stringify({ vieja: { shown: old, dismissed: null } })
            );

            markShown('nueva');

            expect(
                Object.keys(
                    JSON.parse(window.localStorage.getItem('guildforge_popup_views') ?? '{}')
                )
            ).toEqual(['nueva']);
        });

        it('survives storage that holds something else', () => {
            window.localStorage.setItem('guildforge_popup_views', '["no", "un", "objeto"]');
            expect(isDue('a', once, NOW)).toBe(true);

            window.localStorage.setItem('guildforge_popup_views', 'no es json');
            expect(isDue('a', once, NOW)).toBe(true);

            window.localStorage.setItem(
                'guildforge_popup_views',
                JSON.stringify({ a: 'texto', b: { shown: 'ayer' }, c: { shown: 5 } })
            );
            expect(readRecord('a')).toEqual({ shown: null, dismissed: null });
            expect(readRecord('b')).toEqual({ shown: null, dismissed: null });
            expect(readRecord('c')).toEqual({ shown: 5, dismissed: null });

            window.sessionStorage.setItem('guildforge_shown_popups', '{"no": "lista"}');
            window.sessionStorage.setItem('guildforge_popup_page_views', 'no es json');
            expect(wasShownThisVisit('a')).toBe(false);
            expect(countPageView('/')).toBe(1);
        });
    });

    describe('storage that fails', () => {
        function breakStorage(): void {
            vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
                throw new DOMException('full', 'QuotaExceededError');
            });
            vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
                throw new DOMException('blocked', 'SecurityError');
            });
        }

        it('keeps what it cannot write for the page', () => {
            breakStorage();

            expect(isDue('a', once, NOW)).toBe(true);
            markShown('a', NOW);

            expect(wasShownThisVisit('a')).toBe(true);
            expect(isDue('a', once, NOW)).toBe(false);

            markDismissed('b', NOW);
            expect(isDue('b', once, NOW)).toBe(false);
        });

        it('forgets what it kept for the page too', () => {
            breakStorage();

            const before = markShown('a', NOW);
            forgetShown('a', before);

            expect(wasShownThisVisit('a')).toBe(false);
            expect(isDue('a', once, NOW)).toBe(true);
        });

        it('keeps counting the pages of the page load', () => {
            breakStorage();

            expect(countPageView('/')).toBe(1);
            expect(countPageView('/')).toBe(1);
            expect(countPageView('/eventos')).toBe(2);
        });

        it('copes with the storage objects themselves throwing', () => {
            vi.spyOn(window, 'localStorage', 'get').mockImplementation(() => {
                throw new DOMException('blocked', 'SecurityError');
            });
            vi.spyOn(window, 'sessionStorage', 'get').mockImplementation(() => {
                throw new DOMException('blocked', 'SecurityError');
            });

            expect(() => markShown('a', NOW)).not.toThrow();
            expect(isDue('a', once, NOW)).toBe(false);
        });
    });

    describe('left alone (an impersonation)', () => {
        it('neither reads nor writes the browser’s storage', () => {
            window.localStorage.setItem(
                'guildforge_popup_views',
                JSON.stringify({ a: { shown: NOW, dismissed: NOW } })
            );
            const before = window.localStorage.getItem('guildforge_popup_views');
            setVolatileMemory(true);

            // What the administrator saw does not count for the person they look as
            expect(isDue('a', once, NOW)).toBe(true);

            markShown('a', NOW);
            markDismissed('b', NOW);

            expect(window.localStorage.getItem('guildforge_popup_views')).toBe(before);
            expect(window.sessionStorage.length).toBe(0);
            // …but the page still opens each window once
            expect(isDue('a', once, NOW)).toBe(false);
            expect(isDue('b', once, NOW)).toBe(false);
            expect(countPageView('/')).toBe(1);
            expect(countPageView('/eventos')).toBe(2);
            expect(window.sessionStorage.length).toBe(0);
        });
    });

    it('gives the administrator back their own windows when an impersonation ends', () => {
        setVolatileMemory(true);
        markShown('a', NOW);
        markDismissed('b', NOW);
        expect(countPageView('/')).toBe(1);
        expect(isDue('a', once, NOW)).toBe(false);

        setVolatileMemory(false);

        // Nothing of what the person they looked as saw is left
        expect(wasShownThisVisit('a')).toBe(false);
        expect(isDue('a', once, NOW)).toBe(true);
        expect(isDue('b', once, NOW)).toBe(true);
        expect(countPageView('/eventos')).toBe(1);
    });

    it('counts the pages of the visit, not the same page twice in a row', () => {
        expect(countPageView('/')).toBe(1);
        expect(countPageView('/')).toBe(1);
        expect(countPageView('/eventos')).toBe(2);
        expect(countPageView('/')).toBe(3);
    });
});
