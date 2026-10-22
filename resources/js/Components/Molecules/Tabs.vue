<script setup>
import Icon from '../Atoms/Icon.vue';

defineProps({
    tabs: {
        type: Array,
        required: true, // [{ id: 'general', label: 'Umum', icon: 'building', badge: 0 }]
    },
    modelValue: {
        type: String,
        required: true,
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="border-b border-slate-200 dark:border-slate-700/80">
        <nav
            class="no-scrollbar -mb-px flex space-x-2 overflow-x-auto sm:space-x-4"
            aria-label="Tabs"
        >
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                @click="$emit('update:modelValue', tab.id)"
                :class="[
                    'group inline-flex cursor-pointer select-none items-center gap-2 whitespace-nowrap border-b-2 px-3 py-3 text-xs font-semibold transition-all duration-150 sm:text-sm',
                    modelValue === tab.id
                        ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:text-slate-200',
                ]"
            >
                <Icon
                    v-if="tab.icon"
                    :name="tab.icon"
                    :class="[
                        'h-4 w-4 shrink-0 transition-colors',
                        modelValue === tab.id
                            ? 'text-blue-600 dark:text-blue-400'
                            : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300',
                    ]"
                />
                <span>{{ tab.label }}</span>
                <span
                    v-if="tab.badge"
                    class="py-0.2 rounded-full bg-rose-100 px-1.5 text-[10px] font-bold text-rose-700 dark:bg-rose-950 dark:text-rose-300"
                >
                    {{ tab.badge }}
                </span>
            </button>
        </nav>
    </div>
</template>
