<script setup>
/* eslint-disable vue/no-mutating-props */
import Button from '@/Components/Atoms/Button.vue';
import Checkbox from '@/Components/Atoms/Checkbox.vue';
import Icon from '@/Components/Atoms/Icon.vue';
import InputLabel from '@/Components/Atoms/InputLabel.vue';
import SelectInput from '@/Components/Atoms/SelectInput.vue';
import TextInput from '@/Components/Atoms/TextInput.vue';
import TextareaInput from '@/Components/Atoms/TextareaInput.vue';
import FormField from '@/Components/Molecules/FormField.vue';
import ModalDialog from '@/Components/Organisms/ModalDialog.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Tambah Budget Baru',
    },
    form: {
        type: Object,
        required: true,
    },
    users: {
        type: Array,
        default: () => [],
    },
    classifications: {
        type: Array,
        default: () => [],
    },
    isGeneratingCode: {
        type: Boolean,
        default: false,
    },
    isEdit: {
        type: Boolean,
        default: false,
    },
});

defineEmits([
    'close',
    'submit',
    'refresh-code',
    'add-checker',
    'remove-checker',
]);
</script>

<template>
    <ModalDialog
        :show="show"
        max-width="3xl"
        :title="title"
        @close="$emit('close')"
    >
        <form @submit.prevent="$emit('submit')" class="space-y-4">
            <!-- Row 1: Code & Classification -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Kode Budget"
                    required
                    :error="form.errors.code"
                >
                    <div class="flex gap-2">
                        <TextInput
                            v-model="form.code"
                            placeholder="BDG-2026-0001"
                            :error="!!form.errors.code"
                            class="font-mono font-bold uppercase"
                        />
                        <Button
                            type="button"
                            variant="secondary"
                            size="md"
                            :loading="isGeneratingCode"
                            @click="$emit('refresh-code')"
                            title="Buat kode otomatis"
                        >
                            <Icon name="refresh" class="h-4 w-4" />
                        </Button>
                    </div>
                </FormField>

                <FormField
                    label="Klasifikasi Budget"
                    required
                    :error="form.errors.budget_classification_id"
                >
                    <SelectInput
                        v-model="form.budget_classification_id"
                        :error="!!form.errors.budget_classification_id"
                    >
                        <option value="">-- Pilih Klasifikasi --</option>
                        <option
                            v-for="c in classifications"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.name }}
                        </option>
                    </SelectInput>
                </FormField>
            </div>

            <!-- Row 2: Name & PIC -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Nama Budget"
                    required
                    :error="form.errors.name"
                >
                    <TextInput
                        v-model="form.name"
                        placeholder="Contoh: Operasional IT & Software"
                        :error="!!form.errors.name"
                    />
                </FormField>

                <FormField
                    label="PIC Budget (Penanggung Jawab)"
                    required
                    :error="form.errors.pic_id"
                >
                    <SelectInput
                        v-model="form.pic_id"
                        :error="!!form.errors.pic_id"
                    >
                        <option value="">-- Pilih Karyawan PIC --</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">
                            {{ u.name }} ({{ u.email }})
                        </option>
                    </SelectInput>
                </FormField>
            </div>

            <!-- Description -->
            <FormField
                label="Deskripsi / Catatan"
                :error="form.errors.description"
            >
                <TextareaInput
                    v-model="form.description"
                    rows="2"
                    placeholder="Keterangan peruntukan budget ini..."
                    :error="!!form.errors.description"
                />
            </FormField>

            <!-- Status Checkbox -->
            <div class="flex items-center gap-2 pt-1">
                <Checkbox
                    id="budget_is_active"
                    v-model:checked="form.is_active"
                />
                <InputLabel
                    for="budget_is_active"
                    value="Budget Aktif (Dapat digunakan untuk pengajuan pengeluaran)"
                    class="mb-0 cursor-pointer"
                />
            </div>

            <!-- Dynamic Checkers / Approval Flow Section -->
            <div
                class="border-t border-slate-200 pt-3 dark:border-slate-700/80"
            >
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h4
                            class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400"
                        >
                            <Icon name="user" class="h-4 w-4" />
                            <span>Alur Persetujuan (Tim Checker)</span>
                        </h4>
                        <p
                            class="text-[11px] text-slate-500 dark:text-slate-400"
                        >
                            Urutan pemeriksaan berjenjang saat budget ini
                            diajukan.
                        </p>
                    </div>

                    <Button
                        type="button"
                        variant="secondary"
                        size="xs"
                        @click="$emit('add-checker')"
                    >
                        <template #icon-left>
                            <Icon name="plus" class="h-3.5 w-3.5" />
                        </template>
                        Tambah Tingkat
                    </Button>
                </div>

                <div class="space-y-2.5">
                    <div
                        v-for="(checker, index) in form.checkers"
                        :key="index"
                        class="flex items-center gap-2.5 rounded-xl border border-slate-200/80 bg-slate-50/70 p-3 dark:border-slate-700/60 dark:bg-slate-800/40"
                    >
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300"
                        >
                            {{ index + 1 }}
                        </span>

                        <div
                            class="grid flex-1 grid-cols-1 gap-2 sm:grid-cols-2"
                        >
                            <TextInput
                                v-model="checker.role_title"
                                placeholder="Judul Peran (cth: Manager Divisi)"
                                size="sm"
                            />
                            <SelectInput v-model="checker.user_id" size="sm">
                                <option value="">-- Pilih Pemeriksa --</option>
                                <option
                                    v-for="u in users"
                                    :key="u.id"
                                    :value="u.id"
                                >
                                    {{ u.name }}
                                </option>
                            </SelectInput>
                        </div>

                        <button
                            v-if="form.checkers.length > 1"
                            type="button"
                            @click="$emit('remove-checker', index)"
                            class="rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-600 focus:outline-none dark:hover:bg-rose-950/40 dark:hover:text-rose-400"
                            title="Hapus tingkat checker ini"
                        >
                            <Icon name="trash" class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <template #footer>
            <Button variant="secondary" size="md" @click="$emit('close')">
                Batal
            </Button>
            <Button
                variant="primary"
                size="md"
                :loading="form.processing"
                @click="$emit('submit')"
            >
                <template #icon-left>
                    <Icon name="check" class="h-4 w-4" />
                </template>
                {{ isEdit ? 'Simpan Perubahan' : 'Buat Budget' }}
            </Button>
        </template>
    </ModalDialog>
</template>
