<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import Badge from '@/Components/Atoms/Badge.vue';
import Button from '@/Components/Atoms/Button.vue';
import Icon from '@/Components/Atoms/Icon.vue';
import SelectInput from '@/Components/Atoms/SelectInput.vue';
import ConfirmDialog from '@/Components/Molecules/ConfirmDialog.vue';
import FilterPills from '@/Components/Molecules/FilterPills.vue';
import Pagination from '@/Components/Molecules/Pagination.vue';
import StatsCard from '@/Components/Molecules/StatsCard.vue';
import DataTable from '@/Components/Organisms/DataTable.vue';
import FilterBar from '@/Components/Organisms/FilterBar.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import AuthenticatedTemplate from '@/Components/Templates/AuthenticatedTemplate.vue';
import CustomerDetailModal from './Partials/CustomerDetailModal.vue';
import CustomerFormModal from './Partials/CustomerFormModal.vue';

const props = defineProps({
    customers: Object,
    stats: Object,
    termOfPayments: Array,
    currencies: Array,
    chartOfAccounts: Array,
    filters: Object,
    suggestedCode: String,
});

// Search & Filter state
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status ?? '');
const termFilter = ref(props.filters?.term_of_payment_id || '');
const currencyFilter = ref(props.filters?.currency_id || '');
let searchTimeout = null;

const activeFiltersCount = computed(() => {
    let count = 0;
    if (statusFilter.value !== '') count++;
    if (termFilter.value) count++;
    if (currencyFilter.value) count++;
    return count;
});

