<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    links: {
        type: Array,
        required: true,
    },
});

const prevLink = computed(() => {
    return props.links && props.links.length > 0 ? props.links[0] : null;
});

const nextLink = computed(() => {
    return props.links && props.links.length > 0
        ? props.links[props.links.length - 1]
        : null;
});

const activeLink = computed(() => {
    return props.links ? props.links.find((l) => l.active) : null;
});
</script>

<template>
    <div v-if="links && links.length > 3" class="w-full pt-4">
        <!-- MOBILE-FIRST COMPACT PAGINATION (< sm) -->
        <div class="flex items-center justify-between sm:hidden">
            <div>
                <component
                    :is="prevLink?.url ? Link : 'span'"
                    :href="prevLink?.url || undefined"
                    :class="[
                        'shadow-xs inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200',
                        !prevLink?.url &&
                            'pointer-events-none cursor-not-allowed opacity-50',
                    ]"
                >
                    <svg
                        class="h-4 w-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                    <span>Sebelumnya</span>
                </component>
            </div>

            <div
                v-if="activeLink"
                class="text-xs font-semibold text-slate-600 dark:text-slate-400"
            >
                Halaman
                <span class="font-bold text-slate-900 dark:text-white">{{
                    activeLink.label
                }}</span>
            </div>

            <div>
                <component
                    :is="nextLink?.url ? Link : 'span'"
                    :href="nextLink?.url || undefined"
                    :class="[
                        'shadow-xs inline-flex min-h-[44px] items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200',
                        !nextLink?.url &&
                            'pointer-events-none cursor-not-allowed opacity-50',
                    ]"
                >
                    <span>Berikutnya</span>
                    <svg
                        class="h-4 w-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </component>
            </div>
        </div>

        <!-- DESKTOP / TABLET FULL PAGINATION (>= sm) -->
        <div class="hidden gap-1.5 sm:flex sm:items-center sm:justify-center">
            <template v-for="(link, key) in links" :key="key">
                <div
                    v-if="link.url === null"
                    class="select-none rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-400 dark:border-slate-800 dark:text-slate-500"
                    v-html="link.label"
                />
                <Link
                    v-else
                    :href="link.url"
                    class="rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
                    :class="[
                        link.active
                            ? 'shadow-xs border-blue-600 bg-blue-600 font-bold text-white dark:border-blue-500 dark:bg-blue-500'
                            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:text-blue-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-blue-400',
                    ]"
                >
                    <span v-html="link.label" />
                </Link>
            </template>
        </div>
    </div>
</template>
