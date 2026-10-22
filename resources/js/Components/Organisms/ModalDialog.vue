<script setup>
import Icon from '../Atoms/Icon.vue';
import Modal from '../Atoms/Modal.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    subtitle: {
        type: String,
        default: '',
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['close']);
</script>

<template>
    <Modal
        :show="show"
        :max-width="maxWidth"
        :closeable="closeable"
        @close="$emit('close')"
    >
        <div
            class="flex max-h-[90vh] flex-col overflow-hidden rounded-2xl bg-white dark:bg-slate-800"
        >
            <!-- Modal Header -->
            <div
                class="flex shrink-0 items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700/80"
            >
                <div>
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-white sm:text-lg"
                    >
                        <slot name="title">{{ title }}</slot>
                    </h3>
                    <p
                        v-if="subtitle"
                        class="mt-0.5 text-xs text-slate-500 dark:text-slate-400"
                    >
                        {{ subtitle }}
                    </p>
                </div>

                <button
                    v-if="closeable"
                    type="button"
                    @click="$emit('close')"
                    class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 focus:outline-none dark:hover:bg-slate-700 dark:hover:text-slate-200"
                    title="Tutup dialog"
                >
                    <Icon name="close" class="h-5 w-5" />
                </button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="flex-1 overflow-y-auto overscroll-contain p-5 sm:p-6">
                <slot />
            </div>

            <!-- Modal Footer -->
            <div
                v-if="$slots.footer"
                class="flex shrink-0 flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50/70 px-5 py-3.5 dark:border-slate-700/80 dark:bg-slate-800/80 sm:flex-row sm:items-center sm:justify-end sm:gap-3"
            >
                <slot name="footer" />
            </div>
        </div>
    </Modal>
</template>
