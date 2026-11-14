import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import { nextTick, reactive } from 'vue';
import {
    notifyCookieConsentChanged,
    notifyCookieConsentDecided,
    registerCookieConsentProvider,
} from '@/contracts/cookieConsent';
import AnnouncementPopup from '../../resources/js/components/AnnouncementPopup.vue';
import type {
    AnnouncementPopup as PopupSettings,
    PopupAnnouncement,
} from '../../resources/js/types/announcement';

// The Inertia page, shared by the component and the consent contract
const page = reactive({
    url: '/',
    component: 'Home',
    props: { cookieConsentModule: false } as Record<string, unknown>,
});

vi.mock('@inertiajs/vue3', (): { usePage: () => typeof page } => ({
    usePage: (): typeof page => page,
}));

let mobileScreen = false;

function stubScreen(): void {
    vi.stubGlobal(
        'matchMedia',
        vi.fn((query: string) => ({
            matches: query.includes('max-width') ? mobileScreen : !mobileScreen,
            media: query,
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
        }))
    );
}

function popup(
    id: string,
    settings: Partial<PopupSettings> = {},
    overrides: Partial<PopupAnnouncement> = {}
): PopupAnnouncement {
    return {
        id,
        title: `Ventana ${id}`,
        content: '<p>Texto de la ventana</p>',
        visibility: 'public',
        position: 'before-header',
        priority: 5,
        background_color: null,
        text_color: null,
        starts_at: null,
        ends_at: null,
        is_active: true,
        is_dismissible: true,
        created_at: null,
        updated_at: null,
        format: 'popup',
        popup: {
            shape: 'centered',
            image_public_id: null,
            image_alt: null,
            action: null,
            triggers: {
                delay_seconds: 0,
                scroll_percent: null,
                exit_intent: false,
                page_views: null,
            },
            pages: 'all',
            paths: [],
            device: 'all',
            ...settings,
        },
        ...overrides,
    };
}

let wrapper: VueWrapper | null = null;

function mountPopups(announcementPopups: PopupAnnouncement[]): VueWrapper {
    wrapper = mount(AnnouncementPopup, {
        props: { announcementPopups },
        attachTo: document.body,
    });

    return wrapper;
}

async function settle(ms = 0): Promise<void> {
    await flushPromises();
    vi.advanceTimersByTime(ms);
    await nextTick();
}

function dialog(): HTMLElement | null {
    return document.body.querySelector('[role="dialog"]');
}

async function visit(url: string, component = 'Home'): Promise<void> {
    page.url = url;
    page.component = component;
    await settle();
}

