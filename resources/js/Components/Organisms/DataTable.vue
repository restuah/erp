<script setup>
import EmptyState from '../Molecules/EmptyState.vue';
import TableSkeleton from '../Molecules/TableSkeleton.vue';
import CardSkeleton from '../Molecules/CardSkeleton.vue';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    skeletonRows: {
        type: Number,
        default: 5,
    },
    skeletonCols: {
        type: Number,
        default: 5,
    },
    stickyHeader: {
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

defineEmits(['empty-action', 'sort']);
</script>

<template>
    <div class="w-full">
        <!-- 1. ZERO-CLS SKELETON LOADING STATE -->
        <div v-if="loading" class="space-y-4">
            <slot name="loading">
                <!-- Mobile Card Skeletons (< md) -->
                <div v-if="$slots.card" class="block md:hidden">
                    <CardSkeleton :cards="Math.min(skeletonRows, 4)" />
                </div>

                <!-- Desktop Table Skeleton (>= md, or always if no card slot) -->
                <div
                    :class="[
                        'shadow-xs overflow-hidden rounded-xl border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900',
                        $slots.card ? 'hidden md:block' : 'block',
                    ]"
                >
                    <div class="overflow-x-auto">
                        <table class="w-full text-start text-xs sm:text-sm">
                            <thead
                                v-if="$slots.head"
                                class="select-none border-b border-slate-200/90 bg-slate-50/90 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-850/90 dark:text-slate-400"
                            >
                                <slot name="head" />
                            </thead>
                            <TableSkeleton
                                :columns="skeletonCols"
                                :rows="skeletonRows"
                            />
                        </table>
                    </div>
                </div>
            </slot>
        </div>

        <!-- 2. EMPTY STATE -->
        <div
            v-else-if="!items || items.length === 0"
            class="shadow-xs rounded-xl border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900"
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

        <!-- 3. DATA VIEW -->
        <div v-else class="space-y-4">
            <!-- MOBILE CARD LIST VIEW (< md) -->
            <div v-if="$slots.card" class="space-y-3 md:hidden">
                <div
                    v-for="(item, index) in items"
                    :key="item.id || index"
                    class="shadow-xs hover:shadow-xs rounded-xl border border-slate-200/90 bg-white p-4 transition-all duration-150 hover:border-blue-200 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-900/50"
                >
                    <slot name="card" :item="item" :index="index" />
                </div>
            </div>

            <!-- DESKTOP / TABLET DATA TABLE (>= md, or always if no card slot) -->
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
                            :class="[
                                'select-none border-b border-slate-200/90 bg-slate-50/90 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-850/90 dark:text-slate-400',
                                stickyHeader ? 'sticky top-0 z-10 backdrop-blur-sm' : '',
                            ]"
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
