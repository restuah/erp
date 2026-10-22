<script setup>
import { computed, onMounted, ref, useAttrs } from 'vue';

defineOptions({
    inheritAttrs: false,
});

defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    type: {
        type: String,
        default: 'text',
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
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
});

defineEmits(['update:modelValue']);

const attrs = useAttrs();

const inputAttrs = computed(() => {
    const { class: _, style: __, ...rest } = attrs;
    return rest;
});

const input = ref(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <div :class="['relative w-full', attrs.class]" :style="attrs.style">
        <div
            v-if="$slots.prefix"
            class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-slate-400 dark:text-slate-500"
        >
            <slot name="prefix" />
        </div>

        <input
            ref="input"
            v-bind="inputAttrs"
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            @input="$emit('update:modelValue', $event.target.value)"
            :class="[
                'w-full rounded-lg border bg-white text-slate-900 transition-colors duration-150 placeholder:text-slate-400 focus:outline-none dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500',
                size === 'sm'
                    ? 'min-h-[38px] sm:min-h-[34px] px-3 py-2 sm:py-1.5 text-xs'
                    : size === 'lg'
                      ? 'min-h-[48px] sm:min-h-[44px] px-4 py-3 sm:py-2.5 text-base'
                      : 'min-h-[44px] sm:min-h-[38px] px-3.5 py-2.5 sm:py-2 text-sm',
                $slots.prefix ? 'ps-10' : '',
                $slots.suffix ? 'pe-10' : '',
                error
                    ? 'border-rose-500 focus:border-rose-600 focus:ring-2 focus:ring-rose-500/20 dark:border-rose-500/80 dark:focus:ring-rose-500/30'
                    : 'border-slate-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:focus:border-blue-500 dark:focus:ring-blue-500/30',
                disabled
                    ? 'cursor-not-allowed bg-slate-100 opacity-60 dark:bg-slate-800/60'
                    : '',
            ]"
        />

        <div
            v-if="$slots.suffix"
            class="absolute inset-y-0 end-0 flex items-center pe-3 text-slate-400 dark:text-slate-500"
        >
            <slot name="suffix" />
        </div>
    </div>
</template>
