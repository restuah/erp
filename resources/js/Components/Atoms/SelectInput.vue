<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    error: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
});

defineEmits(['update:modelValue']);

const select = ref(null);

onMounted(() => {
    if (select.value?.hasAttribute('autofocus')) {
        select.value.focus();
    }
});

defineExpose({ focus: () => select.value?.focus() });
</script>

<template>
    <select
        ref="select"
        :value="modelValue"
        :disabled="disabled"
        @change="$emit('update:modelValue', $event.target.value)"
        :class="[
            'w-full cursor-pointer rounded-lg bg-white text-slate-900 transition-colors duration-150 focus:outline-none dark:bg-slate-900 dark:text-slate-100',
            size === 'sm'
                ? 'min-h-[38px] sm:min-h-[34px] px-3 py-2 sm:py-1.5 text-xs'
                : size === 'lg'
                  ? 'min-h-[48px] sm:min-h-[44px] px-4 py-3 sm:py-2.5 text-base'
                  : 'min-h-[44px] sm:min-h-[38px] px-3.5 py-2.5 sm:py-2 text-sm',
            error
                ? 'border-rose-500 focus:border-rose-600 focus:ring-2 focus:ring-rose-500/20 dark:border-rose-500/80 dark:focus:ring-rose-500/30'
                : 'border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:focus:border-blue-500 dark:focus:ring-blue-500/30',
            disabled
                ? 'cursor-not-allowed bg-slate-100 opacity-60 dark:bg-slate-800/60'
                : '',
        ]"
    >
        <slot />
    </select>
</template>
