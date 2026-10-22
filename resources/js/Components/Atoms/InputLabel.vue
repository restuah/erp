<script setup>
defineProps({
    value: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <label
        class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-200"
    >
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
        <span
            v-if="required"
            class="ms-0.5 font-bold text-rose-500"
            title="Wajib diisi"
            >*</span
        >
    </label>
</template>
