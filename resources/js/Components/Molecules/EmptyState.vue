<script setup>
import Button from '../Atoms/Button.vue';
import Icon from '../Atoms/Icon.vue';

defineProps({
    title: {
        type: String,
        default: 'Tidak Ada Data',
    },
    description: {
        type: String,
        default: 'Belum ada data yang tersedia untuk ditampilkan.',
    },
    iconName: {
        type: String,
        default: 'document',
    },
    actionText: {
        type: String,
        default: null,
    },
});

defineEmits(['action']);
</script>

<template>
    <div
        class="flex flex-col items-center justify-center px-4 py-12 text-center"
    >
        <div
            class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500"
        >
            <Icon :name="iconName" class="h-7 w-7" />
        </div>
        <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">
            {{ title }}
        </h3>
        <p
            class="mt-1 max-w-sm text-xs text-slate-500 dark:text-slate-400 sm:text-sm"
        >
            {{ description }}
        </p>
        <div v-if="actionText || $slots.action" class="mt-5">
            <slot name="action">
                <Button variant="primary" size="sm" @click="$emit('action')">
                    <template #icon-left>
                        <Icon name="plus" class="h-4 w-4" />
                    </template>
                    {{ actionText }}
                </Button>
            </slot>
        </div>
    </div>
</template>
