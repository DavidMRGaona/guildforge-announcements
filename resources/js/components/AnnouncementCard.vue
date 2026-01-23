<script setup lang="ts">
import type { Announcement } from '../types/announcement';

interface Props {
    announcement: Announcement;
    showCloseButton?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showCloseButton: false,
});

const emit = defineEmits<{
    close: [id: string];
}>();

function getPriorityColor(priority: number): string {
    if (priority >= 8) return 'bg-red-100 dark:bg-red-900/30 border-red-300 dark:border-red-700';
    if (priority >= 5) return 'bg-amber-100 dark:bg-amber-900/30 border-amber-300 dark:border-amber-700';
    return 'bg-blue-100 dark:bg-blue-900/30 border-blue-300 dark:border-blue-700';
}

function getPriorityTextColor(priority: number): string {
    if (priority >= 8) return 'text-red-800 dark:text-red-200';
    if (priority >= 5) return 'text-amber-800 dark:text-amber-200';
    return 'text-blue-800 dark:text-blue-200';
}

function handleClose(): void {
    emit('close', props.announcement.id);
}
</script>

<template>
    <div
        :class="[
            'rounded-lg border p-4 transition-all',
            getPriorityColor(announcement.priority),
        ]"
    >
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <h3
                    :class="[
                        'font-semibold text-lg mb-2',
                        getPriorityTextColor(announcement.priority),
                    ]"
                >
                    {{ announcement.title }}
                </h3>
                <div
                    :class="[
                        'prose prose-sm max-w-none',
                        getPriorityTextColor(announcement.priority),
                    ]"
                    v-html="announcement.content"
                />
            </div>
            <button
                v-if="showCloseButton"
                type="button"
                :class="[
                    'flex-shrink-0 p-1 rounded-full hover:bg-black/10 dark:hover:bg-white/10 transition-colors',
                    getPriorityTextColor(announcement.priority),
                ]"
                @click="handleClose"
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
    </div>
</template>
