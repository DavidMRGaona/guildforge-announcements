<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import type {
    Announcement,
    AnnouncementPosition,
    AnnouncementSettings,
} from '../types/announcement';

interface Props {
    announcements: Announcement[];
    settings?: Partial<AnnouncementSettings>;
    slotPosition?: AnnouncementPosition;
}

const props = withDefaults(defineProps<Props>(), {
    settings: () => ({
        show_banner: true,
        banner_position: 'top',
        auto_rotate: true,
        rotate_interval: 5000,
    }),
    slotPosition: 'before-header',
});

const emit = defineEmits<{
    dismiss: [id: string];
}>();

const STORAGE_KEY = 'guildforge_dismissed_announcements';

/**
 * Load dismissed announcement IDs from localStorage (synchronously)
 */
function getStoredDismissedIds(): Set<string> {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            const ids = JSON.parse(stored) as string[];
            return new Set(ids);
        }
    } catch {
        // Ignore localStorage errors
    }
    return new Set();
}

const currentIndex = ref(0);
const isVisible = ref(true);
// Load dismissed IDs synchronously to prevent flash
const dismissedIds = ref<Set<string>>(getStoredDismissedIds());
let rotationInterval: ReturnType<typeof setInterval> | null = null;

/**
 * Save dismissed announcement IDs to localStorage
 */
function saveDismissedIds(): void {
    try {
        const ids = Array.from(dismissedIds.value);
        localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
    } catch {
        // Ignore localStorage errors
    }
}

const visibleAnnouncements = computed(() =>
    props.announcements.filter(
        (a) =>
            !dismissedIds.value.has(a.id) && a.position === props.slotPosition
    )
);

const currentAnnouncement = computed(() =>
    visibleAnnouncements.value[currentIndex.value] ?? null
);

const hasMultiple = computed(() => visibleAnnouncements.value.length > 1);

const mergedSettings = computed<AnnouncementSettings>(() => ({
    show_banner: props.settings?.show_banner ?? true,
    banner_position: props.settings?.banner_position ?? 'top',
    auto_rotate: props.settings?.auto_rotate ?? true,
    rotate_interval: props.settings?.rotate_interval ?? 5000,
}));

function nextAnnouncement(): void {
    if (hasMultiple.value) {
        currentIndex.value =
            (currentIndex.value + 1) % visibleAnnouncements.value.length;
    }
}

function previousAnnouncement(): void {
    if (hasMultiple.value) {
        currentIndex.value =
            (currentIndex.value - 1 + visibleAnnouncements.value.length) %
            visibleAnnouncements.value.length;
    }
}

function goToAnnouncement(index: number): void {
    currentIndex.value = index;
    resetRotation();
}

function dismissCurrent(): void {
    if (currentAnnouncement.value && currentAnnouncement.value.is_dismissible) {
        const id = currentAnnouncement.value.id;
        dismissedIds.value.add(id);
        saveDismissedIds();
        emit('dismiss', id);

        if (currentIndex.value >= visibleAnnouncements.value.length) {
            currentIndex.value = Math.max(
                0,
                visibleAnnouncements.value.length - 1
            );
        }
    }
}

/**
 * Check if the current announcement can be dismissed
 */
const canDismissCurrent = computed(() => {
    return currentAnnouncement.value?.is_dismissible ?? false;
});

function startRotation(): void {
    if (
        mergedSettings.value.auto_rotate &&
        hasMultiple.value &&
        !rotationInterval
    ) {
        rotationInterval = setInterval(
            nextAnnouncement,
            mergedSettings.value.rotate_interval
        );
    }
}

function stopRotation(): void {
    if (rotationInterval) {
        clearInterval(rotationInterval);
        rotationInterval = null;
    }
}

function resetRotation(): void {
    stopRotation();
    startRotation();
}

function getPriorityClasses(priority: number): string {
    // Banners use solid colors for high visibility
    if (priority >= 8) return 'bg-error text-white';
    if (priority >= 5) return 'bg-warning text-white';
    return 'bg-info text-white';
}

