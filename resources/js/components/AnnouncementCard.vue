<script setup lang="ts">
import { X } from '@lucide/vue';
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
    if (priority >= 8) return 'bg-error-light border-error';
    if (priority >= 5) return 'bg-warning-light border-warning';
    return 'bg-info-light border-info';
}

function getPriorityTextColor(priority: number): string {
    if (priority >= 8) return 'text-base-primary';
    if (priority >= 5) return 'text-base-primary';
    return 'text-base-primary';
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
                    'flex-shrink-0 p-1 rounded-full hover:bg-base-secondary/10 transition-colors',
                    getPriorityTextColor(announcement.priority),
                ]"
                @click="handleClose"
            >
                <X class="w-5 h-5" aria-hidden="true" />
            </button>
        </div>
    </div>
</template>
