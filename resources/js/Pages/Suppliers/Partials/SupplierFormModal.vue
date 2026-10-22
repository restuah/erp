<script setup>
/* eslint-disable vue/no-mutating-props */
import { ref, watch } from 'vue';
import Button from '@/Components/Atoms/Button.vue';
import Icon from '@/Components/Atoms/Icon.vue';
import InputLabel from '@/Components/Atoms/InputLabel.vue';
import SelectInput from '@/Components/Atoms/SelectInput.vue';
import TextInput from '@/Components/Atoms/TextInput.vue';
import TextareaInput from '@/Components/Atoms/TextareaInput.vue';
import FormField from '@/Components/Molecules/FormField.vue';
import Tabs from '@/Components/Molecules/Tabs.vue';
import ModalDialog from '@/Components/Organisms/ModalDialog.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Tambah Supplier Baru',
    },
    form: {
        type: Object,
        required: true,
    },
    termOfPayments: {
        type: Array,
        default: () => [],
    },
    currencies: {
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

defineEmits(['close', 'submit', 'type-change', 'refresh-code']);

const activeTab = ref('general');

const tabs = [
    { id: 'general', label: '1. Profil Umum', icon: 'building' },
    { id: 'address', label: '2. Alamat & Kontak Kantor', icon: 'building' },
    { id: 'contact', label: '3. PIC Marketing & Accounting', icon: 'user' },
    { id: 'finance', label: '4. Termin & Bank', icon: 'dollar' },
    { id: 'delivery', label: '5. Pengiriman', icon: 'calendar' },
];

watch(
    () => props.show,
    (val) => {
        if (val) {
            activeTab.value = 'general';
        }
    },
);
</script>

<template>
    <ModalDialog
        :show="show"
        max-width="3xl"
        :title="title"
        @close="$emit('close')"
    >
        <!-- Tabs Navigation Molecule -->
        <div class="mb-5">
            <Tabs v-model="activeTab" :tabs="tabs" />
        </div>

        <form @submit.prevent="$emit('submit')" class="space-y-4">
            <!-- TAB 1: PROFIL UMUM -->
            <div v-show="activeTab === 'general'" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Supplier Type -->
                    <div>
                        <InputLabel value="Tipe Supplier" required />
                        <div class="mt-1 flex items-center gap-3">
                            <label
                                :class="[
                                    'flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-xl border p-2.5 text-xs font-semibold transition-all',
                                    form.supplier_type === 'direct'
                                        ? 'shadow-xs border-blue-600 bg-blue-50 font-bold text-blue-700 dark:border-blue-500 dark:bg-blue-950/40 dark:text-blue-300'
                                        : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300',
                                ]"
                            >
                                <input
                                    type="radio"
                                    value="direct"
                                    v-model="form.supplier_type"
                                    @change="$emit('type-change', 'direct')"
                                    class="sr-only"
                                />
                                <span>Direct (Bahan Baku)</span>
                            </label>

                            <label
                                :class="[
                                    'flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-xl border p-2.5 text-xs font-semibold transition-all',
                                    form.supplier_type === 'indirect'
                                        ? 'shadow-xs border-amber-600 bg-amber-50 font-bold text-amber-700 dark:border-amber-500 dark:bg-amber-950/40 dark:text-amber-300'
                                        : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300',
                                ]"
                            >
                                <input
                                    type="radio"
                                    value="indirect"
                                    v-model="form.supplier_type"
                                    @change="$emit('type-change', 'indirect')"
                                    class="sr-only"
                                />
                                <span>Indirect (Jasa & Umum)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <InputLabel value="Status Pemasok" required />
                        <SelectInput
                            v-model="form.status"
                            :error="!!form.errors.status"
                            class="mt-1"
                        >
                            <option value="active">
                                Active (Siap Transaksi)
                            </option>
                            <option value="nonactive">
                                Nonactive (Ditangguhkan)
                            </option>
                        </SelectInput>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Supplier Code -->
                    <FormField
                        label="Kode Supplier"
                        required
                        :error="form.errors.supplier_code"
                    >
                        <div class="flex gap-2">
                            <TextInput
                                v-model="form.supplier_code"
                                placeholder="Contoh: DIR-001"
                                :error="!!form.errors.supplier_code"
                                class="font-mono font-bold uppercase"
                            />
                            <Button
                                type="button"
                                variant="secondary"
                                size="md"
                                :loading="isGeneratingCode"
                                @click="$emit('refresh-code')"
                                title="Buat kode otomatis berikutnya"
                            >
                                <Icon name="refresh" class="h-4 w-4" />
                            </Button>
                        </div>
                    </FormField>

                    <!-- NPWP -->
                    <FormField
                        label="NPWP (Opsional)"
                        :error="form.errors.npwp"
                    >
                        <TextInput
                            v-model="form.npwp"
                            placeholder="Contoh: 01.234.567.8-901.000"
                            :error="!!form.errors.npwp"
                        />
                    </FormField>
                </div>

                <!-- Supplier Name -->
                <FormField
                    label="Nama Perusahaan Supplier"
                    required
                    :error="form.errors.supplier_name"
                >
                    <TextInput
                        v-model="form.supplier_name"
                        placeholder="Contoh: PT Sumber Berkah Mandiri"
                        :error="!!form.errors.supplier_name"
                    />
                </FormField>
            </div>

            <!-- TAB 2: ALAMAT & KONTAK KANTOR -->
            <div v-show="activeTab === 'address'" class="space-y-4">
                <FormField
                    label="Alamat Lengkap Kantor / Pabrik"
                    :error="form.errors.address"
                >
                    <TextareaInput
                        v-model="form.address"
                        rows="3"
                        placeholder="Alamat jalan, gedung, nomor, kelurahan, kecamatan, kota/kabupaten..."
                        :error="!!form.errors.address"
                    />
                </FormField>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField label="Kode Pos" :error="form.errors.zipcode">
                        <TextInput
                            v-model="form.zipcode"
                            placeholder="Contoh: 12345"
                            :error="!!form.errors.zipcode"
                        />
                    </FormField>

                    <FormField
                        label="Kode Negara"
                        :error="form.errors.country_code"
                    >
                        <TextInput
                            v-model="form.country_code"
                            placeholder="ID"
                            :error="!!form.errors.country_code"
                            class="font-mono uppercase"
                        />
                    </FormField>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField
                        label="No. Telepon Kantor"
                        :error="form.errors.office_phone"
                    >
                        <TextInput
                            v-model="form.office_phone"
                            placeholder="Contoh: (021) 555-1234"
                            :error="!!form.errors.office_phone"
                        />
                    </FormField>

                    <FormField label="Fax" :error="form.errors.fax">
                        <TextInput
                            v-model="form.fax"
                            placeholder="Contoh: (021) 555-4321"
                            :error="!!form.errors.fax"
                        />
                    </FormField>
                </div>
            </div>

            <!-- TAB 3: PIC MARKETING & ACCOUNTING -->
            <div v-show="activeTab === 'contact'" class="space-y-5">
                <!-- Marketing PIC Card -->
                <div
                    class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4 dark:border-slate-700/60 dark:bg-slate-800/40"
                >
                    <h4
                        class="mb-3 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400"
                    >
                        <Icon name="user" class="h-4 w-4" />
                        <span>Person In Charge (PIC) Marketing / Sales</span>
                    </h4>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <FormField
                            label="Nama PIC Marketing"
                            :error="form.errors.marketing_name"
                        >
                            <TextInput
                                v-model="form.marketing_name"
                                placeholder="Nama sales"
                                size="sm"
                            />
                        </FormField>
                        <FormField
                            label="No. Telepon / WhatsApp"
                            :error="form.errors.marketing_phone"
                        >
                            <TextInput
                                v-model="form.marketing_phone"
                                placeholder="08xxxxxxxxxx"
                                size="sm"
                            />
                        </FormField>
                        <FormField
                            label="Email Marketing"
                            :error="form.errors.marketing_email"
                        >
                            <TextInput
                                v-model="form.marketing_email"
                                type="email"
                                placeholder="sales@vendor.com"
                                size="sm"
                            />
                        </FormField>
                    </div>
                </div>

                <!-- Accounting PIC Card -->
                <div
                    class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4 dark:border-slate-700/60 dark:bg-slate-800/40"
                >
                    <h4
                        class="mb-3 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400"
                    >
                        <Icon name="dollar" class="h-4 w-4" />
                        <span>Person In Charge (PIC) Accounting / Finance</span>
                    </h4>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <FormField
                            label="Nama PIC Accounting"
                            :error="form.errors.accounting_name"
                        >
                            <TextInput
                                v-model="form.accounting_name"
                                placeholder="Nama finance"
                                size="sm"
                            />
                        </FormField>
                        <FormField
                            label="No. Telepon / WhatsApp"
                            :error="form.errors.accounting_phone"
                        >
                            <TextInput
                                v-model="form.accounting_phone"
                                placeholder="08xxxxxxxxxx"
                                size="sm"
                            />
                        </FormField>
                        <FormField
                            label="Email Accounting"
                            :error="form.errors.accounting_email"
                        >
                            <TextInput
                                v-model="form.accounting_email"
                                type="email"
                                placeholder="finance@vendor.com"
                                size="sm"
                            />
                        </FormField>
                    </div>
                </div>
            </div>

            <!-- TAB 4: TERMIN & REKENING BANK -->
            <div v-show="activeTab === 'finance'" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField
                        label="Term of Payment (TOP)"
                        :error="form.errors.term_of_payment_id"
                    >
                        <SelectInput
                            v-model="form.term_of_payment_id"
                            :error="!!form.errors.term_of_payment_id"
                        >
                            <option value="">
                                -- Pilih Termin Pembayaran --
                            </option>
                            <option
                                v-for="top in termOfPayments"
                                :key="top.id"
                                :value="top.id"
                            >
                                {{ top.payment }} - {{ top.name }} ({{
                                    top.days
                                }}
                                Hari)
                            </option>
                        </SelectInput>
                    </FormField>

                    <FormField
                        label="Mata Uang Default"
                        :error="form.errors.default_currency"
                    >
                        <SelectInput
                            v-model="form.default_currency"
                            :error="!!form.errors.default_currency"
                        >
                            <option
                                v-for="c in currencies"
                                :key="c.id"
                                :value="c.code"
                            >
                                {{ c.code }} - {{ c.name }}
                            </option>
                        </SelectInput>
                    </FormField>
                </div>

                <!-- Bank Info Box -->
                <div
                    class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4 dark:border-slate-700/60 dark:bg-slate-800/40"
                >
                    <h4
                        class="mb-3 flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400"
                    >
                        <Icon name="dollar" class="h-4 w-4" />
                        <span>Informasi Rekening Bank Pembayaran Faktur</span>
                    </h4>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <FormField
                            label="Nama Bank"
                            :error="form.errors.bank_name"
                        >
                            <TextInput
                                v-model="form.bank_name"
                                placeholder="BCA / Mandiri / BNI"
                                size="sm"
                            />
                        </FormField>
                        <FormField
                            label="Nomor Rekening"
                            :error="form.errors.bank_account_number"
                        >
                            <TextInput
                                v-model="form.bank_account_number"
                                placeholder="Nomor rekening"
                                size="sm"
                                class="font-mono"
                            />
                        </FormField>
                        <FormField
                            label="Atas Nama (A.N.) Rekening"
                            :error="form.errors.bank_account_name"
                        >
                            <TextInput
                                v-model="form.bank_account_name"
                                placeholder="Nama pemilik rekening"
                                size="sm"
                            />
                        </FormField>
                    </div>
                </div>
            </div>

            <!-- TAB 5: PENGIRIMAN & LOGISTIK -->
            <div v-show="activeTab === 'delivery'" class="space-y-4">
                <FormField
                    label="Estimasi / Periode Pengiriman"
                    :error="form.errors.delivery_period"
                    hint="Contoh: 3-5 Hari Kerja setelah PO disetujui"
                >
                    <TextInput
                        v-model="form.delivery_period"
                        placeholder="Contoh: 3-5 Hari Kerja"
                    />
                </FormField>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField
                        label="Jam Awal Pengiriman"
                        :error="form.errors.delivery_time"
                    >
                        <TextInput v-model="form.delivery_time" type="time" />
                    </FormField>

                    <FormField
                        label="Jam Akhir Pengiriman"
                        :error="form.errors.delivery_end_time"
                    >
                        <TextInput
                            v-model="form.delivery_end_time"
                            type="time"
                        />
                    </FormField>
                </div>
            </div>
        </form>

        <!-- Footer Actions -->
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
                {{ isEdit ? 'Simpan Perubahan' : 'Tambahkan Supplier' }}
            </Button>
        </template>
    </ModalDialog>
</template>