function getAnnouncementStyle(announcement: Announcement | null): Record<string, string> {
    if (!announcement) return {};

    const style: Record<string, string> = {};

    if (announcement.background_color) {
        style.backgroundColor = announcement.background_color;
    }

    if (announcement.text_color) {
        style.color = announcement.text_color;
    }

    return style;
}

function hasCustomColors(announcement: Announcement | null): boolean {
    if (!announcement) return false;
    return Boolean(announcement.background_color || announcement.text_color);
}

function stripHtml(html: string): string {
    return html.replace(/<[^>]*>/g, '');
}

watch(
    () => visibleAnnouncements.value.length,
    (newLength) => {
        if (newLength === 0) {
            isVisible.value = false;
            stopRotation();
        } else if (newLength === 1) {
            stopRotation();
        } else {
            startRotation();
        }
    }
);

onMounted(() => {
    if (mergedSettings.value.auto_rotate && hasMultiple.value) {
        startRotation();
    }
});

onUnmounted(() => {
    stopRotation();
});
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        leave-active-class="transition-all duration-200 ease-in"
        enter-from-class="opacity-0 -translate-y-full"
        enter-to-class="opacity-100 translate-y-0"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-full"
    >
        <div
            v-if="
                isVisible &&
                visibleAnnouncements.length > 0 &&
                mergedSettings.show_banner
            "
            :class="[
                'w-full shadow-lg',
                !hasCustomColors(currentAnnouncement)
                    ? getPriorityClasses(currentAnnouncement?.priority ?? 5)
                    : '',
            ]"
            :style="getAnnouncementStyle(currentAnnouncement)"
            @mouseenter="stopRotation"
            @mouseleave="startRotation"
        >
            <div class="max-w-7xl mx-auto px-4 py-3">
                <div class="flex items-center justify-between gap-4">
                    <!-- Navigation arrows (left) -->
                    <button
                        v-if="hasMultiple"
                        type="button"
                        class="p-1 rounded-full hover:bg-white/20 transition-colors flex-shrink-0"
                        @click="previousAnnouncement"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 19l-7-7 7-7"
                            />
                        </svg>
                    </button>

                    <!-- Content -->
                    <div class="flex-1 min-w-0 text-center">
                        <Transition
                            mode="out-in"
                            enter-active-class="transition-opacity duration-200"
                            leave-active-class="transition-opacity duration-200"
                            enter-from-class="opacity-0"
                            enter-to-class="opacity-100"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                        >
                            <div
                                v-if="currentAnnouncement"
                                :key="currentAnnouncement.id"
                            >
                                <p class="font-semibold">
                                    {{ currentAnnouncement.title }}
                                </p>
                                <p class="text-sm opacity-90 line-clamp-1">
                                    {{ stripHtml(currentAnnouncement.content) }}
                                </p>
                            </div>
                        </Transition>
                    </div>

                    <!-- Navigation arrows (right) -->
                    <button
                        v-if="hasMultiple"
                        type="button"
                        class="p-1 rounded-full hover:bg-white/20 transition-colors flex-shrink-0"
                        @click="nextAnnouncement"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </button>

                    <!-- Close button (only if dismissible) -->
                    <button
                        v-if="canDismissCurrent"
                        type="button"
                        class="p-1 rounded-full hover:bg-white/20 transition-colors flex-shrink-0"
                        @click="dismissCurrent"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Dots indicator -->
                <div
                    v-if="hasMultiple"
                    class="flex justify-center gap-1.5 mt-2"
                >
                    <button
                        v-for="(_, index) in visibleAnnouncements"
                        :key="index"
                        type="button"
                        :class="[
                            'w-2 h-2 rounded-full transition-all',
                            index === currentIndex
                                ? 'bg-white'
                                : 'bg-white/40 hover:bg-white/60',
                        ]"
                        @click="goToAnnouncement(index)"
                    />
                </div>
            </div>
        </div>
    </Transition>
</template>
