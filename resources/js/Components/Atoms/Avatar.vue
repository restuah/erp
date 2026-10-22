<script setup>
import { computed } from 'vue';

const props = defineProps({
    src: {
        type: String,
        default: null,
    },
    name: {
        type: String,
        default: 'User',
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value),
    },
    status: {
        type: String,
        default: null, // 'online' | 'offline' | 'busy'
    },
});

const initials = computed(() => {
    if (!props.name) return 'U';
    const parts = props.name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return props.name.slice(0, 2).toUpperCase();
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'xs':
            return 'h-6 w-6 text-[10px]';
        case 'sm':
            return 'h-8 w-8 text-xs';
        case 'md':
            return 'h-10 w-10 text-sm';
        case 'lg':
            return 'h-12 w-12 text-base';
        default:
            return 'h-10 w-10 text-sm';
    }
});
</script>

<template>
    <div class="relative inline-flex shrink-0">
        <div
            :class="[
                'flex select-none items-center justify-center overflow-hidden rounded-full font-bold',
                sizeClasses,
                src
                    ? 'bg-slate-100 dark:bg-slate-800'
                    : 'shadow-xs bg-blue-600 text-white dark:bg-blue-600',
            ]"
        >
            <img
                v-if="src"
                :src="src"
                :alt="name"
                class="h-full w-full object-cover"
            />
            <span v-else>{{ initials }}</span>
        </div>

        <span
            v-if="status"
            :class="[
                'absolute bottom-0 end-0 block rounded-full ring-2 ring-white dark:ring-slate-900',
                size === 'xs'
                    ? 'h-1.5 w-1.5'
                    : size === 'sm'
                      ? 'h-2 w-2'
                      : 'h-2.5 w-2.5',
                status === 'online'
                    ? 'bg-emerald-500'
                    : status === 'busy'
                      ? 'bg-rose-500'
                      : 'bg-slate-400',
            ]"
            aria-hidden="true"
        />
    </div>
</template>
