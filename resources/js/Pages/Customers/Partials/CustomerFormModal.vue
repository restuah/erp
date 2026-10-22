<script setup>
/* eslint-disable vue/no-mutating-props */
import { ref, watch } from 'vue';
import Button from '@/Components/Atoms/Button.vue';
import Checkbox from '@/Components/Atoms/Checkbox.vue';
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
        default: 'Tambah Customer Baru',
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
    chartOfAccounts: {
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

const emit = defineEmits(['close', 'submit', 'refresh-code']);

const activeTab = ref('general');

const tabs = [
    { id: 'general', label: '1. Profil Umum & Pajak', icon: 'building' },
    { id: 'address', label: '2. Alamat & Kontak', icon: 'map-pin' },
    { id: 'contact', label: '3. PIC / Kontak Personal', icon: 'user' },
    { id: 'finance', label: '4. Finansial & Rekening Bank', icon: 'dollar' },
];

watch(
    () => props.show,
    (val) => {
        if (val) {
            activeTab.value = 'general';
        }
    },
);

const handleClose = () => {
    emit('close');
};

const handleSubmit = () => {
    emit('submit');
};
</script>

<template>
    <ModalDialog
        :show="show"
        :title="title"
        max-width="3xl"
        @close="handleClose"
    >
        <template #content>
            <form
                @submit.prevent="handleSubmit"
                id="customerForm"
                class="space-y-6"
            >
                <!-- Tabs Navigation -->
                <Tabs
                    v-model="activeTab"
                    :tabs="tabs"
                    variant="pills"
                    class="mb-6"
                />

                <!-- Tab 1: Profil Umum & Pajak -->
                <div v-show="activeTab === 'general'" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Kode Customer -->
                        <div>
                            <InputLabel
                                for="customer_code"
                                value="Kode Customer *"
                            />
                            <div class="mt-1 flex gap-2">
                                <TextInput
                                    id="customer_code"
                                    v-model="form.customer_code"
                                    type="text"
                                    class="w-full font-mono font-bold uppercase"
                                    placeholder="CUST-0001"
                                    :disabled="form.processing"
                                    required
                                />
                                <Button
                                    v-if="!isEdit"
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    :disabled="
                                        isGeneratingCode || form.processing
                                    "
                                    @click="$emit('refresh-code')"
                                    title="Generate Kode Otomatis"
                                >
                                    <Icon
                                        name="refresh"
                                        class="h-4 w-4"
                                        :class="{
                                            'animate-spin': isGeneratingCode,
                                        }"
                                    />
                                </Button>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                Kode unik pengenal mitra bisnis customer.
                            </p>
                            <p
                                v-if="form.errors.customer_code"
                                class="mt-1 text-xs text-red-600 dark:text-red-400"
                            >
                                {{ form.errors.customer_code }}
                            </p>
                        </div>

                        <!-- Nama Customer -->
                        <FormField
                            label="Nama Customer *"
                            for-id="name"
                            :error="form.errors.name"
                            required
                        >
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="PT Contoh Pelanggan Industri"
                                :disabled="form.processing"
                                required
                            />
                        </FormField>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <!-- Alias / Singkatan -->
                        <FormField
                            label="Alias / Singkatan"
                            for-id="alias"
                            :error="form.errors.alias"
                            help="Contoh: ADM, TMMIN, HPM"
                        >
                            <TextInput
                                id="alias"
                                v-model="form.alias"
                                type="text"
                                placeholder="ADM"
                                :disabled="form.processing"
                            />
                        </FormField>

                        <!-- Nomor NPWP -->
                        <FormField
                            label="NPWP / Tax ID"
                            for-id="tax_id"
                            :error="form.errors.tax_id"
                            help="Nomor Pokok Wajib Pajak"
                        >
                            <TextInput
                                id="tax_id"
                                v-model="form.tax_id"
                                type="text"
                                placeholder="01.234.567.8-012.000"
                                :disabled="form.processing"
                            />
                        </FormField>

                        <!-- Delivery Lead Time -->
                        <FormField
                            label="Lead Time Pengiriman (Hari)"
                            for-id="delivery_lead_time_days"
                            :error="form.errors.delivery_lead_time_days"
                            help="Estimasi durasi pengiriman"
                        >
                            <TextInput
                                id="delivery_lead_time_days"
                                v-model.number="form.delivery_lead_time_days"
                                type="number"
                                min="0"
                                placeholder="0"
                                :disabled="form.processing"
                            />
                        </FormField>
                    </div>

                    <!-- Status Aktif -->
                    <div
                        class="rounded-xl border border-gray-200 bg-gray-50/60 p-4 dark:border-gray-700 dark:bg-gray-800/60"
                    >
                        <label class="flex cursor-pointer items-center gap-3">
                            <Checkbox
                                v-model:checked="form.is_active"
                                :disabled="form.processing"
                            />
                            <div>
                                <span
                                    class="text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    Customer Aktif
                                </span>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Pelanggan aktif dapat dipilih dalam
                                    transaksi Sales Order, Delivery, dan
                                    Invoicing.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Tab 2: Alamat & Kontak Kantor -->
                <div v-show="activeTab === 'address'" class="space-y-4">
                    <FormField
                        label="Alamat Penagihan / Billing Address"
                        for-id="billing_address"
                        :error="form.errors.billing_address"
                        help="Alamat resmi untuk pengiriman dokumen invoice & tagihan"
                    >
                        <TextareaInput
                            id="billing_address"
                            v-model="form.billing_address"
                            rows="2"
                            placeholder="Jl. Raya Industri No. 12, Jakarta"
                            :disabled="form.processing"
                        />
                    </FormField>

                    <FormField
                        label="Alamat Pengiriman Barang / Shipping Address"
                        for-id="shipping_address"
                        :error="form.errors.shipping_address"
                        help="Lokasi plant atau loading dock penerimaan barang"
                    >
                        <TextareaInput
                            id="shipping_address"
                            v-model="form.shipping_address"
                            rows="2"
                            placeholder="Plant Karawang: Kawasan Industri KIIC Lot C-5"
                            :disabled="form.processing"
                        />
                    </FormField>

                    <FormField
                        label="Alamat Faktur Pajak / Tax Address"
                        for-id="tax_address"
                        :error="form.errors.tax_address"
                        help="Alamat sesuai SKT / SPPKP perpajakan (opsional jika sama dengan billing)"
                    >
                        <TextareaInput
                            id="tax_address"
                            v-model="form.tax_address"
                            rows="2"
                            placeholder="Alamat legal terdaftar di KPP"
                            :disabled="form.processing"
                        />
                    </FormField>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <FormField
                            label="Telepon Kantor"
                            for-id="phone"
                            :error="form.errors.phone"
                        >
                            <TextInput
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                placeholder="+62 21 8901234"
                                :disabled="form.processing"
                            />
                        </FormField>

                        <FormField
                            label="Fax Kantor"
                            for-id="fax"
                            :error="form.errors.fax"
                        >
                            <TextInput
                                id="fax"
                                v-model="form.fax"
                                type="text"
                                placeholder="+62 21 8901235"
                                :disabled="form.processing"
                            />
                        </FormField>

                        <FormField
                            label="Email Kantor"
                            for-id="email"
                            :error="form.errors.email"
                        >
                            <TextInput
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="info@customer.co.id"
                                :disabled="form.processing"
                            />
                        </FormField>
                    </div>
                </div>

                <!-- Tab 3: PIC / Kontak Personal -->
                <div v-show="activeTab === 'contact'" class="space-y-4">
                    <div
                        class="rounded-xl border border-blue-100 bg-blue-50/50 p-4 text-xs text-blue-800 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-300"
                    >
                        <div class="flex items-center gap-2 font-semibold">
                            <Icon name="user" class="h-4 w-4" />
                            Person In Charge (PIC) Pelanggan
                        </div>
                        <p class="mt-1 text-blue-700 dark:text-blue-400">
                            Informasi kontak utama dari bagian purchasing /
                            planning pelanggan untuk koordinasi pengiriman
                            pesanan dan verifikasi tagihan.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <FormField
                            label="Nama PIC"
                            for-id="contact_person"
                            :error="form.errors.contact_person"
                        >
                            <TextInput
                                id="contact_person"
                                v-model="form.contact_person"
                                type="text"
                                placeholder="Budi Santoso"
                                :disabled="form.processing"
                            />
                        </FormField>

                        <FormField
                            label="No. Telepon / WhatsApp PIC"
                            for-id="contact_person_phone"
                            :error="form.errors.contact_person_phone"
                        >
                            <TextInput
                                id="contact_person_phone"
                                v-model="form.contact_person_phone"
                                type="text"
                                placeholder="+62 812 3456 7890"
                                :disabled="form.processing"
                            />
                        </FormField>

                        <FormField
                            label="Email PIC"
                            for-id="contact_person_email"
                            :error="form.errors.contact_person_email"
                        >
                            <TextInput
                                id="contact_person_email"
                                v-model="form.contact_person_email"
                                type="email"
                                placeholder="budi@customer.co.id"
                                :disabled="form.processing"
                            />
                        </FormField>
                    </div>
                </div>

                <!-- Tab 4: Finansial & Rekening Bank -->
                <div v-show="activeTab === 'finance'" class="space-y-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <!-- Term of Payment -->
                        <FormField
                            label="Term of Payment (TOP)"
                            for-id="term_of_payment_id"
                            :error="form.errors.term_of_payment_id"
                            help="Syarat tempo pembayaran penjualan"
                        >
                            <SelectInput
                                id="term_of_payment_id"
                                v-model="form.term_of_payment_id"
                                :disabled="form.processing"
                            >
                                <option value="">
                                    Pilih Syarat Pembayaran
                                </option>
                                <option
                                    v-for="top in termOfPayments"
                                    :key="top.id"
                                    :value="top.id"
                                >
                                    {{ top.payment }} - {{ top.name }} ({{
                                        top.days
                                    }}
                                    hari)
                                </option>
                            </SelectInput>
                        </FormField>

                        <!-- Mata Uang Default -->
                        <FormField
                            label="Mata Uang Default"
                            for-id="currency_id"
                            :error="form.errors.currency_id"
                            help="Mata uang transaksi standar"
                        >
                            <SelectInput
                                id="currency_id"
                                v-model="form.currency_id"
                                :disabled="form.processing"
                            >
                                <option value="">Pilih Mata Uang</option>
                                <option
                                    v-for="curr in currencies"
                                    :key="curr.id"
                                    :value="curr.id"
                                >
                                    {{ curr.code }} - {{ curr.name }}
                                </option>
                            </SelectInput>
                        </FormField>

                        <!-- Akun Piutang (AR COA) -->
                        <FormField
                            label="Akun Piutang (COA AR)"
                            for-id="coa_receivable_id"
                            :error="form.errors.coa_receivable_id"
                            help="Bagan Akun Piutang Usaha"
                        >
                            <SelectInput
                                id="coa_receivable_id"
                                v-model="form.coa_receivable_id"
                                :disabled="form.processing"
                            >
                                <option value="">Pilih Akun Piutang</option>
                                <option
                                    v-for="coa in chartOfAccounts"
                                    :key="coa.id"
                                    :value="coa.id"
                                >
                                    {{ coa.account_code }} -
                                    {{ coa.account_name }}
                                </option>
                            </SelectInput>
                        </FormField>
                    </div>

                    <!-- Rekening Bank Utama Pelanggan -->
                    <div
                        class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-700 dark:bg-gray-800/40"
                    >
                        <div class="mb-3 flex items-center gap-2">
                            <Icon
                                name="dollar"
                                class="h-4 w-4 text-gray-700 dark:text-gray-300"
                            />
                            <h4
                                class="text-sm font-semibold text-gray-900 dark:text-white"
                            >
                                Rekening Bank Customer (Primary Bank)
                            </h4>
                        </div>
                        <p
                            class="mb-4 text-xs text-gray-500 dark:text-gray-400"
                        >
                            Data rekening bank pelanggan untuk pencocokan mutasi
                            dan konfirmasi pembayaran tagihan piutang.
                        </p>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <FormField
                                label="Nama Bank"
                                for-id="bank_name"
                                :error="form.errors.bank_name"
                            >
                                <TextInput
                                    id="bank_name"
                                    v-model="form.bank_name"
                                    type="text"
                                    placeholder="Bank Central Asia (BCA)"
                                    :disabled="form.processing"
                                />
                            </FormField>

                            <FormField
                                label="Nomor Rekening"
                                for-id="bank_account_number"
                                :error="form.errors.bank_account_number"
                            >
                                <TextInput
                                    id="bank_account_number"
                                    v-model="form.bank_account_number"
                                    type="text"
                                    placeholder="123-456-7890"
                                    :disabled="form.processing"
                                />
                            </FormField>
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <FormField
                                label="Nama Pemilik Rekening"
                                for-id="bank_account_name"
                                :error="form.errors.bank_account_name"
                            >
                                <TextInput
                                    id="bank_account_name"
                                    v-model="form.bank_account_name"
                                    type="text"
                                    placeholder="Sesuai buku tabungan / rekening koran"
                                    :disabled="form.processing"
                                />
                            </FormField>

                            <FormField
                                label="Kantor Cabang"
                                for-id="bank_branch"
                                :error="form.errors.bank_branch"
                            >
                                <TextInput
                                    id="bank_branch"
                                    v-model="form.bank_branch"
                                    type="text"
                                    placeholder="KC Jakarta Sudirman"
                                    :disabled="form.processing"
                                />
                            </FormField>

                            <FormField
                                label="Kode SWIFT"
                                for-id="swift_code"
                                :error="form.errors.swift_code"
                            >
                                <TextInput
                                    id="swift_code"
                                    v-model="form.swift_code"
                                    type="text"
                                    placeholder="CENAIDJA"
                                    :disabled="form.processing"
                                />
                            </FormField>
                        </div>
                    </div>

                    <!-- Catatan Tambahan -->
                    <FormField
                        label="Catatan Tambahan (Notes)"
                        for-id="notes"
                        :error="form.errors.notes"
                    >
                        <TextareaInput
                            id="notes"
                            v-model="form.notes"
                            rows="2"
                            placeholder="Informasi penting mengenai spesifikasi pesanan, jadwal kanban, atau instruksi faktur..."
                            :disabled="form.processing"
                        />
                    </FormField>
                </div>
            </form>
        </template>

        <template #footer>
            <div class="flex items-center justify-end gap-3">
                <Button
                    type="button"
                    variant="secondary"
                    :disabled="form.processing"
                    @click="handleClose"
                >
                    Batal
                </Button>
                <Button
                    type="submit"
                    form="customerForm"
                    variant="primary"
                    :disabled="form.processing"
                >
                    <Icon
                        v-if="form.processing"
                        name="spinner"
                        class="me-2 h-4 w-4 animate-spin"
                    />
                    <span>{{
                        isEdit ? 'Simpan Perubahan' : 'Tambah Customer'
                    }}</span>
                </Button>
            </div>
        </template>
    </ModalDialog>
</template>
