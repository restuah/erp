<script setup>
import { Link } from '@inertiajs/vue3';
import Icon from '../Atoms/Icon.vue';

defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    breadcrumbs: {
        type: Array,
        default: () => [], // [{ label: 'Dashboard', href: '/dashboard' }, { label: 'Supplier' }]
    },
});
</script>

<template>
    <div
        class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <div>
            <!-- Breadcrumbs -->
            <nav
                v-if="breadcrumbs.length > 0"
                class="mb-1.5 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400"
            >
                <template v-for="(crumb, idx) in breadcrumbs" :key="idx">
                    <Link
                        v-if="crumb.href"
                        :href="crumb.href"
                        class="transition-colors hover:text-blue-600 dark:hover:text-blue-400"
                    >
                        {{ crumb.label }}
                    </Link>
                    <span
                        v-else
                        class="font-medium text-slate-700 dark:text-slate-200"
                    >
                        {{ crumb.label }}
                    </span>
                    <Icon
                        v-if="idx < breadcrumbs.length - 1"
                        name="chevron-right"
                        class="h-3 w-3 text-slate-400"
                    />
                </template>
            </nav>

            <!-- Page Title -->
            <h1
                class="flex items-center gap-2.5 text-xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-2xl"
            >
                <span>{{ title }}</span>
                <slot name="badge" />
            </h1>

            <!-- Subtitle -->
            <p
                v-if="subtitle"
                class="mt-1 text-xs text-slate-500 dark:text-slate-400 sm:text-sm"
            >
                {{ subtitle }}
            </p>
        </div>

        <!-- Actions Slot -->
        <div
            v-if="$slots.actions"
            class="flex shrink-0 flex-wrap items-center gap-2 sm:gap-3"
        >
            <slot name="actions" />
        </div>
    </div>
</template>
