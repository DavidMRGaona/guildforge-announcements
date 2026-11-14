import { beforeEach, describe, expect, it } from 'vitest';
import {
    countPageView,
    forgetShown,
    isDismissed,
    markDismissed,
    markShown,
    wasShownThisVisit,
} from '../../resources/js/utils/popupMemory';

describe('popupMemory', () => {
    beforeEach(() => {
        window.localStorage.clear();
        window.sessionStorage.clear();
    });

    it('remembers a closed window in this browser', () => {
        expect(isDismissed('a')).toBe(false);
        markDismissed('a');
        markDismissed('a');

        expect(isDismissed('a')).toBe(true);
        expect(
            JSON.parse(window.localStorage.getItem('guildforge_dismissed_popups') ?? '[]')
        ).toEqual(['a']);
    });

    it('remembers an opened window for the visit, and can forget it', () => {
        markShown('a');
        expect(wasShownThisVisit('a')).toBe(true);
        expect(window.localStorage.length).toBe(0);

        forgetShown('a');
        expect(wasShownThisVisit('a')).toBe(false);
    });

    it('counts the pages of the visit, not the same page twice in a row', () => {
        expect(countPageView('/')).toBe(1);
        expect(countPageView('/')).toBe(1);
        expect(countPageView('/eventos')).toBe(2);
        expect(countPageView('/')).toBe(3);
    });

    it('survives storage that holds something else', () => {
        window.localStorage.setItem('guildforge_dismissed_popups', '{"no": "lista"}');
        window.sessionStorage.setItem('guildforge_popup_page_views', 'no es json');

        expect(isDismissed('a')).toBe(false);
        expect(countPageView('/')).toBe(1);
    });
});
