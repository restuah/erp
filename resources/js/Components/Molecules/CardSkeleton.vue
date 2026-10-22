<script setup>
import Skeleton from '../Atoms/Skeleton.vue';

defineProps({
    cards: {
        type: Number,
        default: 3,
    },
});
</script>

<template>
    <div class="space-y-3 md:hidden">
        <div
            v-for="i in cards"
            :key="i"
            class="rounded-xl border border-slate-200/90 bg-white p-4 shadow-xs dark:border-slate-800 dark:bg-slate-900"
        >
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                <Skeleton width="w-20" height="h-4" />
                <Skeleton width="w-16" height="h-5" rounded="rounded-full" />
            </div>

            <div class="mt-3 space-y-2">
                <Skeleton width="w-3/4" height="h-4" />
                <Skeleton width="w-1/2" height="h-3" />
            </div>

            <div class="mt-4 flex items-center justify-end gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                <Skeleton width="w-16" height="h-8" rounded="rounded-lg" />
                <Skeleton width="w-16" height="h-8" rounded="rounded-lg" />
            </div>
        </div>
    </div>
</template>
