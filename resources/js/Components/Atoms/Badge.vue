<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'neutral',
        validator: (value) =>
            [
                'success',
                'danger',
                'warning',
                'info',
                'primary',
                'neutral',
            ].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md'].includes(value),
    },
    dot: {
        type: Boolean,
        default: false,
    },
});

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'success':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800/40';
        case 'danger':
            return 'bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800/40';
        case 'warning':
            return 'bg-amber-50 text-amber-800 border-amber-200/80 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800/40';
        case 'info':
            return 'bg-blue-50 text-blue-700 border-blue-200/80 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800/40';
        case 'primary':
            return 'bg-blue-50 text-blue-700 border-blue-200/90 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-900/50';
        case 'neutral':
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200/80 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700/60';
    }
});

const dotClasses = computed(() => {
    switch (props.variant) {
        case 'success':
            return 'bg-emerald-500 dark:bg-emerald-400';
        case 'danger':
            return 'bg-rose-500 dark:bg-rose-400';
        case 'warning':
            return 'bg-amber-500 dark:bg-amber-400';
        case 'info':
            return 'bg-sky-500 dark:bg-sky-400';
        case 'primary':
            return 'bg-blue-600 dark:bg-blue-400';
        case 'neutral':
        default:
            return 'bg-slate-400 dark:bg-slate-500';
    }
});
</script>

<template>
    <span
        :class="[
            'inline-flex select-none items-center rounded-full border font-medium tracking-tight',
            size === 'sm'
                ? 'gap-1 px-2 py-0.5 text-[11px]'
                : 'gap-1.5 px-2.5 py-0.5 text-xs',
            variantClasses,
        ]"
    >
        <span
            v-if="dot"
            :class="[
                'shrink-0 rounded-full',
                size === 'sm' ? 'h-1.5 w-1.5' : 'h-2 w-2',
                dotClasses,
            ]"
            aria-hidden="true"
        />
        <slot />
    </span>
</template>
