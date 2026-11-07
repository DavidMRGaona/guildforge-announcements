import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import AnnouncementBanner from '../../resources/js/components/AnnouncementBanner.vue';
import type { Announcement } from '../../resources/js/types/announcement';

function announcement(id: string, overrides: Partial<Announcement> = {}): Announcement {
    return {
        id,
        title: `Anuncio ${id}`,
        content: '<p>Contenido</p>',
        priority: 5,
        is_dismissible: true,
        position: 'before-header',
        ...overrides,
    } as Announcement;
}

function mountBanner(announcements: Announcement[]): ReturnType<typeof mount> {
    return mount(AnnouncementBanner, {
        props: { announcements, settings: { auto_rotate: false } },
        global: { stubs: { Transition: false } },
    });
}

describe('AnnouncementBanner accessible names', () => {
    it('names the arrows, the close button and every dot', () => {
        const wrapper = mountBanner([announcement('a'), announcement('b'), announcement('c')]);

        const labels = wrapper.findAll('button').map((button) => button.attributes('aria-label'));

        expect(labels).toEqual([
            'Anuncio anterior',
            'Anuncio siguiente',
            'Cerrar anuncios',
            'Ir al anuncio 1',
            'Ir al anuncio 2',
            'Ir al anuncio 3',
        ]);
    });

    it('has no unnamed button with a single announcement', () => {
        const wrapper = mountBanner([announcement('a')]);

        const labels = wrapper.findAll('button').map((button) => button.attributes('aria-label'));

        expect(labels).toEqual(['Cerrar anuncios']);
    });
});
