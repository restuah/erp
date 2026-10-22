<script setup>
import Button from '@/Components/Atoms/Button.vue';
import Icon from '@/Components/Atoms/Icon.vue';
import InputLabel from '@/Components/Atoms/InputLabel.vue';
import TextInput from '@/Components/Atoms/TextInput.vue';
import ModalDialog from '@/Components/Organisms/ModalDialog.vue';

const props = defineProps({
    show: Boolean,
    activeDay: Object,
    form: Object,
});

const emit = defineEmits(['close', 'submit']);

const formatDateIndo = (dateStr) => {
    if (!dateStr) return '';
    try {
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            const months = [
                'Januari',
                'Februari',
                'Maret',
                'April',
                'Mei',
                'Juni',
                'Juli',
                'Agustus',
                'September',
                'Oktober',
                'November',
                'Desember',
            ];
            const d = parseInt(parts[2], 10);
            const m = months[parseInt(parts[1], 10) - 1];
            const y = parts[0];
            return `${d} ${m} ${y}`;
        }
        return dateStr;
    } catch {
        return dateStr;
    }
};

const formatDateTimeIndo = (dtStr) => {
    if (!dtStr) return '-';
    try {
        const d = new Date(dtStr);
        return d.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dtStr;
    }
};
</script>

<template>
    <ModalDialog
        :show="show"
        @close="$emit('close')"
        title="Atur Status Hari Kalender"
        :subtitle="
            activeDay
                ? `${activeDay.day_name}, ${formatDateIndo(activeDay.date)}`
                : ''
        "
        max-width="lg"
    >
        <form @submit.prevent="$emit('submit')" class="space-y-4">
            <!-- Pilihan Status Kerja vs Libur -->
            <div>
                <InputLabel
                    value="Tentukan Status Hari Ini"
                    class="mb-2 font-bold"
                />
                <div class="grid grid-cols-2 gap-3">
                    <label
                        :class="[
                            'flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition-all',
                            form.is_working_day
                                ? 'border-emerald-500 bg-emerald-50/50 ring-2 ring-emerald-500/20 dark:border-emerald-500/80 dark:bg-emerald-950/30'
                                : 'border-slate-200 bg-white hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800',
                        ]"
                    >
                        <input
                            type="radio"
                            :value="true"
                            v-model="form.is_working_day"
                            class="h-4 w-4 text-emerald-600 focus:ring-emerald-500"
                        />
                        <div>
                            <span
                                class="block text-xs font-bold text-slate-900 dark:text-white"
                            >
                                Hari Kerja
                            </span>
                            <span
                                class="block text-[11px] text-slate-500 dark:text-slate-400"
                            >
                                Operasional aktif normal
                            </span>
                        </div>
                    </label>

                    <label
                        :class="[
                            'flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition-all',
                            !form.is_working_day
                                ? 'border-rose-500 bg-rose-50/50 ring-2 ring-rose-500/20 dark:border-rose-500/80 dark:bg-rose-950/30'
                                : 'border-slate-200 bg-white hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800',
                        ]"
                    >
                        <input
                            type="radio"
                            :value="false"
                            v-model="form.is_working_day"
                            class="h-4 w-4 text-rose-600 focus:ring-rose-500"
                        />
                        <div>
                            <span
                                class="block text-xs font-bold text-rose-600 dark:text-rose-400"
                            >
                                Hari Libur
                            </span>
                            <span
                                class="block text-[11px] text-slate-500 dark:text-slate-400"
                            >
                                Libur / cuti / akhir pekan
                            </span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Input Keterangan / Nama Libur -->
            <div>
                <InputLabel
                    for="day_name"
                    value="Keterangan / Nama Libur / Catatan Tambahan"
                />
                <TextInput
                    id="day_name"
                    type="text"
                    v-model="form.name"
                    class="mt-1 block w-full text-xs"
                    placeholder="Contoh: Libur Cuti Bersama Perusahaan, Hari Pengganti Lembur, dll."
                />
                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                    Opsional untuk hari kerja biasa, sangat disarankan bila
                    ditetapkan sebagai libur khusus.
                </p>
            </div>

            <!-- Audit Info Banner -->
            <div
                v-if="activeDay && activeDay.updater"
                class="rounded-xl border border-amber-200 bg-amber-50/80 p-3 text-xs dark:border-amber-800/50 dark:bg-amber-950/30"
            >
                <div class="flex items-start gap-2.5">
                    <Icon
                        name="info"
                        class="mt-0.5 h-4 w-4 shrink-0 text-amber-600"
                    />
                    <div
                        class="space-y-0.5 text-[11px] text-amber-900 dark:text-amber-200"
                    >
                        <p>
                            Terakhir diubah oleh:
                            <strong class="font-bold">{{
                                activeDay.updater.name
                            }}</strong>
                        </p>
                        <p
                            class="text-[10px] text-amber-600 dark:text-amber-400"
                        >
                            Pada: {{ formatDateTimeIndo(activeDay.updated_at) }}
                        </p>
                    </div>
                </div>
            </div>
        </form>

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
                    :disabled="form.processing"
                    @click="$emit('submit')"
                >
                    <span v-if="form.processing">Menyimpan...</span>
                    <span v-else>Simpan Perubahan</span>
                </Button>
            </div>
        </template>
    </ModalDialog>
</template>
