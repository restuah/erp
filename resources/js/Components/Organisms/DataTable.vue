<script setup>
import EmptyState from '../Molecules/EmptyState.vue';
import Spinner from '../Atoms/Spinner.vue';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    emptyTitle: {
        type: String,
        default: 'Tidak Ada Data',
    },
    emptyDescription: {
        type: String,
        default: 'Belum ada data yang cocok dengan kriteria pencarian.',
    },
    emptyIcon: {
        type: String,
        default: 'document',
    },
    emptyActionText: {
        type: String,
        default: null,
    },
});

defineEmits(['empty-action']);
</script>

<template>
    <div class="w-full">
        <!-- LOADING STATE -->
        <div
            v-if="loading"
            class="flex flex-col items-center justify-center py-16 text-slate-400 dark:text-slate-500"
        >
            <Spinner size="h-8 w-8" class="text-blue-600 dark:text-blue-400" />
            <p
                class="mt-3 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-400"
            >
                Memuat Data Enterprise...
            </p>
        </div>

        <!-- EMPTY STATE -->
        <div
            v-else-if="!items || items.length === 0"
            class="rounded-xl border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900"
        >
            <EmptyState
                :title="emptyTitle"
                :description="emptyDescription"
                :icon-name="emptyIcon"
                :action-text="emptyActionText"
                @action="$emit('empty-action')"
            >
                <template v-if="$slots['empty-action']" #action>
                    <slot name="empty-action" />
                </template>
            </EmptyState>
        </div>

        <!-- DATA VIEW -->
        <div v-else class="space-y-4">
            <!-- 1. MOBILE CARD LIST VIEW (< md) -->
            <div v-if="$slots.card" class="space-y-3 md:hidden">
                <div
                    v-for="(item, index) in items"
                    :key="item.id || index"
                    class="shadow-xs hover:shadow-xs rounded-xl border border-slate-200/90 bg-white p-4 transition-all duration-150 hover:border-blue-200 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-900/50"
                >
                    <slot name="card" :item="item" :index="index" />
                </div>
            </div>

            <!-- 2. DESKTOP / TABLET DATA TABLE (>= md, or always if no card slot) -->
            <div
                :class="[
                    'shadow-xs overflow-hidden rounded-xl border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900',
                    $slots.card ? 'hidden md:block' : 'block',
                ]"
            >
                <div class="overflow-x-auto">
                    <table
                        class="w-full text-start text-xs text-slate-700 dark:text-slate-200 sm:text-sm"
                    >
                        <!-- Table Head -->
                        <thead
                            class="select-none border-b border-slate-200/90 bg-slate-50/90 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-850/90 dark:text-slate-400"
                        >
                            <slot name="head" />
                        </thead>

                        <!-- Table Body -->
                        <tbody
                            class="divide-y divide-slate-100 dark:divide-slate-800/80"
                        >
                            <slot name="body" :items="items">
                                <tr
                                    v-for="(item, index) in items"
                                    :key="item.id || index"
                                    class="transition-colors hover:bg-blue-50/25 dark:hover:bg-slate-800/50"
                                >
                                    <slot
                                        name="row"
                                        :item="item"
                                        :index="index"
                                    />
                                </tr>
                            </slot>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PAGINATION / FOOTER SLOT -->
            <div v-if="$slots.pagination" class="pt-1">
                <slot name="pagination" />
            </div>
        </div>
    </div>
</template>
