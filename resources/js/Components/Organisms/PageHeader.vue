<script setup>
import Breadcrumbs from '../Molecules/Breadcrumbs.vue';

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
            <!-- Breadcrumbs Molecule -->
            <div v-if="breadcrumbs && breadcrumbs.length > 0" class="mb-2">
                <Breadcrumbs :items="breadcrumbs" />
            </div>

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
