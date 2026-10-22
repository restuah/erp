<script setup>
import { useForm } from '@inertiajs/vue3';
import Button from '@/Components/Atoms/Button.vue';
import Icon from '@/Components/Atoms/Icon.vue';
import InputLabel from '@/Components/Atoms/InputLabel.vue';
import ModalDialog from '@/Components/Organisms/ModalDialog.vue';

const props = defineProps({
    show: Boolean,
    currentYear: Number,
    yearsList: Array,
});

const emit = defineEmits(['close']);

const syncForm = useForm({
    year: props.currentYear,
});

const submitSync = () => {
    syncForm.post(route('calendar.sync'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>

<template>
    <ModalDialog
        :show="show"
        @close="$emit('close')"
        title="Sinkronisasi API Tanggal Merah"
        subtitle="Ambil hari libur resmi & cuti bersama dari API nasional"
        max-width="md"
    >
        <div class="space-y-4 text-xs text-slate-600 dark:text-slate-300">
            <div
                class="rounded-xl border border-blue-100 bg-blue-50/80 p-3.5 text-blue-900 dark:border-blue-900/50 dark:bg-blue-950/40 dark:text-blue-300"
            >
                <div
                    class="flex items-center gap-2 font-bold text-blue-800 dark:text-blue-200"
                >
                    <Icon name="info" class="h-4 w-4" />
                    <span>Sumber Data API Resmi</span>
                </div>
                <p class="mt-1 text-[11px] leading-relaxed">
                    Sistem akan mengambil data hari libur nasional dan cuti
                    bersama dari API publik (<code
                        class="rounded bg-blue-100/80 px-1 py-0.5 font-mono text-[10px] dark:bg-blue-900/80"
                        >tanggalmerah.upset.dev</code
                    >) untuk memperbarui kalender kerja operasional.
                </p>
            </div>

            <div>
                <InputLabel
                    for="sync_year"
                    value="Pilih Tahun Kalender untuk Disinkronkan"
                    class="mb-1 font-semibold"
                />
                <select
                    id="sync_year"
                    v-model="syncForm.year"
                    class="shadow-2xs block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                >
                    <option v-for="y in yearsList" :key="y" :value="y">
                        Tahun {{ y }}
                    </option>
                </select>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-end gap-2">
                <Button
                    variant="secondary"
                    type="button"
                    @click="$emit('close')"
                >
                    Batal
                </Button>
                <Button
                    variant="primary"
                    type="button"
                    :disabled="syncForm.processing"
                    @click="submitSync"
                >
                    <Icon
                        v-if="!syncForm.processing"
                        name="refresh"
                        class="me-1.5 h-4 w-4"
                    />
                    <span v-if="syncForm.processing">Menyinkronkan...</span>
                    <span v-else>Mulai Sinkronisasi</span>
                </Button>
            </div>
        </template>
    </ModalDialog>
</template>
