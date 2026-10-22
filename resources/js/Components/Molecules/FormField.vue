<script setup>
import InputError from '../Atoms/InputError.vue';
import InputLabel from '../Atoms/InputLabel.vue';

defineProps({
    label: {
        type: String,
        default: '',
    },
    id: {
        type: String,
        default: null,
    },
    required: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: '',
    },
    hint: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="w-full">
        <InputLabel
            v-if="label"
            :for="id"
            :value="label"
            :required="required"
        />
        <slot />
        <p
            v-if="hint && !error"
            class="mt-1 text-xs text-slate-500 dark:text-slate-400"
        >
            {{ hint }}
        </p>
        <InputError :message="error" />
    </div>
</template>
