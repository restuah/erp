<script setup>
defineProps({
    width: {
        type: String,
        default: 'w-full',
    },
    height: {
        type: String,
        default: 'h-4',
    },
    rounded: {
        type: String,
        default: 'rounded-md',
    },
    circle: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div
        :class="[
            'animate-shimmer shrink-0 bg-slate-200/80 dark:bg-slate-800/80',
            circle ? 'rounded-full' : rounded,
            width,
            height,
        ]"
        aria-hidden="true"
    />
</template>
