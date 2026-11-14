<script setup lang="ts">
import { X } from '@lucide/vue';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { buildImageUrl } from '@/utils/cloudinary';
import type { PopupAnnouncement, PopupCloseReason } from '../types/announcement';

/**
 * One window of an announcement. The centred shape is modal: it covers the page, keeps the focus
 * inside and gives it back on closing. The corner card is not: it does not take the focus from
 * what the visitor is doing and the page stays usable. Both close with the ✕ and with Esc, always.
 */
const props = defineProps<{
    announcement: PopupAnnouncement;
}>();

const emit = defineEmits<{
    close: [reason: PopupCloseReason];
}>();

const { t } = useI18n();

const panel = ref<HTMLElement | null>(null);
const isModal = computed(() => props.announcement.popup.shape === 'centered');
const titleId = computed(() => `announcement-popup-title-${props.announcement.id}`);
const bodyId = computed(() => `announcement-popup-body-${props.announcement.id}`);
const imageUrl = computed(() =>
    buildImageUrl(props.announcement.popup.image_public_id, { width: 960, crop: 'fit' })
);
const action = computed(() => props.announcement.popup.action);

const FOCUSABLE =
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

let returnFocusTo: HTMLElement | null = null;
let previousOverflow: string | null = null;

function focusableElements(): HTMLElement[] {
    return panel.value ? Array.from(panel.value.querySelectorAll<HTMLElement>(FOCUSABLE)) : [];
}

function onDocumentKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape' && !event.defaultPrevented) {
        event.preventDefault();
        emit('close', 'dismiss');
    }
}

/** Tab and Shift+Tab go round the window's own controls while it is modal */
function onPanelKeydown(event: KeyboardEvent): void {
    if (!isModal.value || event.key !== 'Tab') {
        return;
    }

    const elements = focusableElements();
    const first = elements[0];
    const last = elements[elements.length - 1];

    if (first === undefined || last === undefined) {
        event.preventDefault();
        panel.value?.focus();

        return;
    }

    const active = document.activeElement;

    if (event.shiftKey && (active === first || active === panel.value)) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && active === last) {
        event.preventDefault();
        first.focus();
    }
}

/** Focus that leaves a modal window by any other way (a click, a screen reader) is brought back */
function onDocumentFocusIn(event: FocusEvent): void {
    if (
        isModal.value &&
        panel.value &&
        event.target instanceof Node &&
        !panel.value.contains(event.target)
    ) {
        panel.value.focus();
    }
}

function onBackdropClick(event: MouseEvent): void {
    if (event.target === event.currentTarget) {
        emit('close', 'dismiss');
    }
}

onMounted(async () => {
    document.addEventListener('keydown', onDocumentKeydown);

    if (!isModal.value) {
        return;
    }

    returnFocusTo = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    previousOverflow = document.documentElement.style.overflow;
    document.documentElement.style.overflow = 'hidden';
    document.addEventListener('focusin', onDocumentFocusIn);

    await nextTick();
    panel.value?.focus();
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onDocumentKeydown);

    if (!isModal.value) {
        return;
    }

    document.removeEventListener('focusin', onDocumentFocusIn);
    document.documentElement.style.overflow = previousOverflow ?? '';

    if (returnFocusTo && document.contains(returnFocusTo)) {
        returnFocusTo.focus();
    }
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="isModal"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4"
            data-testid="announcement-popup-backdrop"
            @click="onBackdropClick"
        >
            <div
                ref="panel"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                :aria-describedby="bodyId"
                tabindex="-1"
                class="relative max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-surface text-base-primary shadow-xl focus:outline-none motion-safe:animate-[announcement-popup-in_200ms_ease-out]"
                @keydown="onPanelKeydown"
            >
                <img
                    v-if="imageUrl"
                    :src="imageUrl"
                    alt=""
                    class="max-h-64 w-full rounded-t-lg object-cover"
                    loading="lazy"
                />
                <div class="p-6">
                    <h2 :id="titleId" class="pr-8 text-lg font-semibold text-base-primary">
                        {{ announcement.title }}
                    </h2>
                    <!-- The server sanitizes the content (EloquentAnnouncementRepository) -->
                    <!-- eslint-disable vue/no-v-html -->
                    <div
                        :id="bodyId"
                        class="prose prose-sm mt-2 max-w-none text-base-secondary"
                        v-html="announcement.content"
                    />
                    <!-- eslint-enable vue/no-v-html -->
                    <a
                        v-if="action"
                        :href="action.url"
                        class="mt-5 inline-flex items-center justify-center rounded-md bg-(--button-primary-bg) px-4 py-2 text-sm font-medium text-(--button-primary-text) inset-ring inset-ring-(--button-primary-border) hover:bg-(--button-primary-hover-bg) hover:text-(--button-primary-hover-text) focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2"
                        @click="emit('close', 'action')"
                    >
                        {{ action.label }}
                    </a>
                </div>
                <button
                    type="button"
                    :aria-label="t('announcementsPopup.close')"
                    class="absolute right-3 top-3 rounded-full bg-surface/80 p-1.5 text-base-secondary transition-colors hover:bg-muted hover:text-base-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                    @click="emit('close', 'dismiss')"
                >
                    <X class="h-5 w-5" aria-hidden="true" />
                </button>
            </div>
        </div>
        <div
            v-else
            ref="panel"
            role="dialog"
            :aria-labelledby="titleId"
            :aria-describedby="bodyId"
            tabindex="-1"
            class="fixed inset-x-4 bottom-4 z-[60] max-h-[70vh] overflow-y-auto rounded-lg border border-(--color-border) bg-surface text-base-primary shadow-xl focus:outline-none sm:left-auto sm:right-4 sm:w-96 motion-safe:animate-[announcement-popup-in_200ms_ease-out]"
        >
            <img
                v-if="imageUrl"
                :src="imageUrl"
                alt=""
                class="max-h-40 w-full rounded-t-lg object-cover"
                loading="lazy"
            />
            <div class="p-4">
                <h2 :id="titleId" class="pr-8 font-semibold text-base-primary">
                    {{ announcement.title }}
                </h2>
                <!-- eslint-disable vue/no-v-html -->
                <div
                    :id="bodyId"
                    class="prose prose-sm mt-1 max-w-none text-base-secondary"
                    v-html="announcement.content"
                />
                <!-- eslint-enable vue/no-v-html -->
                <a
                    v-if="action"
                    :href="action.url"
                    class="mt-4 inline-flex items-center justify-center rounded-md bg-(--button-primary-bg) px-4 py-2 text-sm font-medium text-(--button-primary-text) inset-ring inset-ring-(--button-primary-border) hover:bg-(--button-primary-hover-bg) hover:text-(--button-primary-hover-text) focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2"
                    @click="emit('close', 'action')"
                >
                    {{ action.label }}
                </a>
            </div>
            <button
                type="button"
                :aria-label="t('announcementsPopup.close')"
                class="absolute right-2 top-2 rounded-full bg-surface/80 p-1.5 text-base-secondary transition-colors hover:bg-muted hover:text-base-primary focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                @click="emit('close', 'dismiss')"
            >
                <X class="h-5 w-5" aria-hidden="true" />
            </button>
        </div>
    </Teleport>
</template>
