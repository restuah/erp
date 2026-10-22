<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import Badge from '../Atoms/Badge.vue';

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
    badgeVariant: {
        type: String,
        default: 'primary',
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
        'group flex items-center gap-3 rounded-lg px-3 py-2 text-[13px] font-medium transition-all duration-150 relative';
    if (props.active) {
        return `${base} bg-blue-50/90 text-blue-700 font-semibold shadow-xs dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200/80 dark:border-blue-900/50 before:absolute before:start-0 before:top-1.5 before:bottom-1.5 before:w-1 before:rounded-r-full before:bg-blue-600 dark:before:bg-blue-400`;
    }
    return `${base} text-slate-600 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-200 border border-transparent`;
});

const iconClasses = computed(() => {
    if (props.active) {
        return 'text-blue-600 dark:text-blue-400 shrink-0';
    }
    return 'text-slate-400 group-hover:text-slate-700 dark:text-slate-400 dark:group-hover:text-slate-200 shrink-0 transition-colors';
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
        <Badge
            v-if="!collapsed && badge"
            class="ms-auto"
            size="sm"
            :variant="badgeVariant"
        >
            {{ badge }}
        </Badge>

        <!-- Active indicator bar for collapsed state -->
        <span
            v-if="collapsed && active"
            class="absolute -start-1 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-blue-600 dark:bg-blue-400"
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
        <Badge
            v-if="!collapsed && badge"
            class="ms-auto"
            size="sm"
            variant="neutral"
        >
            {{ badge }}
        </Badge>
    </div>
</template>
