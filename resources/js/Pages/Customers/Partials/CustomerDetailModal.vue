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
    customer: {
        type: Object,
        default: null,
    },
});

defineEmits(['close', 'edit']);

const formatDate = (dateString) => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    } catch {
        return dateString;
    }
};
</script>

<template>
    <ModalDialog
        :show="show"
        title="Detail Customer"
        max-width="3xl"
        @close="$emit('close')"
    >
        <template #content>
            <div v-if="customer" class="space-y-6">
                <!-- Header Profil Customer -->
                <div
                    class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-gray-50/70 p-5 dark:border-gray-700/80 dark:bg-gray-800/50 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-lg font-bold text-white shadow-sm"
                        >
                            {{
                                customer.alias
                                    ? customer.alias.slice(0, 3)
                                    : customer.customer_code.slice(-3)
                            }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3
                                    class="text-base font-bold text-gray-900 dark:text-white"
                                >
                                    {{ customer.name }}
                                </h3>
                                <Badge
                                    v-if="customer.alias"
                                    variant="secondary"
                                    size="sm"
                                    class="font-bold"
                                >
                                    {{ customer.alias }}
                                </Badge>
                            </div>
                            <div
                                class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                            >
                                <span
                                    class="font-mono font-semibold text-gray-700 dark:text-gray-300"
                                >
                                    {{ customer.customer_code }}
                                </span>
                                <span>•</span>
                                <span>NPWP: {{ customer.tax_id || '-' }}</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <Badge
                            :variant="customer.is_active ? 'success' : 'danger'"
                            size="md"
                        >
                            {{ customer.is_active ? 'Aktif' : 'Nonaktif' }}
                        </Badge>
                    </div>
                </div>

                <!-- Grid Informasi -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- Kolom 1: Finansial & Operasional -->
                    <div
                        class="space-y-4 rounded-xl border border-gray-200 p-4 dark:border-gray-700"
                    >
                        <div
                            class="flex items-center gap-2 border-b border-gray-100 pb-2 text-sm font-semibold text-gray-900 dark:border-gray-800 dark:text-white"
                        >
                            <Icon
                                name="dollar"
                                class="h-4 w-4 text-emerald-600"
                            />
                            Finansial & Ketentuan Penjualan
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <span class="text-gray-500"
                                    >Term of Payment:</span
                                >
                                <p
                                    class="mt-0.5 font-medium text-gray-900 dark:text-white"
                                >
                                    {{
                                        customer.term_of_payment
                                            ? `${customer.term_of_payment.payment} (${customer.term_of_payment.days} hari)`
                                            : '-'
                                    }}
                                </p>
                            </div>
                            <div>
                                <span class="text-gray-500"
                                    >Mata Uang Default:</span
                                >
                                <p
                                    class="mt-0.5 font-medium text-gray-900 dark:text-white"
                                >
                                    {{
                                        customer.currency
                                            ? `${customer.currency.code} - ${customer.currency.name}`
                                            : 'IDR'
                                    }}
                                </p>
                            </div>
                            <div>
                                <span class="text-gray-500"
                                    >Akun Piutang (AR COA):</span
                                >
                                <p
                                    class="mt-0.5 font-medium text-gray-900 dark:text-white"
                                >
                                    {{
                                        customer.receivable_account
                                            ? `${customer.receivable_account.account_code} - ${customer.receivable_account.account_name}`
                                            : '-'
                                    }}
                                </p>
                            </div>
                            <div>
                                <span class="text-gray-500"
                                    >Delivery Lead Time:</span
                                >
                                <p
                                    class="mt-0.5 font-medium text-gray-900 dark:text-white"
                                >
                                    {{ customer.delivery_lead_time_days }} hari
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom 2: Kontak Personal (PIC) & Kantor -->
                    <div
                        class="space-y-4 rounded-xl border border-gray-200 p-4 dark:border-gray-700"
                    >
                        <div
                            class="flex items-center gap-2 border-b border-gray-100 pb-2 text-sm font-semibold text-gray-900 dark:border-gray-800 dark:text-white"
                        >
                            <Icon name="user" class="h-4 w-4 text-blue-600" />
                            Kontak PIC & Kantor
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <span class="text-gray-500">Nama PIC:</span>
                                <p
                                    class="mt-0.5 font-medium text-gray-900 dark:text-white"
                                >
                                    {{ customer.contact_person || '-' }}
                                </p>
                            </div>
                            <div>
                                <span class="text-gray-500"
                                    >Telepon / WA PIC:</span
                                >
                                <p
                                    class="mt-0.5 font-medium text-gray-900 dark:text-white"
                                >
                                    {{ customer.contact_person_phone || '-' }}
                                </p>
                            </div>
                            <div>
                                <span class="text-gray-500">Email PIC:</span>
                                <p
                                    class="mt-0.5 break-all font-medium text-gray-900 dark:text-white"
                                >
                                    {{ customer.contact_person_email || '-' }}
                                </p>
                            </div>
                            <div>
                                <span class="text-gray-500"
                                    >Telepon / Fax Kantor:</span
                                >
                                <p
                                    class="mt-0.5 font-medium text-gray-900 dark:text-white"
                                >
                                    {{ customer.phone || '-' }}
                                    {{
                                        customer.fax
                                            ? `/ Fax: ${customer.fax}`
                                            : ''
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alamat Lengkap -->
                <div
                    class="space-y-3 rounded-xl border border-gray-200 p-4 text-xs dark:border-gray-700"
                >
                    <div
                        class="flex items-center gap-2 border-b border-gray-100 pb-2 text-sm font-semibold text-gray-900 dark:border-gray-800 dark:text-white"
                    >
                        <Icon name="map-pin" class="h-4 w-4 text-amber-600" />
                        Alamat Pengiriman & Penagihan
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <span
                                class="font-semibold text-gray-700 dark:text-gray-300"
                                >Alamat Penagihan (Billing):</span
                            >
                            <p class="mt-1 text-gray-600 dark:text-gray-400">
                                {{ customer.billing_address || '-' }}
                            </p>
                        </div>
                        <div>
                            <span
                                class="font-semibold text-gray-700 dark:text-gray-300"
                                >Alamat Pengiriman (Shipping):</span
                            >
                            <p class="mt-1 text-gray-600 dark:text-gray-400">
                                {{ customer.shipping_address || '-' }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-if="customer.tax_address"
                        class="border-t border-gray-100 pt-2 dark:border-gray-800"
                    >
                        <span
                            class="font-semibold text-gray-700 dark:text-gray-300"
                            >Alamat Faktur Pajak:</span
                        >
                        <p class="mt-1 text-gray-600 dark:text-gray-400">
                            {{ customer.tax_address }}
                        </p>
                    </div>
                </div>

                <!-- Rekening Bank -->
                <div
                    v-if="customer.banks && customer.banks.length > 0"
                    class="rounded-xl border border-gray-200 p-4 dark:border-gray-700"
                >
                    <div
                        class="flex items-center gap-2 border-b border-gray-100 pb-2 text-sm font-semibold text-gray-900 dark:border-gray-800 dark:text-white"
                    >
                        <Icon name="building" class="h-4 w-4 text-purple-600" />
                        Rekening Bank Customer
                    </div>

                    <div
                        class="mt-3 divide-y divide-gray-100 dark:divide-gray-800"
                    >
                        <div
                            v-for="bank in customer.banks"
                            :key="bank.id"
                            class="flex items-center justify-between py-2 text-xs"
                        >
                            <div>
                                <span
                                    class="font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ bank.bank_name }}
                                </span>
                                <span
                                    v-if="bank.bank_branch"
                                    class="ms-1 text-gray-500"
                                >
                                    ({{ bank.bank_branch }})
                                </span>
                                <p
                                    class="font-mono text-gray-700 dark:text-gray-300"
                                >
                                    No: {{ bank.bank_account_number }} a/n
                                    {{ bank.bank_account_name }}
                                </p>
                            </div>
                            <Badge
                                v-if="bank.is_primary"
                                variant="info"
                                size="sm"
                            >
                                Utama
                            </Badge>
                        </div>
                    </div>
                </div>

                <!-- Catatan Tambahan -->
                <div
                    v-if="customer.notes"
                    class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 text-xs dark:border-gray-700 dark:bg-gray-800/40"
                >
                    <span class="font-semibold text-gray-700 dark:text-gray-300"
                        >Catatan:</span
                    >
                    <p class="mt-1 text-gray-600 dark:text-gray-400">
                        {{ customer.notes }}
                    </p>
                </div>

                <!-- Audit Log Metadata -->
                <div
                    class="rounded-xl border border-dashed border-gray-200 p-4 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400"
                >
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <div>
                            Dibuat oleh:
                            <span
                                class="font-semibold text-gray-700 dark:text-gray-300"
                                >{{ customer.creator?.name || 'Sistem' }}</span
                            >
                            pada {{ formatDate(customer.created_at) }}
                        </div>
                        <div>
                            Terakhir diubah oleh:
                            <span
                                class="font-semibold text-gray-700 dark:text-gray-300"
                                >{{ customer.updater?.name || 'Sistem' }}</span
                            >
                            pada {{ formatDate(customer.updated_at) }}
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex w-full items-center justify-between">
                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    @click="$emit('close')"
                >
                    Tutup
                </Button>

                <Button
                    v-if="$page.props.auth?.can?.['customers.edit']"
                    type="button"
                    variant="primary"
                    size="sm"
                    @click="$emit('edit', customer)"
                >
                    <Icon name="pencil" class="me-1.5 h-4 w-4" />
                    Edit Customer
                </Button>
            </div>
        </template>
    </ModalDialog>
</template>
