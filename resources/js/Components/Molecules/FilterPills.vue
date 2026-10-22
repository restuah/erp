<script setup>
defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    options: {
        type: Array,
        required: true, // [{ label: 'Semua', value: '' }, { label: 'Direct', value: 'direct' }]
    },
    size: {
        type: String,
        default: 'sm',
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div
        class="inline-flex flex-wrap items-center gap-1 rounded-xl bg-slate-100 p-1 dark:bg-slate-800/80"
    >
        <button
            v-for="opt in options"
            :key="opt.value"
            type="button"
            @click="$emit('update:modelValue', opt.value)"
            :class="[
                'inline-flex cursor-pointer select-none items-center rounded-lg font-semibold transition-all duration-150',
                size === 'sm' ? 'px-3 py-1 text-xs' : 'px-3.5 py-1.5 text-sm',
                modelValue === opt.value
                    ? 'shadow-xs bg-white font-bold text-blue-600 dark:bg-slate-700 dark:text-blue-400'
                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white',
            ]"
        >
            <span>{{ opt.label }}</span>
            <span
                v-if="opt.count !== undefined"
                :class="[
                    'py-0.2 ms-1.5 rounded-full px-1.5 text-[10px] font-bold',
                    modelValue === opt.value
                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300'
                        : 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
                ]"
            >
                {{ opt.count }}
            </span>
        </button>
    </div>
</template>
