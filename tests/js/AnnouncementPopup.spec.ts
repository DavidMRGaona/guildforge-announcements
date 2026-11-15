import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import { nextTick, reactive } from 'vue';
import {
    notifyCookieConsentChanged,
    notifyCookieConsentDecided,
    registerCookieConsentProvider,
} from '@/contracts/cookieConsent';
import AnnouncementPopup from '../../resources/js/components/AnnouncementPopup.vue';
import { resetPageMemory } from '../../resources/js/utils/popupMemory';
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
            frequency: 'once',
            frequency_days: null,
            ...settings,
        },
        seen: false,
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

/** What this browser keeps about the windows in `localStorage`, by announcement id */
function storedViews(): Record<string, { shown: number | null; dismissed: number | null }> {
    return JSON.parse(window.localStorage.getItem('guildforge_popup_views') ?? '{}');
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
        resetPageMemory();
        mobileScreen = false;
        stubScreen();
        // Every window now counts (announcements#69): no test reaches the network
        vi.stubGlobal(
            'fetch',
            vi.fn(() => Promise.resolve(new Response(null, { status: 204 })))
        );
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
        window.localStorage.setItem(
            'guildforge_popup_views',
            JSON.stringify({ cerrada: { shown: Date.now(), dismissed: Date.now() } })
        );
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
        expect(storedViews().a?.dismissed).toEqual(expect.any(Number));
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
        expect(storedViews().a?.dismissed).toEqual(expect.any(Number));
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
        // Opened, not closed: the browser knows it was shown and nothing more
        expect(storedViews().a?.dismissed).toBeNull();

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

    describe('frequency (announcements#68)', () => {
        async function closeIt(): Promise<void> {
            dialog()?.querySelector<HTMLButtonElement>('button')?.click();
            await settle();
        }

        /** A new visit of the same browser: the page loads again and the tab's session is new */
        async function newVisit(offered: PopupAnnouncement[]): Promise<void> {
            wrapper?.unmount();
            wrapper = null;
            window.sessionStorage.clear();
            resetPageMemory();
            mountPopups(offered);
            await settle();
        }

        it('opens a window once: not again in a later visit', async () => {
            const offered = [popup('a', { frequency: 'once' })];
            mountPopups(offered);
            await settle();
            await closeIt();

            await newVisit(offered);
            expect(dialog()).toBeNull();
        });

        it('keeps a window opened but not closed from coming back, as it counts as seen', async () => {
            const offered = [popup('a', { frequency: 'once' })];
            mountPopups(offered);
            await settle();
            expect(dialog()).not.toBeNull();

            await newVisit(offered);
            expect(dialog()).toBeNull();
        });

        it('opens a window once per session: again in the next visit, not in the same', async () => {
            const offered = [popup('a', { frequency: 'session' })];
            mountPopups(offered);
            await settle();
            await closeIt();

            await visit('/eventos', 'Events/Index');
            await settle(60_000);
            expect(dialog()).toBeNull();

            await newVisit(offered);
            expect(dialog()?.textContent).toContain('Ventana a');
        });

        it('opens a window every N days once they have passed since it was shown', async () => {
            const offered = [popup('a', { frequency: 'days', frequency_days: 7 })];
            mountPopups(offered);
            await settle();
            await closeIt();

            vi.advanceTimersByTime(6 * 86_400_000 + 23 * 3_600_000);
            await newVisit(offered);
            expect(dialog()).toBeNull();

            vi.advanceTimersByTime(3_600_000);
            await newVisit(offered);
            expect(dialog()?.textContent).toContain('Ventana a');
        });

        it('counts the days from the last time it was shown, not from the closing', async () => {
            const offered = [popup('a', { frequency: 'days', frequency_days: 2 })];
            mountPopups(offered);
            await settle();

            vi.advanceTimersByTime(86_400_000);
            await closeIt();

            // Shown two days ago, closed one day ago: due
            vi.advanceTimersByTime(86_400_000);
            await newVisit(offered);
            expect(dialog()).not.toBeNull();
        });
    });

    describe('what the server is told (announcements#68)', () => {
        const fetchMock = vi.fn(() => Promise.resolve(new Response(null, { status: 204 })));

        beforeEach(() => {
            fetchMock.mockClear();
            vi.stubGlobal('fetch', fetchMock);
        });

        function lastCalls(): string[] {
            return (fetchMock.mock.calls as unknown as [string, RequestInit][]).map(
                ([url, init]) => `${init.method} ${url}`
            );
        }

        it('is told when a signed-in account gets a window and when it closes it', async () => {
            page.props = { cookieConsentModule: false, auth: { user: { id: 'u1' } } };
            mountPopups([popup('a')]);
            await settle();

            expect(lastCalls()).toEqual([
                'POST /anuncios/ventanas/a/mostrada',
                'POST /anuncios/ventanas/a/metrica/shown',
            ]);

            dialog()?.querySelector<HTMLButtonElement>('button')?.click();
            await settle();

            expect(lastCalls()).toEqual([
                'POST /anuncios/ventanas/a/mostrada',
                'POST /anuncios/ventanas/a/metrica/shown',
                'POST /anuncios/ventanas/a/cerrada',
                'POST /anuncios/ventanas/a/metrica/dismissed',
            ]);
        });

        it('keeps a guest’s memory in the browser alone, and only counts', async () => {
            mountPopups([popup('a')]);
            await settle();
            dialog()?.querySelector<HTMLButtonElement>('button')?.click();
            await settle();

            // No notice of what this person saw: just the anonymous counters
            expect(lastCalls()).toEqual([
                'POST /anuncios/ventanas/a/metrica/shown',
                'POST /anuncios/ventanas/a/metrica/dismissed',
            ]);
            expect(storedViews().a?.dismissed).toEqual(expect.any(Number));
        });

        it('counts a press of the button as a click, not as a closing (announcements#69)', async () => {
            mountPopups([popup('a', { action: { label: 'Apúntate', url: '/socios' } })]);
            await settle();

            const link = dialog()?.querySelector<HTMLAnchorElement>('a[href="/socios"]');
            link?.addEventListener('click', (event) => event.preventDefault());
            link?.click();
            await settle();

            expect(lastCalls()).toEqual([
                'POST /anuncios/ventanas/a/metrica/shown',
                'POST /anuncios/ventanas/a/metrica/action_clicked',
            ]);
            expect(dialog()).toBeNull();
        });

        it('counts each window once per showing, and a window that never opens counts nothing', async () => {
            mountPopups([popup('a', { pages: 'home' }), popup('b', { pages: 'home' })]);
            page.url = '/eventos';
            await settle();

            expect(fetchMock).not.toHaveBeenCalled();
        });

        it('writes and sends nothing while an administrator views the site as someone else', async () => {
            page.props = {
                cookieConsentModule: false,
                auth: { user: { id: 'u1' } },
                impersonation: { targetName: 'Ana', mode: 'read_only', notice: null },
            };
            mountPopups([popup('a')]);
            await settle();
            expect(dialog()).not.toBeNull();

            dialog()?.querySelector<HTMLButtonElement>('button')?.click();
            await settle();

            expect(fetchMock).not.toHaveBeenCalled();
            expect(window.localStorage.length).toBe(0);
            expect(window.sessionStorage.getItem('guildforge_shown_popups')).toBeNull();

            // The administrator’s own browser memory does not hide it either
            wrapper?.unmount();
            wrapper = null;
            // A new page load
            resetPageMemory();
            window.localStorage.setItem(
                'guildforge_popup_views',
                JSON.stringify({ a: { shown: 1, dismissed: 1 } })
            );
            mountPopups([popup('a')]);
            await settle();
            expect(dialog()).not.toBeNull();
        });

        it('still works when the notice cannot be sent', async () => {
            fetchMock.mockImplementation(() => Promise.reject(new Error('offline')));
            page.props = { cookieConsentModule: false, auth: { user: { id: 'u1' } } };
            mountPopups([popup('a')]);
            await settle();

            dialog()?.querySelector<HTMLButtonElement>('button')?.click();
            await settle();

            expect(dialog()).toBeNull();
        });
    });

    describe('storage that fails (announcements#68)', () => {
        function breakStorage(): void {
            vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
                throw new DOMException('full', 'QuotaExceededError');
            });
            vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
                throw new DOMException('blocked', 'SecurityError');
            });
        }

        afterEach(() => {
            vi.restoreAllMocks();
        });

        it('opens and closes the window as usual', async () => {
            breakStorage();
            mountPopups([popup('a')]);
            await settle();
            expect(dialog()).not.toBeNull();

            dialog()?.querySelector<HTMLButtonElement>('button')?.click();
            await settle();
            expect(dialog()).toBeNull();
        });

        it('does not open it again on this page load', async () => {
            breakStorage();
            mountPopups([popup('a'), popup('b')]);
            await settle();
            dialog()?.querySelector<HTMLButtonElement>('button')?.click();
            await settle();

            await visit('/eventos', 'Events/Index');
            expect(dialog()?.textContent).toContain('Ventana b');
            dialog()?.querySelector<HTMLButtonElement>('button')?.click();
            await settle();

            await visit('/', 'Home');
            await settle(60_000);
            expect(dialog()).toBeNull();
        });
    });

    describe('windows the account has already seen (announcements#68)', () => {
        it('does not open a window the server marks as seen', async () => {
            mountPopups([popup('vista', {}, { seen: true }), popup('nueva')]);
            await settle();

            expect(dialog()?.textContent).toContain('Ventana nueva');
        });

        it('keeps an open window while the visits bring it back marked as seen', async () => {
            const offered = [popup('a', { shape: 'corner' })];
            const wrapped = mountPopups(offered);
            await settle();
            expect(dialog()).not.toBeNull();

            // The server now knows it was shown: the next visit shares it as seen
            page.url = '/eventos';
            page.component = 'Events/Index';
            await wrapped.setProps({ announcementPopups: [{ ...offered[0]!, seen: true }] });
            await settle();

            expect(dialog()?.textContent).toContain('Ventana a');

            // And the same path again (filters, pages of results)
            await wrapped.setProps({ announcementPopups: [{ ...offered[0]!, seen: true }] });
            await settle();
            expect(dialog()).not.toBeNull();
        });

        it('does not open it again on a later page once it was closed', async () => {
            const offered = [popup('a'), popup('b')];
            const wrapped = mountPopups(offered);
            await settle();
            dialog()?.querySelector<HTMLButtonElement>('button')?.click();
            await settle();

            await wrapped.setProps({
                announcementPopups: [{ ...offered[0]!, seen: true }, offered[1]!],
            });
            await visit('/eventos', 'Events/Index');

            expect(dialog()?.textContent).toContain('Ventana b');
        });
    });
});
