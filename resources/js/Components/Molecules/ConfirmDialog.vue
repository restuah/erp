<script setup>
import Button from '../Atoms/Button.vue';
import Icon from '../Atoms/Icon.vue';
import Modal from '../Atoms/Modal.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Konfirmasi Tindakan',
    },
    message: {
        type: String,
        default:
            'Apakah Anda yakin ingin melanjutkan tindakan ini? Data yang dihapus mungkin tidak dapat dikembalikan.',
    },
    confirmText: {
        type: String,
        default: 'Ya, Hapus Data',
    },
    cancelText: {
        type: String,
        default: 'Batal',
    },
    confirmVariant: {
        type: String,
        default: 'danger',
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close', 'confirm']);
</script>

<template>
    <Modal :show="show" max-width="md" @close="$emit('close')">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400"
                >
                    <Icon name="trash" class="h-5 w-5" />
                </div>
                <div>
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-white"
                    >
                        {{ title }}
                    </h3>
                    <p
                        class="mt-2 text-xs leading-relaxed text-slate-500 dark:text-slate-400 sm:text-sm"
                    >
                        {{ message }}
                    </p>
                </div>
            </div>

            <div
                class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3"
            >
                <Button
                    variant="secondary"
                    size="sm"
                    @click="$emit('close')"
                    :disabled="loading"
                >
                    {{ cancelText }}
                </Button>
                <Button
                    :variant="confirmVariant"
                    size="sm"
                    :loading="loading"
                    @click="$emit('confirm')"
                >
                    {{ confirmText }}
                </Button>
            </div>
        </div>
    </Modal>
</template>
