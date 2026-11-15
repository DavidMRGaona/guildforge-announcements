import { afterEach, describe, expect, it, vi } from 'vitest';
import { h, type VNode } from 'vue';
import { mount, type VueWrapper } from '@vue/test-utils';
import PopupDialog from '../../resources/js/popup/PopupDialog.vue';
import type { PopupAnnouncement } from '../../resources/js/types/announcement';
import type { ResolvedSiteBlock } from '@/types/siteBlocks';

/**
 * The dialog only draws the window and says how it was closed. Counting and remembering belong to
 * AnnouncementPopup, so the dialog alone sends nothing (announcements#69): nothing it draws can
 * count, which is why a preview of it never counts either.
 */
const announcement: PopupAnnouncement = {
    id: 'a',
    title: 'Hazte socio',
    content: '<p>Texto</p>',
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
        action: { label: 'Apúntate', url: '/socios' },
        triggers: { delay_seconds: 0, scroll_percent: null, exit_intent: false, page_views: null },
        pages: 'all',
        paths: [],
        device: 'all',
        frequency: 'once',
        frequency_days: null,
        block: null,
    },
    seen: false,
};

let wrapper: VueWrapper | null = null;

afterEach(() => {
    wrapper?.unmount();
    wrapper = null;
    vi.unstubAllGlobals();
});

describe('PopupDialog', () => {
    it('sends nothing on its own and reports how it was closed', () => {
        const fetchMock = vi.fn();
        vi.stubGlobal('fetch', fetchMock);
        wrapper = mount(PopupDialog, { props: { announcement }, attachTo: document.body });

        const link = document.body.querySelector<HTMLAnchorElement>('a[href="/socios"]');
        link?.addEventListener('click', (event) => event.preventDefault());
        link?.click();
        document.body.querySelector<HTMLButtonElement>('button')?.click();

        expect(wrapper.emitted('close')).toEqual([['action'], ['dismiss']]);
        expect(fetchMock).not.toHaveBeenCalled();
    });

    describe('with a site block', () => {
        const block: ResolvedSiteBlock = {
            key: 'example-module.subscribe',
            module: 'example-module',
            component: 'components/SubscribeBlock.vue',
            props: { heading: 'Suscríbete' },
        };

        // Stands in for the core's SiteBlock.vue, which loads the module's component
        const SiteBlockStub = {
            props: {
                block: { type: Object, required: true },
                placement: { type: String, default: null },
            },
            render(this: { block: ResolvedSiteBlock; placement: string | null }): VNode {
                return h(
                    'div',
                    { 'data-testid': 'site-block', 'data-placement': this.placement },
                    JSON.stringify(this.block)
                );
            },
        };

        function withBlock(shape: 'centered' | 'corner', action = true): PopupAnnouncement {
            return {
                ...announcement,
                popup: {
                    ...announcement.popup,
                    shape,
                    action: action ? announcement.popup.action : null,
                    block,
                },
            };
        }

        it.each(['centered', 'corner'] as const)(
            'draws the block of a %s window with the core component',
            (shape) => {
                wrapper = mount(PopupDialog, {
                    props: { announcement: withBlock(shape) },
                    attachTo: document.body,
                    global: { stubs: { SiteBlock: SiteBlockStub } },
                });

                const drawn = document.body.querySelectorAll('[data-testid="site-block"]');

                expect(drawn).toHaveLength(1);
                expect(drawn[0]?.getAttribute('data-placement')).toBe('popup');
                expect(drawn[0]?.textContent).toContain('example-module.subscribe');
            }
        );

        it('draws nothing extra for a window without a block', () => {
            wrapper = mount(PopupDialog, {
                props: { announcement },
                attachTo: document.body,
                global: { stubs: { SiteBlock: SiteBlockStub } },
            });

            expect(document.body.querySelector('[data-testid="site-block"]')).toBeNull();
        });

        it('keeps the focus inside the modal window, whose block may hold a form', () => {
            wrapper = mount(PopupDialog, {
                props: { announcement: withBlock('centered', false) },
                attachTo: document.body,
                global: {
                    stubs: {
                        SiteBlock: {
                            render: (): VNode => h('input', { 'data-testid': 'site-block-input' }),
                        },
                    },
                },
            });

            const dialog = document.body.querySelector<HTMLElement>('[role="dialog"]');
            const input = document.body.querySelector<HTMLElement>(
                '[data-testid="site-block-input"]'
            );

            expect(dialog?.contains(input)).toBe(true);
        });
    });
});
