<script setup>
import { ref } from 'vue';
import Icon from '../Atoms/Icon.vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'info',
        validator: (val) => ['info', 'success', 'warning', 'danger'].includes(val),
    },
    title: {
        type: String,
        default: '',
    },
    message: {
        type: String,
        default: '',
    },
    dismissible: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['dismiss']);
const isDismissed = ref(false);

const handleDismiss = () => {
    isDismissed.value = true;
    emit('dismiss');
};

const variantConfig = {
    info: {
        containerClass: 'bg-blue-50/90 border-blue-200 text-blue-900 dark:bg-blue-950/40 dark:border-blue-800/80 dark:text-blue-200',
        iconName: 'info',
        iconClass: 'text-blue-600 dark:text-blue-400',
    },
    success: {
        containerClass: 'bg-emerald-50/90 border-emerald-200 text-emerald-900 dark:bg-emerald-950/40 dark:border-emerald-800/80 dark:text-emerald-200',
        iconName: 'check-circle',
        iconClass: 'text-emerald-600 dark:text-emerald-400',
    },
    warning: {
        containerClass: 'bg-amber-50/90 border-amber-200 text-amber-900 dark:bg-amber-950/40 dark:border-amber-800/80 dark:text-amber-200',
        iconName: 'warning',
        iconClass: 'text-amber-600 dark:text-amber-400',
    },
    danger: {
        containerClass: 'bg-rose-50/90 border-rose-200 text-rose-900 dark:bg-rose-950/40 dark:border-rose-800/80 dark:text-rose-200',
        iconName: 'danger',
        iconClass: 'text-rose-600 dark:text-rose-400',
    },
};
</script>

<template>
    <div
        v-if="!isDismissed"
        role="alert"
        :class="[
            'relative flex items-start gap-3 rounded-xl border p-3.5 sm:p-4 text-xs sm:text-sm shadow-xs transition-all',
            variantConfig[variant].containerClass,
        ]"
    >
        <div class="mt-0.5 shrink-0">
            <Icon
                :name="variantConfig[variant].iconName"
                :class="['h-4 w-4 sm:h-5 sm:w-5', variantConfig[variant].iconClass]"
            />
        </div>

        <div class="flex-1 min-w-0 pr-6 sm:pr-0">
            <h4 v-if="title" class="font-bold tracking-tight mb-0.5">
                {{ title }}
            </h4>
            <div class="leading-relaxed opacity-95">
                <slot>{{ message }}</slot>
            </div>
        </div>

        <button
            v-if="dismissible"
            type="button"
            @click="handleDismiss"
            class="absolute top-2.5 right-2.5 sm:relative sm:top-auto sm:right-auto inline-flex min-h-[44px] min-w-[44px] sm:min-h-[28px] sm:min-w-[28px] items-center justify-center rounded-lg p-1 opacity-70 hover:opacity-100 hover:bg-black/5 dark:hover:bg-white/10 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-1 cursor-pointer"
            title="Tutup pesan"
            aria-label="Tutup pesan"
        >
            <Icon name="close" class="h-4 w-4" />
        </button>
    </div>
</template>