describe('AnnouncementPopup', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        window.localStorage.clear();
        window.sessionStorage.clear();
        mobileScreen = false;
        stubScreen();
        page.url = '/';
        page.component = 'Home';
        page.props = { cookieConsentModule: false };
    });

    afterEach(() => {
        wrapper?.unmount();
        wrapper = null;
        vi.useRealTimers();
        vi.unstubAllGlobals();
    });

    it('opens the first window that fits as a modal dialog named by its title', async () => {
        mountPopups([popup('alta'), popup('baja')]);
        await settle();

        const shown = dialog();
        expect(shown).not.toBeNull();
        expect(shown?.getAttribute('aria-modal')).toBe('true');
        expect(
            document.getElementById(shown?.getAttribute('aria-labelledby') ?? '')?.textContent
        ).toContain('Ventana alta');
        expect(document.body.querySelectorAll('[role="dialog"]')).toHaveLength(1);
    });

    it('draws the corner card as a dialog that is not modal', async () => {
        mountPopups([popup('a', { shape: 'corner' })]);
        await settle();

        expect(dialog()?.hasAttribute('aria-modal')).toBe(false);
    });

    it('waits for its trigger', async () => {
        mountPopups([
            popup('a', {
                triggers: {
                    delay_seconds: 5,
                    scroll_percent: null,
                    exit_intent: false,
                    page_views: null,
                },
            }),
        ]);

        await settle(4000);
        expect(dialog()).toBeNull();
        await settle(1000);
        expect(dialog()).not.toBeNull();
    });

    it('skips windows for another page, another screen, or already closed in this browser', async () => {
        window.localStorage.setItem('guildforge_dismissed_popups', JSON.stringify(['cerrada']));
        mobileScreen = true;

        mountPopups([
            popup('portada-no', { pages: 'paths', paths: ['/eventos*'] }),
            popup('escritorio', { device: 'desktop' }),
            popup('cerrada'),
            popup('esta'),
        ]);
        await settle();

        expect(dialog()?.textContent).toContain('Ventana esta');
    });

    it('never opens on a page that is always left out', async () => {
        page.url = '/aviso-legal';
        page.component = 'Legal/Show';

        mountPopups([popup('a')]);
        await settle(60_000);

        expect(dialog()).toBeNull();
    });

    it('waits for the answer to the cookie banner before opening', async () => {
        page.props = { cookieConsentModule: true };
        let decided = false;
        registerCookieConsentProvider({ hasConsent: () => decided, hasDecided: () => decided });

        mountPopups([popup('a')]);
        await settle(60_000);
        expect(dialog()).toBeNull();

        decided = true;
        notifyCookieConsentDecided();
        await settle();
        expect(dialog()).not.toBeNull();
    });

    it('closes when the banner asks again, and comes back once it has an answer', async () => {
        page.props = { cookieConsentModule: true };
        let decided = true;
        registerCookieConsentProvider({ hasConsent: () => decided, hasDecided: () => decided });

        mountPopups([popup('a')]);
        await settle();
        expect(dialog()).not.toBeNull();

        decided = false;
        notifyCookieConsentChanged();
        await settle();
        expect(dialog()).toBeNull();

        decided = true;
        notifyCookieConsentDecided();
        await settle();
        expect(dialog()).not.toBeNull();
    });

    it('closes with Escape, gives the focus back and stays closed in this browser', async () => {
        const opener = document.createElement('button');
        document.body.appendChild(opener);
        opener.focus();

        mountPopups([popup('a'), popup('b')]);
        await settle();
        expect(document.activeElement).toBe(dialog());

        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await settle();

        expect(dialog()).toBeNull();
        expect(document.activeElement).toBe(opener);
        expect(
            JSON.parse(window.localStorage.getItem('guildforge_dismissed_popups') ?? '[]')
        ).toEqual(['a']);
        // At most one window per page: the next one waits for another page
        await settle(60_000);
        expect(dialog()).toBeNull();

        await visit('/eventos', 'Events/Index');
        expect(dialog()?.textContent).toContain('Ventana b');
        opener.remove();
    });

    it('can always be closed with its ✕, even if the announcement said otherwise', async () => {
        mountPopups([popup('a', {}, { is_dismissible: false })]);
        await settle();

        const close = dialog()?.querySelector<HTMLButtonElement>(
            'button[aria-label="Cerrar la ventana"]'
        );
        expect(close).toBeTruthy();
        close?.click();
        await settle();

        expect(dialog()).toBeNull();
    });

    it('keeps the focus inside the centred window', async () => {
        mountPopups([popup('a', { action: { label: 'Apúntate', url: '/socios' } })]);
        await settle();

        const link = dialog()?.querySelector<HTMLAnchorElement>('a[href="/socios"]');
        const close = dialog()?.querySelector<HTMLButtonElement>('button');
        expect(link?.textContent).toContain('Apúntate');

        close?.focus();
        dialog()?.dispatchEvent(new KeyboardEvent('keydown', { key: 'Tab', bubbles: true }));
        expect(document.activeElement).toBe(link);

        link?.focus();
        dialog()?.dispatchEvent(
            new KeyboardEvent('keydown', { key: 'Tab', shiftKey: true, bubbles: true })
        );
        expect(document.activeElement).toBe(close);
    });

    it('remembers a window whose button was followed', async () => {
        mountPopups([popup('a', { action: { label: 'Ver eventos', url: '/eventos' } })]);
        await settle();

        dialog()
            ?.querySelector<HTMLAnchorElement>('a[href="/eventos"]')
            ?.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
        await settle();

        expect(dialog()).toBeNull();
        expect(
            JSON.parse(window.localStorage.getItem('guildforge_dismissed_popups') ?? '[]')
        ).toEqual(['a']);
    });

    it('stays open on the next page if it fits there, and goes if it does not', async () => {
        mountPopups([popup('a', { shape: 'corner', pages: 'paths', paths: ['/eventos*'] })]);
        await visit('/eventos', 'Events/Index');
        expect(dialog()).not.toBeNull();

        await visit('/eventos/torneo', 'Events/Show');
        expect(dialog()).not.toBeNull();

        await visit('/articulos', 'Articles/Index');
        expect(dialog()).toBeNull();
    });

    it('drops the pending triggers of a page left before they are met', async () => {
        mountPopups([
            popup('a', {
                pages: 'home',
                triggers: {
                    delay_seconds: 10,
                    scroll_percent: null,
                    exit_intent: false,
                    page_views: null,
                },
            }),
        ]);
        await settle(5000);

        await visit('/eventos', 'Events/Index');
        await settle(60_000);
        expect(dialog()).toBeNull();
    });

    it('opens after the pages of the visit it asks for', async () => {
        mountPopups([
            popup('a', {
                triggers: {
                    delay_seconds: null,
                    scroll_percent: null,
                    exit_intent: false,
                    page_views: 3,
                },
            }),
        ]);
        await settle();
        expect(dialog()).toBeNull();

        await visit('/eventos', 'Events/Index');
        expect(dialog()).toBeNull();

        await visit('/articulos', 'Articles/Index');
        expect(dialog()).not.toBeNull();
    });

    it('opens once per visit even when not closed', async () => {
        mountPopups([popup('a', { shape: 'corner', pages: 'home' })]);
        await settle();
        expect(dialog()).not.toBeNull();

        await visit('/eventos', 'Events/Index');
        await visit('/', 'Home');
        await settle(60_000);
        expect(dialog()).toBeNull();
    });

    it('closes a window no longer offered after the session changes', async () => {
        const wrapped = mountPopups([popup('visitas', {}, { visibility: 'guests' })]);
        await settle();
        expect(dialog()).not.toBeNull();

        await wrapped.setProps({ announcementPopups: [] });
        await settle();
        expect(dialog()).toBeNull();
    });

    it('stops waiting when it unmounts', async () => {
        page.props = { cookieConsentModule: true };
        let decided = false;
        registerCookieConsentProvider({ hasConsent: () => false, hasDecided: () => decided });

        const wrapped = mountPopups([popup('a')]);
        await settle();
        wrapped.unmount();
        wrapper = null;

        decided = true;
        notifyCookieConsentDecided();
        await settle(60_000);
        expect(dialog()).toBeNull();
    });

    it('does not open over a banner reopened before its trigger is met', async () => {
        page.props = { cookieConsentModule: true };
        let decided = true;
        registerCookieConsentProvider({ hasConsent: () => decided, hasDecided: () => decided });
        const delay = {
            delay_seconds: 30,
            scroll_percent: null,
            exit_intent: false,
            page_views: null,
        };

        mountPopups([popup('a', { triggers: delay })]);
        await settle(10_000);

        decided = false;
        notifyCookieConsentChanged();
        await settle(25_000);
        expect(dialog()).toBeNull();

        decided = true;
        notifyCookieConsentDecided();
        // The triggers start again from the new answer
        await settle(29_000);
        expect(dialog()).toBeNull();
        await settle(1000);
        expect(dialog()).not.toBeNull();
    });

    it('opens no other window after a visit to the same path brings the same windows again', async () => {
        const offered = [popup('a'), popup('b')];
        const wrapped = mountPopups(offered);
        await settle();
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await settle();
        expect(dialog()).toBeNull();

        // A page of results, a filter or a form that redirects back: a new array, the same windows
        page.url = '/?pagina=2';
        await wrapped.setProps({
            announcementPopups: offered.map((announcement) => ({ ...announcement })),
        });
        await settle(60_000);

        expect(dialog()).toBeNull();
    });

    it('keeps counting its delay when a visit to the same path brings the same windows again', async () => {
        const offered = [
            popup('a', {
                triggers: {
                    delay_seconds: 10,
                    scroll_percent: null,
                    exit_intent: false,
                    page_views: null,
                },
            }),
        ];
        const wrapped = mountPopups(offered);
        await settle(5000);

        await wrapped.setProps({
            announcementPopups: offered.map((announcement) => ({ ...announcement })),
        });
        await settle(5000);

        expect(dialog()).not.toBeNull();
    });

    it('closes the corner card with Escape only while the focus is in it', async () => {
        const menu = document.createElement('button');
        document.body.appendChild(menu);
        mountPopups([popup('a', { shape: 'corner' })]);
        await settle();

        menu.focus();
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await settle();
        expect(dialog()).not.toBeNull();
        expect(window.localStorage.getItem('guildforge_dismissed_popups')).toBeNull();

        dialog()?.querySelector<HTMLButtonElement>('button')?.focus();
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await settle();
        expect(dialog()).toBeNull();
        menu.remove();
    });

    it('describes its image with the alternative text, or marks it as decoration', async () => {
        mountPopups([
            popup('a', {
                image_public_id: 'announcements/popups/2026/11/a.webp',
                image_alt: 'Mesa con dados',
            }),
        ]);
        await settle();
        expect(dialog()?.querySelector('img')?.getAttribute('alt')).toBe('Mesa con dados');
        wrapper?.unmount();
        wrapper = null;
        window.sessionStorage.clear();

        mountPopups([popup('b', { image_public_id: 'announcements/popups/2026/11/b.webp' })]);
        await settle();
        expect(dialog()?.querySelector('img')?.getAttribute('alt')).toBe('');
    });
});
