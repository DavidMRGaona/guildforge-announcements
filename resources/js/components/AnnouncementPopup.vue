<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, shallowRef, watch, watchEffect } from 'vue';
import {
    hasCookieConsentDecided,
    hasCookieConsentModule,
    whenConsentDecided,
} from '@/contracts/cookieConsent';
import PopupDialog from '../popup/PopupDialog.vue';
import type { PopupAnnouncement } from '../types/announcement';
import {
    countPageView,
    forgetShown,
    isDismissed,
    markDismissed,
    markShown,
    wasShownThisVisit,
} from '../utils/popupMemory';
import { fitsDevice, fitsPage, pathOf, type PopupPageContext } from '../utils/popupPages';
import { armPopupTriggers, detectEnvironment, type PopupEnvironment } from '../utils/popupTriggers';

/**
 * The windows of announcements (announcements#67), mounted once in the layout. The server sends
 * the ones this person may see now, best first (priority, then the most recent start); this
 * component opens at most one per page: the first that fits the page and the screen and was
 * neither closed in this browser nor opened in this visit, once the cookie banner has an answer
 * and one of its triggers is met.
 *
 * The layout stays mounted while Inertia moves between pages, so each new path is a new page:
 * pending triggers are dropped, and an open window stays only if it fits the new page.
 */
const props = withDefaults(
    defineProps<{
        announcementPopups?: PopupAnnouncement[];
    }>(),
    { announcementPopups: () => [] }
);

const page = usePage();

const current = shallowRef<PopupAnnouncement | null>(null);
const pathname = computed(() => pathOf(page.url));

let pending: AbortController | null = null;
let pageViews = 0;
let stopConsentWatch: (() => void) | null = null;

function pageContext(): PopupPageContext {
    return { pathname: pathname.value, component: page.component };
}

function environment(): PopupEnvironment {
    return detectEnvironment(window, pageViews);
}

function isUsable(announcement: PopupAnnouncement): boolean {
    return announcement.format === 'popup' && announcement.popup !== null;
}

function fits(
    announcement: PopupAnnouncement,
    context: PopupPageContext,
    isMobile: boolean
): boolean {
    return (
        isUsable(announcement) &&
        fitsPage(announcement.popup, context) &&
        fitsDevice(announcement.popup.device, isMobile)
    );
}

function candidate(): PopupAnnouncement | null {
    const context = pageContext();
    const { isMobile } = environment();

    return (
        props.announcementPopups.find(
            (announcement) =>
                fits(announcement, context, isMobile) &&
                !isDismissed(announcement.id) &&
                !wasShownThisVisit(announcement.id)
        ) ?? null
    );
}

function cancelPending(): void {
    pending?.abort();
    pending = null;
}

/** Looks for the window of this page and waits for the banner answer and its first trigger */
async function schedule(): Promise<void> {
    cancelPending();

    if (current.value !== null) {
        return;
    }

    const next = candidate();

    if (next === null) {
        return;
    }

    const controller = new AbortController();
    pending = controller;

    try {
        // Never over the cookie banner: once it has an answer (or there is none to wait for)
        await whenConsentDecided({ signal: controller.signal });
    } catch {
        return;
    }

    if (controller.signal.aborted) {
        return;
    }

    armPopupTriggers(next.popup.triggers, environment(), () => open(next), controller.signal);
}

function open(announcement: PopupAnnouncement): void {
    pending = null;

    if (
        current.value !== null ||
        !props.announcementPopups.some(({ id }) => id === announcement.id)
    ) {
        return;
    }

    current.value = announcement;
    markShown(announcement.id);
    watchConsent(announcement);
}

/**
 * If the banner asks again while the window is open (preferences reopened, or a provider that
 * registered late with its banner showing), the window goes and waits for the new answer.
 */
function watchConsent(announcement: PopupAnnouncement): void {
    stopConsentWatch?.();

    let first = true;

    stopConsentWatch = watchEffect(() => {
        // Read on every change the provider reports, also when the answer stays the same
        const decided = hasCookieConsentDecided();

        if (first) {
            first = false;

            return;
        }

        if (!decided && hasCookieConsentModule() && current.value?.id === announcement.id) {
            close();
            forgetShown(announcement.id);
            void schedule();
        }
    });
}

function close(): void {
    stopConsentWatch?.();
    stopConsentWatch = null;
    current.value = null;
}

/** Closed with the ✕, Esc, the backdrop or its button: not again in this browser, and no other on this page */
function onClose(): void {
    const announcement = current.value;

    if (announcement === null) {
        return;
    }

    markDismissed(announcement.id);
    close();
}

function visitPage(): void {
    pageViews = countPageView(pathname.value);

    const shown = current.value;

    if (shown !== null && !fits(shown, pageContext(), environment().isMobile)) {
        close();
    }

    void schedule();
}

watch(pathname, visitPage);

// The list changes with the session (signing in or out): a window no longer offered goes
watch(
    () => props.announcementPopups,
    (announcements) => {
        const shown = current.value;

        if (shown !== null && !announcements.some(({ id }) => id === shown.id)) {
            close();
        }

        if (current.value === null) {
            void schedule();
        }
    }
);

onMounted(visitPage);

onBeforeUnmount(() => {
    cancelPending();
    close();
});
</script>

<template>
    <PopupDialog v-if="current" :key="current.id" :announcement="current" @close="onClose" />
</template>