const applyFilters = () => {
    router.get(
        route('customers.index'),
        {
            search: search.value || undefined,
            status: statusFilter.value !== '' ? statusFilter.value : undefined,
            term_of_payment_id: termFilter.value || undefined,
            currency_id: currencyFilter.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch([statusFilter, termFilter, currencyFilter], () => {
    applyFilters();
});

const resetFilters = () => {
    search.value = '';
    statusFilter.value = '';
    termFilter.value = '';
    currencyFilter.value = '';
    applyFilters();
};

// -----------------------------------------------------------------------------
// CREATE MODAL & FORM STATE
// -----------------------------------------------------------------------------
const showCreateModal = ref(false);
const isGeneratingCode = ref(false);

const createForm = useForm({
    customer_code: props.suggestedCode || '',
    name: '',
    alias: '',
    tax_id: '',
    term_of_payment_id: '',
    currency_id: '',
    coa_receivable_id: '',
    phone: '',
    fax: '',
    email: '',
    contact_person: '',
    contact_person_phone: '',
    contact_person_email: '',
    billing_address: '',
    shipping_address: '',
    tax_address: '',
    delivery_lead_time_days: 0,
    is_active: true,
    notes: '',
    bank_name: '',
    bank_account_number: '',
    bank_account_name: '',
    bank_branch: '',
    swift_code: '',
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.customer_code = props.suggestedCode || '';
    createForm.is_active = true;
    createForm.delivery_lead_time_days = 0;
    showCreateModal.value = true;
};

const refreshSuggestedCode = async () => {
    isGeneratingCode.value = true;
    try {
        const response = await axios.get(route('customers.generate-code'));
        if (response.data?.code) {
            createForm.customer_code = response.data.code;
        }
    } catch {
        // Fallback retain current
    } finally {
        isGeneratingCode.value = false;
    }
};

const submitCreate = () => {
    createForm.post(route('customers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// -----------------------------------------------------------------------------
// EDIT MODAL & FORM STATE
// -----------------------------------------------------------------------------
const showEditModal = ref(false);
const editingCustomer = ref(null);

const editForm = useForm({
    customer_code: '',
    name: '',
    alias: '',
    tax_id: '',
    term_of_payment_id: '',
    currency_id: '',
    coa_receivable_id: '',
    phone: '',
    fax: '',
    email: '',
    contact_person: '',
    contact_person_phone: '',
    contact_person_email: '',
    billing_address: '',
    shipping_address: '',
    tax_address: '',
    delivery_lead_time_days: 0,
    is_active: true,
    notes: '',
    bank_name: '',
    bank_account_number: '',
    bank_account_name: '',
    bank_branch: '',
    swift_code: '',
});

const openEditModal = (customer) => {
    editingCustomer.value = customer;
    editForm.clearErrors();

    const primaryBank =
        customer.banks?.find((b) => b.is_primary) || customer.banks?.[0] || {};

    editForm.customer_code = customer.customer_code || '';
    editForm.name = customer.name || '';
    editForm.alias = customer.alias || '';
    editForm.tax_id = customer.tax_id || '';
    editForm.term_of_payment_id = customer.term_of_payment_id || '';
    editForm.currency_id = customer.currency_id || '';
    editForm.coa_receivable_id = customer.coa_receivable_id || '';
    editForm.phone = customer.phone || '';
    editForm.fax = customer.fax || '';
    editForm.email = customer.email || '';
    editForm.contact_person = customer.contact_person || '';
    editForm.contact_person_phone = customer.contact_person_phone || '';
    editForm.contact_person_email = customer.contact_person_email || '';
    editForm.billing_address = customer.billing_address || '';
    editForm.shipping_address = customer.shipping_address || '';
    editForm.tax_address = customer.tax_address || '';
    editForm.delivery_lead_time_days = customer.delivery_lead_time_days ?? 0;
    editForm.is_active = Boolean(customer.is_active);
    editForm.notes = customer.notes || '';

    editForm.bank_name = primaryBank.bank_name || '';
    editForm.bank_account_number = primaryBank.bank_account_number || '';
    editForm.bank_account_name = primaryBank.bank_account_name || '';
    editForm.bank_branch = primaryBank.bank_branch || '';
    editForm.swift_code = primaryBank.swift_code || '';

    // If detail modal was open, close it
    showDetailModal.value = false;
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!editingCustomer.value) return;

    editForm.put(route('customers.update', editingCustomer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editingCustomer.value = null;
        },
    });
};

// -----------------------------------------------------------------------------
// DETAIL MODAL STATE
// -----------------------------------------------------------------------------
const showDetailModal = ref(false);
const selectedCustomer = ref(null);

const openDetailModal = (customer) => {
    selectedCustomer.value = customer;
    showDetailModal.value = true;
};

// -----------------------------------------------------------------------------
// DELETE CONFIRMATION STATE
// -----------------------------------------------------------------------------
const showDeleteDialog = ref(false);
const customerToDelete = ref(null);
const isDeleting = ref(false);

const confirmDelete = (customer) => {
    customerToDelete.value = customer;
    showDeleteDialog.value = true;
};

const executeDelete = () => {
    if (!customerToDelete.value) return;

    isDeleting.value = true;
    router.delete(route('customers.destroy', customerToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDialog.value = false;
            customerToDelete.value = null;
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};
</script>

<template>
    <Head title="Master Customer" />

    <AuthenticatedTemplate>
        <template #header>
            <PageHeader
                title="Master Customer"
                subtitle="Kelola data mitra bisnis pelanggan untuk persiapan modul Sales, Delivery, dan Accounts Receivable (AR)."
                :breadcrumbs="[
                    { label: 'Dashboard', href: route('dashboard') },
                    { label: 'Master Data' },
                    { label: 'Master Customer' },
                ]"
            >
                <template #actions>
                    <Button
                        v-if="$page.props.auth?.can?.['customers.create']"
                        variant="primary"
                        size="md"
                        @click="openCreateModal"
                    >
                        <Icon name="plus" class="me-2 h-4 w-4" />
                        Tambah Customer
                    </Button>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-6">
            <!-- 1. Stats Overview Cards -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatsCard
                    title="Total Customer"
                    :value="stats?.total || 0"
                    icon="users"
                    variant="primary"
                />
                <StatsCard
                    title="Customer Aktif"
                    :value="stats?.active || 0"
                    icon="check-circle"
                    variant="success"
                />
                <StatsCard
                    title="Customer Nonaktif"
                    :value="stats?.inactive || 0"
                    icon="x-circle"
                    variant="danger"
                />
                <StatsCard
                    title="Akun Piutang (AR)"
                    :value="stats?.with_receivable_coa || 0"
                    icon="calculator"
                    variant="warning"
                />
            </div>

            <!-- 2. Filter & Search Bar -->
            <FilterBar
                v-model:search="search"
                search-placeholder="Cari kode, nama customer, alias, NPWP, kontak, atau no. rekening..."
                :active-filters-count="activeFiltersCount"
                @reset="resetFilters"
            >
                <template #filters>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <!-- Filter Status -->
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Status Aktif
                            </label>
                            <SelectInput
                                v-model="statusFilter"
                                class="w-full text-xs"
                            >
                                <option value="">Semua Status</option>
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </SelectInput>
                        </div>

                        <!-- Filter Term of Payment -->
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Term of Payment (TOP)
                            </label>
                            <SelectInput
                                v-model="termFilter"
                                class="w-full text-xs"
                            >
                                <option value="">Semua Syarat Bayar</option>
                                <option
                                    v-for="top in termOfPayments"
                                    :key="top.id"
                                    :value="top.id"
                                >
                                    {{ top.payment }} ({{ top.days }} hr)
                                </option>
                            </SelectInput>
                        </div>

                        <!-- Filter Mata Uang -->
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-gray-700 dark:text-gray-300"
                            >
                                Mata Uang Default
                            </label>
                            <SelectInput
                                v-model="currencyFilter"
                                class="w-full text-xs"
                            >
                                <option value="">Semua Mata Uang</option>
                                <option
                                    v-for="curr in currencies"
                                    :key="curr.id"
                                    :value="curr.id"
                                >
                                    {{ curr.code }} - {{ curr.name }}
                                </option>
                            </SelectInput>
                        </div>
                    </div>
                </template>
            </FilterBar>

            <!-- Filter Pills -->
            <FilterPills
                v-if="activeFiltersCount > 0"
                :count="activeFiltersCount"
                @reset="resetFilters"
            />

            <!-- 3. Desktop Table & Mobile Cards -->
            <DataTable
                :items="customers.data"
                :empty="customers.data.length === 0"
                empty-title="Belum ada data customer"
                empty-description="Tambahkan mitra bisnis customer baru untuk memulai transaksi penjualan dan pengiriman."
            >
                <!-- Desktop Table View -->
                <template #table>
                    <table
                        class="w-full text-start text-sm text-gray-600 dark:text-gray-300"
                    >
                        <thead
                            class="border-b border-gray-200 bg-gray-50/75 text-xs uppercase tracking-wider text-gray-500 dark:border-gray-700/60 dark:bg-gray-700/40 dark:text-gray-400"
                        >
                            <tr>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 font-semibold"
                                >
                                    Kode & Nama Pelanggan
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 font-semibold"
                                >
                                    PIC & Kontak
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 font-semibold"
                                >
                                    Finansial & TOP
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 font-semibold"
                                >
                                    Akun Piutang & Bank
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 font-semibold"
                                >
                                    Status
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3.5 text-end font-semibold"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-200 dark:divide-gray-700/60"
                        >
                            <tr
                                v-for="cust in customers.data"
                                :key="cust.id"
                                class="transition-colors hover:bg-gray-50/50 dark:hover:bg-gray-700/25"
                            >
                                <!-- Kode & Nama -->
                                <td class="px-6 py-4">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-xs font-bold text-blue-800 dark:bg-blue-950 dark:text-blue-300"
                                        >
                                            {{
                                                cust.alias
                                                    ? cust.alias.slice(0, 3)
                                                    : cust.customer_code.slice(
                                                          -3,
                                                      )
                                            }}
                                        </div>
                                        <div>
                                            <div
                                                class="flex items-center gap-1.5"
                                            >
                                                <span
                                                    class="font-bold text-gray-900 dark:text-white"
                                                >
                                                    {{ cust.name }}
                                                </span>
                                                <Badge
                                                    v-if="cust.alias"
                                                    variant="secondary"
                                                    size="sm"
                                                    class="font-bold"
                                                >
                                                    {{ cust.alias }}
                                                </Badge>
                                            </div>
                                            <div
                                                class="mt-0.5 flex items-center gap-2 text-xs text-gray-500"
                                            >
                                                <span
                                                    class="font-mono font-semibold text-gray-700 dark:text-gray-300"
                                                >
                                                    {{ cust.customer_code }}
                                                </span>
                                                <span v-if="cust.tax_id"
                                                    >• NPWP:
                                                    {{ cust.tax_id }}</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- PIC & Kontak -->
                                <td class="px-6 py-4 text-xs">
                                    <div
                                        v-if="cust.contact_person"
                                        class="font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ cust.contact_person }}
                                    </div>
                                    <div
                                        v-if="
                                            cust.contact_person_phone ||
                                            cust.phone
                                        "
                                        class="text-gray-500"
                                    >
                                        {{
                                            cust.contact_person_phone ||
                                            cust.phone
                                        }}
                                    </div>
                                    <div
                                        v-if="
                                            cust.contact_person_email ||
                                            cust.email
                                        "
                                        class="text-gray-400"
                                    >
                                        {{
                                            cust.contact_person_email ||
                                            cust.email
                                        }}
                                    </div>
                                    <span
                                        v-if="
                                            !cust.contact_person &&
                                            !cust.phone &&
                                            !cust.email
                                        "
                                        class="italic text-gray-400"
                                    >
                                        Belum diatur
                                    </span>
                                </td>

                                <!-- Finansial & TOP -->
                                <td class="px-6 py-4 text-xs">
                                    <div
                                        class="font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{
                                            cust.term_of_payment
                                                ? cust.term_of_payment.payment
                                                : 'TOP Belum Diatur'
                                        }}
                                    </div>
                                    <div class="mt-0.5 text-gray-500">
                                        Valuta:
                                        <span class="font-semibold">{{
                                            cust.currency?.code || 'IDR'
                                        }}</span>
                                        • Lead:
                                        {{ cust.delivery_lead_time_days }} hari
                                    </div>
                                </td>

                                <!-- Akun Piutang & Bank -->
                                <td class="px-6 py-4 text-xs">
                                    <div
                                        v-if="cust.receivable_account"
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {{
                                            cust.receivable_account.account_code
                                        }}
                                        -
                                        {{
                                            cust.receivable_account.account_name
                                        }}
                                    </div>
                                    <div v-else class="italic text-gray-400">
                                        Akun AR belum dipilih
                                    </div>
                                    <div
                                        v-if="
                                            cust.banks && cust.banks.length > 0
                                        "
                                        class="mt-0.5 font-mono text-gray-500"
                                    >
                                        {{ cust.banks[0].bank_name }}:
                                        {{ cust.banks[0].bank_account_number }}
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4">
                                    <Badge
                                        :variant="
                                            cust.is_active
                                                ? 'success'
                                                : 'danger'
                                        "
                                        size="sm"
                                    >
                                        {{
                                            cust.is_active
                                                ? 'Aktif'
                                                : 'Nonaktif'
                                        }}
                                    </Badge>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 text-end">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <!-- Detail Button -->
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                            title="Lihat Detail Customer"
                                            @click="openDetailModal(cust)"
                                        >
                                            <Icon name="eye" class="h-4 w-4" />
                                        </button>

                                        <!-- Edit Button -->
                                        <button
                                            v-if="
                                                $page.props.auth?.can?.[
                                                    'customers.edit'
                                                ]
                                            "
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-blue-600 transition-colors hover:bg-blue-50 hover:text-blue-700 dark:text-blue-400 dark:hover:bg-blue-950/50"
                                            title="Ubah Data Customer"
                                            @click="openEditModal(cust)"
                                        >
                                            <Icon
                                                name="pencil"
                                                class="h-4 w-4"
                                            />
                                        </button>

                                        <!-- Delete Button -->
                                        <button
                                            v-if="
                                                $page.props.auth?.can?.[
                                                    'customers.delete'
                                                ]
                                            "
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-600 transition-colors hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/50"
                                            title="Hapus Customer"
                                            @click="confirmDelete(cust)"
                                        >
                                            <Icon
                                                name="trash"
                                                class="h-4 w-4"
                                            />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </template>

                <!-- Mobile Cards View -->
                <template #cards>
                    <div
                        class="divide-y divide-gray-200 dark:divide-gray-700/60"
                    >
                        <div
                            v-for="cust in customers.data"
                            :key="cust.id"
                            class="space-y-3 p-4"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="font-bold text-gray-900 dark:text-white"
                                        >
                                            {{ cust.name }}
                                        </span>
                                        <Badge
                                            v-if="cust.alias"
                                            variant="secondary"
                                            size="sm"
                                        >
                                            {{ cust.alias }}
                                        </Badge>
                                    </div>
                                    <p class="font-mono text-xs text-gray-500">
                                        {{ cust.customer_code }}
                                    </p>
                                </div>
                                <Badge
                                    :variant="
                                        cust.is_active ? 'success' : 'danger'
                                    "
                                    size="sm"
                                >
                                    {{ cust.is_active ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </div>

                            <div
                                class="space-y-1 text-xs text-gray-600 dark:text-gray-400"
                            >
                                <div v-if="cust.contact_person">
                                    <span class="text-gray-500">PIC:</span>
                                    {{ cust.contact_person }} ({{
                                        cust.contact_person_phone || '-'
                                    }})
                                </div>
                                <div>
                                    <span class="text-gray-500">TOP:</span>
                                    {{
                                        cust.term_of_payment
                                            ? `${cust.term_of_payment.payment} (${cust.term_of_payment.days} hr)`
                                            : '-'
                                    }}
                                    • <span class="text-gray-500">Lead:</span>
                                    {{ cust.delivery_lead_time_days }} hr
                                </div>
                                <div v-if="cust.receivable_account">
                                    <span class="text-gray-500">AR COA:</span>
                                    {{ cust.receivable_account.account_code }} -
                                    {{ cust.receivable_account.account_name }}
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-end gap-2 border-t border-gray-100 pt-2 dark:border-gray-800"
                            >
                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    @click="openDetailModal(cust)"
                                >
                                    <Icon name="eye" class="me-1 h-3.5 w-3.5" />
                                    Detail
                                </Button>
                                <Button
                                    v-if="
                                        $page.props.auth?.can?.[
                                            'customers.edit'
                                        ]
                                    "
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    @click="openEditModal(cust)"
                                >
                                    <Icon
                                        name="pencil"
                                        class="me-1 h-3.5 w-3.5"
                                    />
                                    Edit
                                </Button>
                                <Button
                                    v-if="
                                        $page.props.auth?.can?.[
                                            'customers.delete'
                                        ]
                                    "
                                    type="button"
                                    variant="danger"
                                    size="sm"
                                    @click="confirmDelete(cust)"
                                >
                                    <Icon
                                        name="trash"
                                        class="me-1 h-3.5 w-3.5"
                                    />
                                    Hapus
                                </Button>
                            </div>
                        </div>
                    </div>
                </template>
            </DataTable>

            <!-- 4. Pagination -->
            <Pagination :links="customers.links" />
        </div>

        <!-- Create Customer Modal -->
        <CustomerFormModal
            :show="showCreateModal"
            title="Tambah Customer Baru"
            :form="createForm"
            :term-of-payments="termOfPayments"
            :currencies="currencies"
            :chart-of-accounts="chartOfAccounts"
            :is-generating-code="isGeneratingCode"
            :is-edit="false"
            @close="showCreateModal = false"
            @submit="submitCreate"
            @refresh-code="refreshSuggestedCode"
        />

        <!-- Edit Customer Modal -->
        <CustomerFormModal
            :show="showEditModal"
            title="Ubah Data Customer"
            :form="editForm"
            :term-of-payments="termOfPayments"
            :currencies="currencies"
            :chart-of-accounts="chartOfAccounts"
            :is-edit="true"
            @close="showEditModal = false"
            @submit="submitEdit"
        />

        <!-- Detail Customer Modal -->
        <CustomerDetailModal
            :show="showDetailModal"
            :customer="selectedCustomer"
            @close="showDetailModal = false"
            @edit="openEditModal"
        />

        <!-- Delete Confirmation Dialog -->
        <ConfirmDialog
            :show="showDeleteDialog"
            title="Hapus Customer"
            :message="`Apakah Anda yakin ingin memindahkan customer '${customerToDelete?.name}' ke Tempat Sampah (Recycle Bin)? Data dapat dipulihkan kapan saja.`"
            confirm-button-text="Ya, Pindahkan ke Sampah"
            confirm-variant="danger"
            :processing="isDeleting"
            @confirm="executeDelete"
            @cancel="showDeleteDialog = false"
        />
    </AuthenticatedTemplate>
</template>
