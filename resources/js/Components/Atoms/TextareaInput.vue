<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    rows: {
        type: [String, Number],
        default: 3,
    },
    placeholder: {
        type: String,
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
});

defineEmits(['update:modelValue']);

const textarea = ref(null);

onMounted(() => {
    if (textarea.value?.hasAttribute('autofocus')) {
        textarea.value.focus();
    }
});

defineExpose({ focus: () => textarea.value?.focus() });
</script>

<template>
    <textarea
        ref="textarea"
        :rows="rows"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        @input="$emit('update:modelValue', $event.target.value)"
        :class="[
            'w-full rounded-lg bg-white px-3.5 py-2 text-sm text-slate-900 transition-colors duration-150 placeholder:text-slate-400 focus:outline-none dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-500',
            error
                ? 'border-rose-500 focus:border-rose-600 focus:ring-2 focus:ring-rose-500/20 dark:border-rose-500/80'
                : 'border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-600 dark:focus:border-blue-500',
            disabled
                ? 'cursor-not-allowed bg-slate-50 opacity-60 dark:bg-slate-800/50'
                : '',
        ]"
    />
</template>
