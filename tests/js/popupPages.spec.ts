import { describe, expect, it } from 'vitest';
import type { AnnouncementPopup } from '../../resources/js/types/announcement';
import {
    fitsDevice,
    fitsPage,
    isExcludedPage,
    matchesPathPattern,
    pathOf,
} from '../../resources/js/utils/popupPages';

function popup(overrides: Partial<AnnouncementPopup> = {}): AnnouncementPopup {
    return {
        shape: 'centered',
        image_public_id: null,
        image_alt: null,
        action: null,
        triggers: { delay_seconds: 0, scroll_percent: null, exit_intent: false, page_views: null },
        pages: 'all',
        paths: [],
        device: 'all',
        ...overrides,
    };
}

describe('pathOf', () => {
    it('keeps the path of a page URL, without query, hash or final slash', () => {
        expect(pathOf('/eventos?pagina=2#lista')).toBe('/eventos');
        expect(pathOf('/eventos/')).toBe('/eventos');
        expect(pathOf('https://example.org/articulos/uno')).toBe('/articulos/uno');
        expect(pathOf('/')).toBe('/');
    });
});

describe('isExcludedPage', () => {
    it.each([
        ['/admin', 'Home'],
        ['/admin/anuncios', 'Home'],
        ['/newsletter', 'Newsletter/Show'],
        ['/newsletter/gracias', 'Newsletter/Thanks'],
        ['/iniciar-sesion', 'Auth/Login'],
        ['/registro', 'Auth/Register'],
        ['/aviso-legal', 'Legal/Show'],
        ['/', 'Maintenance'],
        ['/correos/baja/1/a', 'Mail/Unsubscribe'],
        ['/no-existe', 'Error'],
    ])('never opens on %s (%s)', (pathname, component) => {
        expect(isExcludedPage({ pathname, component })).toBe(true);
    });

    it.each([
        ['/', 'Home'],
        ['/eventos', 'Events/Index'],
        ['/newsletters-antiguas', 'Articles/Show'],
    ])('may open on %s', (pathname, component) => {
        expect(isExcludedPage({ pathname, component })).toBe(false);
    });
});

describe('matchesPathPattern', () => {
    it('matches one page exactly, with or without a final slash', () => {
        expect(matchesPathPattern('/eventos', '/eventos')).toBe(true);
        expect(matchesPathPattern('/eventos/', '/eventos')).toBe(true);
        expect(matchesPathPattern('/eventos', '/eventos/torneo')).toBe(false);
    });

    it('matches everything that starts like a pattern ending in *', () => {
        expect(matchesPathPattern('/eventos*', '/eventos')).toBe(true);
        expect(matchesPathPattern('/eventos*', '/eventos/torneo')).toBe(true);
        expect(matchesPathPattern('/eventos/*', '/eventos')).toBe(false);
        expect(matchesPathPattern('/eventos*', '/articulos')).toBe(false);
    });
});

describe('fitsPage', () => {
    const home = { pathname: '/', component: 'Home' };
    const events = { pathname: '/eventos', component: 'Events/Index' };

    it('opens on every page, only on the home page or only on its paths', () => {
        expect(fitsPage(popup(), events)).toBe(true);
        expect(fitsPage(popup({ pages: 'home' }), home)).toBe(true);
        expect(fitsPage(popup({ pages: 'home' }), events)).toBe(false);
        expect(
            fitsPage(popup({ pages: 'paths', paths: ['/articulos', '/eventos*'] }), events)
        ).toBe(true);
        expect(fitsPage(popup({ pages: 'paths', paths: ['/articulos'] }), events)).toBe(false);
    });

    it('never opens on an excluded page, not even with a matching path', () => {
        expect(fitsPage(popup(), { pathname: '/aviso-legal', component: 'Legal/Show' })).toBe(
            false
        );
        expect(
            fitsPage(popup({ pages: 'paths', paths: ['/admin*'] }), {
                pathname: '/admin',
                component: 'Home',
            })
        ).toBe(false);
    });
});

describe('fitsDevice', () => {
    it('opens on every screen, only on phones or only on desktop', () => {
        expect(fitsDevice('all', true)).toBe(true);
        expect(fitsDevice('all', false)).toBe(true);
        expect(fitsDevice('mobile', true)).toBe(true);
        expect(fitsDevice('mobile', false)).toBe(false);
        expect(fitsDevice('desktop', false)).toBe(true);
        expect(fitsDevice('desktop', true)).toBe(false);
    });
});
