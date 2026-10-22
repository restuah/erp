<script setup>
import { computed } from 'vue';
import Spinner from './Spinner.vue';

const props = defineProps({
    type: {
        type: String,
        default: 'submit',
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) =>
            [
                'primary',
                'secondary',
                'danger',
                'warning',
                'ghost',
                'outline',
            ].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'primary':
            return 'bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white shadow-xs focus:ring-blue-500/30 dark:bg-blue-600 dark:hover:bg-blue-500 dark:focus:ring-blue-400/30 border border-transparent';
        case 'secondary':
            return 'bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 border border-slate-300 shadow-xs focus:ring-blue-500/20 dark:bg-slate-800 dark:hover:bg-slate-700/80 dark:text-slate-200 dark:border-slate-700 dark:focus:ring-slate-400/30';
        case 'danger':
            return 'bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white shadow-xs focus:ring-rose-500/40 dark:bg-rose-600 dark:hover:bg-rose-500 border border-transparent';
        case 'warning':
            return 'bg-amber-500 hover:bg-amber-600 active:bg-amber-700 text-white shadow-xs focus:ring-amber-500/40 dark:bg-amber-600 dark:hover:bg-amber-500 border border-transparent';
        case 'outline':
            return 'bg-transparent hover:bg-blue-50 active:bg-blue-100 text-blue-600 border border-blue-300 dark:text-blue-400 dark:border-blue-600/60 dark:hover:bg-blue-950/40';
        case 'ghost':
            return 'bg-transparent hover:bg-slate-100 active:bg-slate-200 text-slate-600 dark:text-slate-300 dark:hover:bg-slate-800 dark:active:bg-slate-700/80 border border-transparent';
        default:
            return '';
    }
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'xs':
            return 'px-2.5 py-1 text-xs min-h-[32px] gap-1.5 rounded-md';
        case 'sm':
            return 'px-3 py-1.5 text-xs font-medium min-h-[36px] gap-2 rounded-lg';
        case 'md':
            return 'px-4 py-2 text-sm font-medium min-h-[40px] sm:min-h-[38px] gap-2 rounded-lg';
        case 'lg':
            return 'px-5 py-2.5 text-base font-medium min-h-[44px] gap-2.5 rounded-xl';
        default:
            return 'px-4 py-2 text-sm min-h-[40px] gap-2 rounded-lg';
    }
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="[
            'inline-flex cursor-pointer select-none items-center justify-center font-semibold transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-offset-1',
            variantClasses,
            sizeClasses,
            (disabled || loading) &&
                'pointer-events-none cursor-not-allowed opacity-60 shadow-none',
        ]"
    >
        <Spinner
            v-if="loading"
            class="shrink-0"
            :class="size === 'xs' ? 'h-3.5 w-3.5' : 'h-4 w-4'"
        />
        <slot name="icon-left" />
        <slot />
        <slot name="icon-right" />
    </button>
</template>
