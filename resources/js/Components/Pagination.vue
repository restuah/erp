<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div
        v-if="links.length > 3"
        class="flex flex-wrap items-center justify-center gap-1.5 pt-4"
    >
        <template v-for="(link, key) in links" :key="key">
            <div
                v-if="link.url === null"
                class="select-none rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-400 dark:border-gray-700 dark:text-gray-500"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                class="rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                :class="[
                    link.active
                        ? 'shadow-xs border-indigo-600 bg-indigo-600 text-white dark:border-indigo-500 dark:bg-indigo-500'
                        : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-indigo-400',
                ]"
            >
                <span v-html="link.label" />
            </Link>
        </template>
    </div>
</template>
