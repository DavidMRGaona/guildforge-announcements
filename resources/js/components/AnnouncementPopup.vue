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
// A window opened (or closed) on this page: no other opens until the path changes
let handledThisPage = false;
let offeredIds = idsOf(props.announcementPopups);

function idsOf(announcements: PopupAnnouncement[]): string {
    return announcements.map(({ id }) => id).join(',');
}

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

/** The banner is asking: a consent module is on the page and has no answer */
function bannerIsAsking(): boolean {
    return hasCookieConsentModule() && !hasCookieConsentDecided();
}

function cancelPending(): void {
    pending?.abort();
    pending = null;

    if (current.value === null) {
        stopWatchingConsent();
    }
}

/** Looks for the window of this page and waits for the banner answer and its first trigger */
async function schedule(): Promise<void> {
    cancelPending();

    if (current.value !== null || handledThisPage) {
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

    // False only when the wait gave up on a provider that never registered
    const answered = hasCookieConsentDecided();

    watchConsent();
    armPopupTriggers(
        next.popup.triggers,
        environment(),
        () => open(next, answered),
        controller.signal
    );
}

function open(announcement: PopupAnnouncement, answeredWhenArmed: boolean): void {
    pending = null;

    if (
        current.value !== null ||
        handledThisPage ||
        !props.announcementPopups.some(({ id }) => id === announcement.id)
    ) {
        return;
    }

    // The banner came back while the trigger was pending: wait for its new answer
    if (answeredWhenArmed && bannerIsAsking()) {
        void schedule();

        return;
    }

    current.value = announcement;
    handledThisPage = true;
    markShown(announcement.id);
}

/**
 * While a trigger is pending or the window is open, a banner that asks again (preferences
 * reopened, or a provider that registered late with its banner showing) puts the window away
 * and makes it wait for the new answer, triggers and all.
 */
function watchConsent(): void {
    stopWatchingConsent();

    let first = true;

    stopConsentWatch = watchEffect(() => {
        // Read on every change the provider reports, also when the answer stays the same
        const decided = hasCookieConsentDecided();

        if (first) {
            first = false;

            return;
        }

        if (decided || !hasCookieConsentModule()) {
            return;
        }

        const shown = current.value;

        if (shown !== null) {
            current.value = null;
            forgetShown(shown.id);
            handledThisPage = false;
        }

        void schedule();
    });
}

function stopWatchingConsent(): void {
    stopConsentWatch?.();
    stopConsentWatch = null;
}

function close(): void {
    stopWatchingConsent();
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

    // An open window that fits the new page is that page's window
    handledThisPage = current.value !== null;
    void schedule();
}

watch(pathname, visitPage);

/**
 * Every Inertia visit brings a new array, also to the same path (pages of results, filters, a
 * form that redirects back): only a change in the windows offered (signing in or out) counts.
 * A window no longer offered goes; pending triggers start again only for a new set.
 */
watch(
    () => props.announcementPopups,
    (announcements) => {
        const ids = idsOf(announcements);

        if (ids === offeredIds) {
            return;
        }

        offeredIds = ids;
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
