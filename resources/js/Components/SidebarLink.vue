<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        default: '#',
    },
    active: {
        type: Boolean,
        default: false,
    },
    collapsed: {
        type: Boolean,
        default: false,
    },
    badge: {
        type: [String, Number],
        default: null,
    },
    badgeColor: {
        type: String,
        default: 'blue',
    },
    title: {
        type: String,
        default: '',
    },
});

const isPlaceholder = computed(() => props.href === '#');

const linkClasses = computed(() => {
    const base =
        'group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150 relative';
    if (props.active) {
        return `${base} bg-indigo-50 text-indigo-700 font-semibold shadow-xs dark:bg-indigo-950/50 dark:text-indigo-300 dark:border dark:border-indigo-800/40`;
    }
    return `${base} text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/60 dark:hover:text-gray-200`;
});

const iconClasses = computed(() => {
    if (props.active) {
        return 'text-indigo-600 dark:text-indigo-400 shrink-0';
    }
    return 'text-gray-400 group-hover:text-gray-700 dark:text-gray-400 dark:group-hover:text-gray-200 shrink-0 transition-colors';
});
</script>

<template>
    <Link
        v-if="!isPlaceholder"
        :href="href"
        :class="[linkClasses, collapsed ? 'justify-center px-2' : '']"
        :title="title"
    >
        <span :class="iconClasses">
            <slot name="icon" />
        </span>
        <span v-if="!collapsed" class="truncate">
            <slot />
        </span>
        <span
            v-if="!collapsed && badge"
            class="ms-auto rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider"
            :class="{
                'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300':
                    badgeColor === 'blue' || badgeColor === 'indigo',
                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300':
                    badgeColor === 'emerald' || badgeColor === 'green',
                'bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-300':
                    badgeColor === 'amber' || badgeColor === 'yellow',
                'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300':
                    badgeColor === 'gray',
            }"
        >
            {{ badge }}
        </span>

        <!-- Active indicator bar for collapsed state -->
        <span
            v-if="collapsed && active"
            class="absolute -start-1 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-indigo-600 dark:bg-indigo-400"
        />
    </Link>

    <div
        v-else
        :class="[
            linkClasses,
            collapsed ? 'justify-center px-2' : '',
            'cursor-not-allowed opacity-70',
        ]"
        :title="title || 'Fitur dalam pengembangan'"
    >
        <span :class="iconClasses">
            <slot name="icon" />
        </span>
        <span v-if="!collapsed" class="truncate">
            <slot />
        </span>
        <span
            v-if="!collapsed && badge"
            class="ms-auto rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-700 dark:text-gray-400"
        >
            {{ badge }}
        </span>
    </div>
</template>
