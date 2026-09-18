<script setup>
import { computed, onMounted, ref, useAttrs } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const model = defineModel({
    type: [String, Number],
    default: '',
});

const attrs = useAttrs();
const showPassword = ref(false);
const input = ref(null);

// Extract layout/spacing classes for the outer wrapper container
const wrapperClass = computed(() => {
    const raw = attrs.class;
    if (!raw) return '';
    if (typeof raw === 'string') {
        return raw
            .split(/\s+/)
            .filter((c) =>
                /^(m[tblrxy]?-\S+|w-\S+|max-w-\S+|min-w-\S+|block|inline-block|inline|flex|inline-flex|grid|col-span-\S+)/.test(
                    c,
                ),
            )
            .join(' ');
    }
    return '';
});

// Extract input styling classes (e.g., text size, padding, custom borders)
const inputClass = computed(() => {
    const raw = attrs.class;
    if (!raw) return '';
    if (typeof raw === 'string') {
        return raw
            .split(/\s+/)
            .filter(
                (c) =>
                    !/^(m[tblrxy]?-\S+|block|inline-block|inline|flex|inline-flex|grid|col-span-\S+)/.test(
                        c,
                    ),
            )
            .join(' ');
    }
    return raw;
});

// Sanitize attributes to avoid duplicate class bindings
const sanitizedAttrs = computed(() => {
    const rest = { ...attrs };
    delete rest.class;
    return rest;
});

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({
    focus: () => input.value?.focus(),
    input,
});
</script>

<template>
    <div :class="['relative', wrapperClass]">
        <slot name="prefix" />

        <input
            v-bind="sanitizedAttrs"
            ref="input"
            :type="showPassword ? 'text' : 'password'"
            v-model="model"
            class="block w-full rounded-md border-gray-300 pr-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
            :class="inputClass"
        />

        <button
            type="button"
            @click="showPassword = !showPassword"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 transition-colors hover:text-gray-600 focus:outline-none dark:text-gray-400 dark:hover:text-gray-200"
            :title="
                showPassword ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'
            "
            :aria-label="
                showPassword ? 'Sembunyikan kata sandi' : 'Lihat kata sandi'
            "
            tabindex="-1"
        >
            <!-- Show password icon (eye) -->
            <svg
                v-if="!showPassword"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"
                />
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
            </svg>

            <!-- Hide password icon (eye-slash) -->
            <svg
                v-else
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"
                />
            </svg>
        </button>
    </div>
</template>
