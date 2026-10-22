<script setup>
import { ref } from 'vue';
import Button from '../Atoms/Button.vue';
import Icon from '../Atoms/Icon.vue';
import SearchInput from '../Molecules/SearchInput.vue';

defineProps({
    search: {
        type: String,
        default: '',
    },
    searchPlaceholder: {
        type: String,
        default: 'Cari data...',
    },
    activeFiltersCount: {
        type: Number,
        default: 0,
    },
});

defineEmits(['update:search', 'reset']);

const mobileFiltersOpen = ref(false);
</script>

<template>
    <div class="mb-5 space-y-3">
        <!-- TOP ROW: Search & Mobile Filter Toggle -->
        <div
            class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="w-full sm:max-w-md">
                <SearchInput
                    :model-value="search"
                    @update:model-value="$emit('update:search', $event)"
                    :placeholder="searchPlaceholder"
                />
            </div>

            <div class="flex items-center gap-2">
                <!-- Mobile Filters Toggle Button (< md) -->
                <div v-if="$slots.filters" class="w-full sm:w-auto md:hidden">
                    <Button
                        type="button"
                        variant="secondary"
                        size="sm"
                        class="w-full justify-between"
                        @click="mobileFiltersOpen = !mobileFiltersOpen"
                    >
                        <template #icon-left>
                            <Icon name="filter" class="h-4 w-4" />
                        </template>
                        <span>Filter Data</span>
                        <template #icon-right>
                            <span
                                v-if="activeFiltersCount > 0"
                                class="py-0.2 rounded-full bg-blue-600 px-1.5 text-[10px] font-bold text-white dark:bg-blue-600"
                            >
                                {{ activeFiltersCount }}
                            </span>
                            <Icon
                                :name="
                                    mobileFiltersOpen
                                        ? 'chevron-up'
                                        : 'chevron-down'
                                "
                                class="ms-1 h-4 w-4 text-slate-400"
                            />
                        </template>
                    </Button>
                </div>

                <!-- Desktop Extra Actions Slot -->
                <slot name="actions" />
            </div>
        </div>

        <!-- FILTERS CONTAINER -->
        <!-- Desktop (md:flex) or Mobile when toggled open -->
        <div
            v-if="$slots.filters"
            :class="[
                'rounded-xl border border-slate-200/80 bg-slate-50/70 p-3 dark:border-slate-700/60 dark:bg-slate-800/50',
                mobileFiltersOpen ? 'block' : 'hidden md:block',
            ]"
        >
            <div
                class="flex flex-col gap-2.5 md:flex-row md:flex-wrap md:items-center md:justify-between"
            >
                <div
                    class="flex flex-col flex-wrap gap-2.5 sm:flex-row sm:items-center"
                >
                    <slot name="filters" />
                </div>

                <!-- Reset Filters Button -->
                <div
                    v-if="activeFiltersCount > 0 || search"
                    class="flex justify-end pt-1 md:pt-0"
                >
                    <Button
                        type="button"
                        variant="ghost"
                        size="xs"
                        @click="$emit('reset')"
                        class="text-slate-500 hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-400"
                    >
                        <template #icon-left>
                            <Icon name="close" class="h-3.5 w-3.5" />
                        </template>
                        Reset Filter
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
