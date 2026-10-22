<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '../Atoms/Icon.vue';

defineProps({
    items: {
        type: Array,
        required: true,
        // Each item: { label: String, href?: String, icon?: String }
    },
    showHomeIcon: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <nav aria-label="Breadcrumb" class="flex items-center text-xs">
        <ol class="flex flex-wrap items-center gap-1.5 text-slate-500 dark:text-slate-400">
            <li
                v-for="(item, idx) in items"
                :key="idx"
                class="inline-flex items-center gap-1.5"
            >
                <!-- First item home icon optional -->
                <Icon
                    v-if="idx === 0 && showHomeIcon && !item.icon"
                    name="home"
                    class="h-3.5 w-3.5 text-slate-400 dark:text-slate-500"
                />
                <Icon
                    v-else-if="item.icon"
                    :name="item.icon"
                    class="h-3.5 w-3.5 text-slate-400 dark:text-slate-500"
                />

                <!-- Clickable Link -->
                <Link
                    v-if="item.href && idx < items.length - 1"
                    :href="item.href"
                    class="font-medium max-w-[140px] sm:max-w-none truncate text-slate-500 hover:text-blue-600 focus:outline-none focus:underline dark:text-slate-400 dark:hover:text-blue-400 transition-colors"
                >
                    {{ item.label }}
                </Link>

                <!-- Current Active Page (Last Item or non-link) -->
                <span
                    v-else
                    :aria-current="idx === items.length - 1 ? 'page' : undefined"
                    class="font-semibold max-w-[180px] sm:max-w-none truncate text-slate-800 dark:text-slate-200"
                >
                    {{ item.label }}
                </span>

                <!-- Separator Chevron -->
                <Icon
                    v-if="idx < items.length - 1"
                    name="chevron-right"
                    class="h-3 w-3 text-slate-300 dark:text-slate-600 shrink-0"
                    aria-hidden="true"
                />
            </li>
        </ol>
    </nav>
</template>
