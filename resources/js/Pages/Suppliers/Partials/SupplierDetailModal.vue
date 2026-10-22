<script setup>
import Badge from '@/Components/Atoms/Badge.vue';
import Button from '@/Components/Atoms/Button.vue';
import Icon from '@/Components/Atoms/Icon.vue';
import ModalDialog from '@/Components/Organisms/ModalDialog.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    supplier: {
        type: Object,
        default: null,
    },
});

defineEmits(['close', 'edit']);
</script>

<template>
    <ModalDialog
        :show="show"
        max-width="3xl"
        title="Detail Informasi Supplier"
        subtitle="Profil lengkap rekanan pemasok dan ketentuan bisnis"
        @close="$emit('close')"
    >
        <div v-if="supplier" class="space-y-6">
            <!-- Header Card -->
            <div
                class="flex flex-col gap-3 rounded-xl border border-slate-200/80 bg-slate-50 p-4 dark:border-slate-700/60 dark:bg-slate-800/60 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="shadow-xs flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-base font-bold text-white dark:bg-blue-500"
                    >
                        {{
                            supplier.supplier_code
                                ? supplier.supplier_code.slice(0, 3)
                                : 'SUP'
                        }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="font-mono text-xs font-bold text-slate-500 dark:text-slate-400"
                            >
                                {{ supplier.supplier_code }}
                            </span>
                            <Badge
                                :variant="
                                    supplier.supplier_type === 'direct'
                                        ? 'info'
                                        : 'warning'
                                "
                                size="sm"
                            >
                                {{
                                    supplier.supplier_type === 'direct'
                                        ? 'Direct'
                                        : 'Indirect'
                                }}
                            </Badge>
                        </div>
                        <h3
                            class="text-base font-bold text-slate-900 dark:text-white sm:text-lg"
                        >
                            {{ supplier.supplier_name }}
                        </h3>
                    </div>
                </div>

                <div>
                    <Badge
                        :variant="
                            supplier.status === 'active' ? 'success' : 'danger'
                        "
                        dot
                        size="md"
                    >
                        {{
                            supplier.status === 'active'
                                ? 'Active Vendor'
                                : 'Nonactive'
                        }}
                    </Badge>
                </div>
            </div>

            <!-- 2-Column Detail Sections -->
            <div class="grid grid-cols-1 gap-4 text-xs md:grid-cols-2">
                <!-- Card 1: Kantor & Legalitas -->
                <div
                    class="space-y-3 rounded-xl border border-slate-200/80 p-4 dark:border-slate-700/60 dark:bg-slate-800/40"
                >
                    <h4
                        class="flex items-center gap-1.5 border-b border-slate-100 pb-2 font-bold uppercase tracking-wider text-slate-700 dark:border-slate-700/60 dark:text-slate-300"
                    >
                        <Icon name="building" class="h-4 w-4 text-blue-500" />
                        <span>Kantor & Legalitas</span>
                    </h4>

                    <div class="space-y-2">
                        <div>
                            <span
                                class="block text-slate-400 dark:text-slate-500"
                                >NPWP</span
                            >
                            <span
                                class="font-mono font-semibold text-slate-800 dark:text-slate-200"
                                >{{ supplier.npwp || '-' }}</span
                            >
                        </div>
                        <div>
                            <span
                                class="block text-slate-400 dark:text-slate-500"
                                >Alamat</span
                            >
                            <p
                                class="whitespace-pre-line text-slate-800 dark:text-slate-200"
                            >
                                {{ supplier.address || '-' }}
                            </p>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <span
                                    class="block text-slate-400 dark:text-slate-500"
                                    >Kode Pos</span
                                >
                                <span
                                    class="text-slate-800 dark:text-slate-200"
                                    >{{ supplier.zipcode || '-' }}</span
                                >
                            </div>
                            <div>
                                <span
                                    class="block text-slate-400 dark:text-slate-500"
                                    >Negara</span
                                >
                                <span
                                    class="text-slate-800 dark:text-slate-200"
                                    >{{ supplier.country_code || 'ID' }}</span
                                >
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <span
                                    class="block text-slate-400 dark:text-slate-500"
                                    >Telepon Kantor</span
                                >
                                <span
                                    class="text-slate-800 dark:text-slate-200"
                                    >{{ supplier.office_phone || '-' }}</span
                                >
                            </div>
                            <div>
                                <span
                                    class="block text-slate-400 dark:text-slate-500"
                                    >Fax</span
                                >
                                <span
                                    class="text-slate-800 dark:text-slate-200"
                                    >{{ supplier.fax || '-' }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Keuangan & Ketentuan Pembayaran -->
                <div
                    class="space-y-3 rounded-xl border border-slate-200/80 p-4 dark:border-slate-700/60 dark:bg-slate-800/40"
                >
                    <h4
                        class="flex items-center gap-1.5 border-b border-slate-100 pb-2 font-bold uppercase tracking-wider text-slate-700 dark:border-slate-700/60 dark:text-slate-300"
                    >
                        <Icon name="dollar" class="h-4 w-4 text-emerald-500" />
                        <span>Termin & Rekening Bank</span>
                    </h4>

                    <div class="space-y-2">
                        <div>
                            <span
                                class="block text-slate-400 dark:text-slate-500"
                                >Term of Payment (TOP)</span
                            >
                            <div v-if="supplier.term_of_payment" class="mt-0.5">
                                <span
                                    class="font-bold text-blue-600 dark:text-blue-400"
                                    >{{
                                        supplier.term_of_payment.payment
                                    }}</span
                                >
                                <span
                                    class="text-slate-500 dark:text-slate-400"
                                >
                                    ({{ supplier.term_of_payment.name }} -
                                    {{ supplier.term_of_payment.days }}
                                    Hari)</span
                                >
                            </div>
                            <span v-else class="text-slate-400">-</span>
                        </div>
                        <div>
                            <span
                                class="block text-slate-400 dark:text-slate-500"
                                >Mata Uang Default</span
                            >
                            <span
                                class="font-bold text-slate-800 dark:text-slate-200"
                                >{{ supplier.default_currency || 'IDR' }}</span
                            >
                        </div>
                        <div
                            class="space-y-1 border-t border-slate-100 pt-2 dark:border-slate-700/60"
                        >
                            <div>
                                <span
                                    class="block text-slate-400 dark:text-slate-500"
                                    >Nama Bank</span
                                >
                                <span
                                    class="font-semibold text-slate-800 dark:text-slate-200"
                                    >{{ supplier.bank_name || '-' }}</span
                                >
                            </div>
                            <div>
                                <span
                                    class="block text-slate-400 dark:text-slate-500"
                                    >No. Rekening</span
                                >
                                <span
                                    class="font-mono font-bold text-slate-800 dark:text-slate-200"
                                    >{{
                                        supplier.bank_account_number || '-'
                                    }}</span
                                >
                            </div>
                            <div>
                                <span
                                    class="block text-slate-400 dark:text-slate-500"
                                    >Atas Nama (A.N.)</span
                                >
                                <span
                                    class="text-slate-800 dark:text-slate-200"
                                    >{{
                                        supplier.bank_account_name || '-'
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Kontak PIC Marketing & Accounting -->
                <div
                    class="space-y-3 rounded-xl border border-slate-200/80 p-4 dark:border-slate-700/60 dark:bg-slate-800/40"
                >
                    <h4
                        class="flex items-center gap-1.5 border-b border-slate-100 pb-2 font-bold uppercase tracking-wider text-slate-700 dark:border-slate-700/60 dark:text-slate-300"
                    >
                        <Icon name="user" class="h-4 w-4 text-blue-500" />
                        <span>Person In Charge (PIC)</span>
                    </h4>

                    <div class="space-y-3">
                        <div
                            class="rounded-lg bg-slate-50 p-2.5 dark:bg-slate-800/80"
                        >
                            <span
                                class="mb-1 block text-[10px] font-bold uppercase text-blue-600 dark:text-blue-400"
                                >PIC Marketing</span
                            >
                            <p
                                class="font-semibold text-slate-900 dark:text-white"
                            >
                                {{ supplier.marketing_name || '-' }}
                            </p>
                            <p class="text-slate-600 dark:text-slate-300">
                                {{ supplier.marketing_phone || '-' }}
                            </p>
                            <p class="text-blue-600 dark:text-blue-400">
                                {{ supplier.marketing_email || '-' }}
                            </p>
                        </div>

                        <div
                            class="rounded-lg bg-slate-50 p-2.5 dark:bg-slate-800/80"
                        >
                            <span
                                class="mb-1 block text-[10px] font-bold uppercase text-purple-600 dark:text-purple-400"
                                >PIC Accounting / Finance</span
                            >
                            <p
                                class="font-semibold text-slate-900 dark:text-white"
                            >
                                {{ supplier.accounting_name || '-' }}
                            </p>
                            <p class="text-slate-600 dark:text-slate-300">
                                {{ supplier.accounting_phone || '-' }}
                            </p>
                            <p class="text-purple-600 dark:text-purple-400">
                                {{ supplier.accounting_email || '-' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Jadwal Pengiriman -->
                <div
                    class="space-y-3 rounded-xl border border-slate-200/80 p-4 dark:border-slate-700/60 dark:bg-slate-800/40"
                >
                    <h4
                        class="flex items-center gap-1.5 border-b border-slate-100 pb-2 font-bold uppercase tracking-wider text-slate-700 dark:border-slate-700/60 dark:text-slate-300"
                    >
                        <Icon name="calendar" class="h-4 w-4 text-amber-500" />
                        <span>Logistik & Jadwal Kirim</span>
                    </h4>

                    <div class="space-y-2">
                        <div>
                            <span
                                class="block text-slate-400 dark:text-slate-500"
                                >Estimasi Periode Kirim</span
                            >
                            <span
                                class="font-medium text-slate-800 dark:text-slate-200"
                                >{{
                                    supplier.delivery_period ||
                                    'Sesuai kesepakatan PO'
                                }}</span
                            >
                        </div>
                        <div>
                            <span
                                class="block text-slate-400 dark:text-slate-500"
                                >Jendela Jam Pengiriman</span
                            >
                            <span
                                class="font-medium text-slate-800 dark:text-slate-200"
                            >
                                {{ supplier.delivery_time || '08:00' }} s/d
                                {{ supplier.delivery_end_time || '17:00' }} WIB
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <Button variant="secondary" size="md" @click="$emit('close')">
                Tutup
            </Button>
            <Button
                variant="primary"
                size="md"
                @click="$emit('edit', supplier)"
            >
                <template #icon-left>
                    <Icon name="edit" class="h-4 w-4" />
                </template>
                Ubah Data Supplier
            </Button>
        </template>
    </ModalDialog>
</template>
