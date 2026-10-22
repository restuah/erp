<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
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
import SupplierDetailModal from './Partials/SupplierDetailModal.vue';
import SupplierFormModal from './Partials/SupplierFormModal.vue';

const props = defineProps({
    suppliers: Object,
    stats: Object,
    termOfPayments: Array,
    currencies: Array,
    filters: Object,
    suggestedCodes: Object,
});

// Search & Filter state
const search = ref(props.filters.search || '');
const typeFilter = ref(props.filters.type || '');
const statusFilter = ref(props.filters.status || '');
const termFilter = ref(props.filters.term_of_payment_id || '');
let searchTimeout = null;

const activeFiltersCount = computed(() => {
    let count = 0;
    if (typeFilter.value) count++;
    if (statusFilter.value) count++;
    if (termFilter.value) count++;
    return count;
});

const applyFilters = () => {
    router.get(
        route('suppliers.index'),
        {
            search: search.value || undefined,
            type: typeFilter.value || undefined,
            status: statusFilter.value || undefined,
            term_of_payment_id: termFilter.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch([typeFilter, statusFilter, termFilter], () => {
    applyFilters();
});

const resetFilters = () => {
    search.value = '';
    typeFilter.value = '';
    statusFilter.value = '';
    termFilter.value = '';
    applyFilters();
};

// Create Modal & Form State
const showCreateModal = ref(false);
const isGeneratingCode = ref(false);

const createForm = useForm({
    supplier_type: 'direct',
    supplier_code: props.suggestedCodes?.direct || '',
    supplier_name: '',
    status: 'active',
    term_of_payment_id: '',
    address: '',
    zipcode: '',
    npwp: '',
    office_phone: '',
    fax: '',
    marketing_name: '',
    marketing_phone: '',
    marketing_email: '',
    accounting_name: '',
    accounting_phone: '',
    accounting_email: '',
    bank_name: '',
    bank_account_name: '',
    bank_account_number: '',
    default_currency: 'IDR',
    country_code: 'ID',
    delivery_period: '',
    delivery_time: '',
    delivery_end_time: '',
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.supplier_type = 'direct';
    createForm.supplier_code = props.suggestedCodes?.direct || '';
    createForm.status = 'active';
    createForm.default_currency = 'IDR';
    createForm.country_code = 'ID';
    showCreateModal.value = true;
};

const onCreateTypeChange = async (newType) => {
    createForm.supplier_type = newType;
    isGeneratingCode.value = true;
    try {
        const response = await fetch(
            route('suppliers.generate-code', { type: newType }),
        );
        const data = await response.json();
        if (data.code) {
            createForm.supplier_code = data.code;
        }
    } catch (e) {
        console.error('Failed to generate code:', e);
    } finally {
        isGeneratingCode.value = false;
    }
};

const refreshCreateCode = async () => {
    await onCreateTypeChange(createForm.supplier_type);
};

const submitCreate = () => {
    createForm.supplier_code = createForm.supplier_code.toUpperCase().trim();
    createForm.default_currency = createForm.default_currency
        ? createForm.default_currency.toUpperCase().trim()
        : 'IDR';
    createForm.country_code = createForm.country_code
        ? createForm.country_code.toUpperCase().trim()
        : 'ID';

    createForm.post(route('suppliers.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// Edit Modal & Form State
const showEditModal = ref(false);
const supplierToEdit = ref(null);
const editForm = useForm({
    supplier_type: 'direct',
    supplier_code: '',
    supplier_name: '',
    status: 'active',
    term_of_payment_id: '',
    address: '',
    zipcode: '',
    npwp: '',
    office_phone: '',
    fax: '',
    marketing_name: '',
    marketing_phone: '',
    marketing_email: '',
    accounting_name: '',
    accounting_phone: '',
    accounting_email: '',
    bank_name: '',
    bank_account_name: '',
    bank_account_number: '',
    default_currency: 'IDR',
    country_code: 'ID',
    delivery_period: '',
    delivery_time: '',
    delivery_end_time: '',
});

const openEditModal = (supplier) => {
    supplierToEdit.value = supplier;
    editForm.reset();
    editForm.clearErrors();

    editForm.supplier_type = supplier.supplier_type;
    editForm.supplier_code = supplier.supplier_code;
    editForm.supplier_name = supplier.supplier_name;
    editForm.status = supplier.status;
    editForm.term_of_payment_id = supplier.term_of_payment_id || '';
    editForm.address = supplier.address || '';
    editForm.zipcode = supplier.zipcode || '';
    editForm.npwp = supplier.npwp || '';
    editForm.office_phone = supplier.office_phone || '';
    editForm.fax = supplier.fax || '';
    editForm.marketing_name = supplier.marketing_name || '';
    editForm.marketing_phone = supplier.marketing_phone || '';
    editForm.marketing_email = supplier.marketing_email || '';
    editForm.accounting_name = supplier.accounting_name || '';
    editForm.accounting_phone = supplier.accounting_phone || '';
    editForm.accounting_email = supplier.accounting_email || '';
    editForm.bank_name = supplier.bank_name || '';
    editForm.bank_account_name = supplier.bank_account_name || '';
    editForm.bank_account_number = supplier.bank_account_number || '';
    editForm.default_currency = supplier.default_currency || 'IDR';
    editForm.country_code = supplier.country_code || 'ID';
    editForm.delivery_period = supplier.delivery_period || '';
    editForm.delivery_time = supplier.delivery_time || '';
    editForm.delivery_end_time = supplier.delivery_end_time || '';

    showEditModal.value = true;
};

const onEditTypeChange = async (newType) => {
    editForm.supplier_type = newType;
};

const refreshEditCode = async () => {
    isGeneratingCode.value = true;
    try {
        const response = await fetch(
            route('suppliers.generate-code', { type: editForm.supplier_type }),
        );
        const data = await response.json();
        if (data.code) {
            editForm.supplier_code = data.code;
        }
    } catch (e) {
        console.error('Failed to generate code:', e);
    } finally {
        isGeneratingCode.value = false;
    }
};

const submitEdit = () => {
    if (!supplierToEdit.value) return;
    editForm.supplier_code = editForm.supplier_code.toUpperCase().trim();
    editForm.default_currency = editForm.default_currency
        ? editForm.default_currency.toUpperCase().trim()
        : 'IDR';
    editForm.country_code = editForm.country_code
        ? editForm.country_code.toUpperCase().trim()
        : 'ID';

    editForm.put(route('suppliers.update', supplierToEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            supplierToEdit.value = null;
        },
    });
};

// Detail Modal State
const showDetailModal = ref(false);
const supplierDetail = ref(null);

const openDetailModal = (supplier) => {
    supplierDetail.value = supplier;
    showDetailModal.value = true;
};

// Delete Modal State & Action
const showDeleteModal = ref(false);
const supplierToDelete = ref(null);

const openDeleteModal = (supplier) => {
    supplierToDelete.value = supplier;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!supplierToDelete.value) return;
    router.delete(route('suppliers.destroy', supplierToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            supplierToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Master Supplier" />

    <AuthenticatedTemplate>
        <!-- PAGE HEADER ORGANISM -->
        <PageHeader
            title="Master Supplier"
            subtitle="Kelola data rekanan pemasok barang & jasa (Supplier Direct dan Indirect), ketentuan pembayaran (TOP), rekening, dan kontak PIC."
            :breadcrumbs="[
                { label: 'Dashboard', href: route('dashboard') },
                { label: 'Master Data' },
                { label: 'Supplier' },
            ]"
        >
            <template #badge>
                <Badge variant="primary" size="sm">
                    {{ stats.total }} Vendor
                </Badge>
            </template>

            <template #actions>
                <Button variant="primary" size="md" @click="openCreateModal">
                    <template #icon-left>
                        <Icon name="plus" class="h-4 w-4" />
                    </template>
                    Tambah Supplier Baru
                </Button>
            </template>
        </PageHeader>

        <!-- STATS OVERVIEW CARDS (Responsive Grid: 2 cols on mobile, 5 on lg) -->
        <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
            <StatsCard
                title="Total Rekanan"
                :value="stats.total"
                subtitle="Semua vendor terdaftar"
                icon-name="building"
                icon-color="blue"
            />
            <StatsCard
                title="Supplier Direct"
                :value="stats.direct"
                subtitle="Bahan baku inti"
                icon-name="building"
                icon-color="blue"
            />
            <StatsCard
                title="Supplier Indirect"
                :value="stats.indirect"
                subtitle="Jasa & umum"
                icon-name="building"
                icon-color="amber"
            />
            <StatsCard
                title="Status Aktif"
                :value="stats.active"
                subtitle="Siap transaksi PO"
                icon-name="check"
                icon-color="emerald"
            />
            <StatsCard
                title="Nonaktif"
                :value="stats.nonactive"
                subtitle="Ditangguhkan"
                icon-name="alert"
                icon-color="rose"
            />
        </div>

        <!-- FILTER BAR ORGANISM -->
        <FilterBar
            v-model:search="search"
            search-placeholder="Cari kode supplier, nama perusahaan, telepon, atau NPWP..."
            :active-filters-count="activeFiltersCount"
            @reset="resetFilters"
        >
            <template #filters>
                <!-- Type Filter Pills -->
                <FilterPills
                    v-model="typeFilter"
                    :options="[
                        { label: 'Semua Tipe', value: '' },
                        {
                            label: 'Direct',
                            value: 'direct',
                            count: stats.direct,
                        },
                        {
                            label: 'Indirect',
                            value: 'indirect',
                            count: stats.indirect,
                        },
                    ]"
                />

                <!-- Status Select Filter -->
                <SelectInput
                    v-model="statusFilter"
                    size="sm"
                    class="w-full sm:w-auto"
                >
                    <option value="">Semua Status</option>
                    <option value="active">Active</option>
                    <option value="nonactive">Nonactive</option>
                </SelectInput>

                <!-- Term of Payment Filter -->
                <SelectInput
                    v-model="termFilter"
                    size="sm"
                    class="w-full sm:w-auto"
                >
                    <option value="">Semua Termin (TOP)</option>
                    <option
                        v-for="top in termOfPayments"
                        :key="top.id"
                        :value="top.id"
                    >
                        {{ top.payment }} ({{ top.days }} Hari)
                    </option>
                </SelectInput>
            </template>
        </FilterBar>

        <!-- RESPONSIVE DATA TABLE ORGANISM -->
        <!-- Desktop: Rich Table | Mobile: Touch-Friendly Card List -->
        <DataTable
            :items="suppliers.data"
            empty-title="Belum Ada Rekanan Supplier"
            empty-description="Belum ada data supplier yang cocok dengan kriteria pencarian Anda."
            empty-action-text="Tambah Supplier Sekarang"
            @empty-action="openCreateModal"
        >
            <!-- 1. MOBILE CARD LIST VIEW (< md) -->
            <template #card="{ item }">
                <div class="space-y-3">
                    <!-- Card Header: Code, Badges, Status -->
                    <div
                        class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2 dark:border-slate-700/60"
                    >
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="font-mono text-xs font-bold text-slate-900 dark:text-white"
                                >
                                    {{ item.supplier_code }}
                                </span>
                                <Badge
                                    :variant="
                                        item.supplier_type === 'direct'
                                            ? 'info'
                                            : 'warning'
                                    "
                                    size="sm"
                                >
                                    {{
                                        item.supplier_type === 'direct'
                                            ? 'Direct'
                                            : 'Indirect'
                                    }}
                                </Badge>
                            </div>
                            <h3
                                class="mt-1 text-sm font-bold text-slate-900 dark:text-white"
                            >
                                {{ item.supplier_name }}
                            </h3>
                        </div>

                        <Badge
                            :variant="
                                item.status === 'active' ? 'success' : 'danger'
                            "
                            dot
                            size="sm"
                        >
                            {{
                                item.status === 'active'
                                    ? 'Active'
                                    : 'Nonactive'
                            }}
                        </Badge>
                    </div>

                    <!-- Card Body Details -->
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="block text-[11px] text-slate-400"
                                >PIC Marketing</span
                            >
                            <span
                                class="font-medium text-slate-800 dark:text-slate-200"
                            >
                                {{ item.marketing_name || '-' }}
                            </span>
                            <p
                                v-if="item.marketing_phone"
                                class="text-[10px] text-slate-500"
                            >
                                {{ item.marketing_phone }}
                            </p>
                        </div>

                        <div>
                            <span class="block text-[11px] text-slate-400"
                                >Termin Pembayaran</span
                            >
                            <span
                                v-if="item.term_of_payment"
                                class="font-semibold text-blue-600 dark:text-blue-400"
                            >
                                {{ item.term_of_payment.payment }} ({{
                                    item.term_of_payment.days
                                }}
                                Hari)
                            </span>
                            <span v-else class="text-slate-400">-</span>
                        </div>

                        <div
                            class="col-span-2 flex items-center justify-between border-t border-slate-50 pt-1 dark:border-slate-700/40"
                        >
                            <span class="text-[11px] text-slate-500"
                                >Mata Uang:
                                <strong>{{
                                    item.default_currency || 'IDR'
                                }}</strong></span
                            >
                            <span
                                v-if="item.bank_name"
                                class="text-[11px] text-slate-500"
                                >Bank:
                                <strong>{{ item.bank_name }}</strong></span
                            >
                        </div>
                    </div>

                    <!-- Card Actions: Touch-friendly buttons (min 44px) -->
                    <div
                        class="flex items-center justify-end gap-2 border-t border-slate-100 pt-2 dark:border-slate-700/60"
                    >
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="openDetailModal(item)"
                            title="Detail"
                        >
                            <template #icon-left>
                                <Icon name="eye" class="h-4 w-4" />
                            </template>
                            Detail
                        </Button>
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="openEditModal(item)"
                            title="Ubah"
                        >
                            <template #icon-left>
                                <Icon name="edit" class="h-4 w-4" />
                            </template>
                            Ubah
                        </Button>
                        <Button
                            variant="danger"
                            size="sm"
                            @click="openDeleteModal(item)"
                            title="Hapus"
                        >
                            <template #icon-left>
                                <Icon name="trash" class="h-4 w-4" />
                            </template>
                        </Button>
                    </div>
                </div>
            </template>

            <!-- 2. DESKTOP / TABLET DATA TABLE (>= md) -->
            <template #head>
                <tr>
                    <th scope="col" class="px-5 py-3.5 font-bold">Supplier</th>
                    <th scope="col" class="px-5 py-3.5 font-bold">Tipe</th>
                    <th scope="col" class="px-5 py-3.5 font-bold">
                        PIC Marketing
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">
                        PIC Accounting & Bank
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">
                        Ketentuan Pembayaran
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">
                        Jadwal Kirim
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">Status</th>
                    <th scope="col" class="px-5 py-3.5 text-end font-bold">
                        Aksi
                    </th>
                </tr>
            </template>

            <template #row="{ item }">
                <!-- Supplier Code & Name -->
                <td class="px-5 py-4">
                    <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                            <span
                                class="font-mono text-xs font-bold text-slate-900 dark:text-white"
                            >
                                {{ item.supplier_code }}
                            </span>
                            <span
                                v-if="item.npwp"
                                class="text-[10px] text-slate-400"
                                :title="'NPWP: ' + item.npwp"
                            >
                                • NPWP
                            </span>
                        </div>
                        <span
                            class="mt-0.5 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            {{ item.supplier_name }}
                        </span>
                        <span
                            v-if="item.office_phone"
                            class="text-[11px] text-slate-500 dark:text-slate-400"
                        >
                            Telp: {{ item.office_phone }}
                        </span>
                    </div>
                </td>

                <!-- Type -->
                <td class="px-5 py-4">
                    <Badge
                        :variant="
                            item.supplier_type === 'direct' ? 'info' : 'warning'
                        "
                        size="sm"
                    >
                        {{
                            item.supplier_type === 'direct'
                                ? 'Direct'
                                : 'Indirect'
                        }}
                    </Badge>
                </td>

                <!-- PIC Marketing -->
                <td class="px-5 py-4">
                    <div
                        v-if="
                            item.marketing_name ||
                            item.marketing_phone ||
                            item.marketing_email
                        "
                        class="space-y-0.5 text-xs"
                    >
                        <p class="font-semibold text-slate-900 dark:text-white">
                            {{ item.marketing_name || '-' }}
                        </p>
                        <p
                            v-if="item.marketing_phone"
                            class="text-slate-500 dark:text-slate-400"
                        >
                            {{ item.marketing_phone }}
                        </p>
                        <p
                            v-if="item.marketing_email"
                            class="text-blue-600 dark:text-blue-400"
                        >
                            {{ item.marketing_email }}
                        </p>
                    </div>
                    <span v-else class="text-xs text-slate-400">-</span>
                </td>

                <!-- PIC Accounting & Bank -->
                <td class="px-5 py-4">
                    <div
                        v-if="
                            item.accounting_name ||
                            item.bank_name ||
                            item.bank_account_number
                        "
                        class="space-y-0.5 text-xs"
                    >
                        <p
                            v-if="item.accounting_name"
                            class="font-semibold text-slate-900 dark:text-white"
                        >
                            {{ item.accounting_name }}
                        </p>
                        <p
                            v-if="item.bank_name"
                            class="text-slate-700 dark:text-slate-300"
                        >
                            {{ item.bank_name }}
                            <span v-if="item.bank_account_number"
                                >- {{ item.bank_account_number }}</span
                            >
                        </p>
                        <p
                            v-if="item.bank_account_name"
                            class="text-[11px] text-slate-500 dark:text-slate-400"
                        >
                            a.n. {{ item.bank_account_name }}
                        </p>
                    </div>
                    <span v-else class="text-xs text-slate-400">-</span>
                </td>

                <!-- Term of Payment & Currency -->
                <td class="px-5 py-4">
                    <div class="space-y-1 text-xs">
                        <div v-if="item.term_of_payment">
                            <span
                                class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-700 dark:bg-purple-950/50 dark:text-purple-300"
                            >
                                {{ item.term_of_payment.payment }}
                            </span>
                            <p
                                class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400"
                            >
                                {{ item.term_of_payment.name }} ({{
                                    item.term_of_payment.days
                                }}
                                Hari)
                            </p>
                        </div>
                        <span v-else class="text-slate-400">Belum diatur</span>

                        <div
                            class="text-[11px] font-semibold text-slate-600 dark:text-slate-400"
                        >
                            Mata Uang: {{ item.default_currency || 'IDR' }}
                        </div>
                    </div>
                </td>

                <!-- Delivery Schedule -->
                <td class="px-5 py-4">
                    <div
                        v-if="item.delivery_period || item.delivery_time"
                        class="space-y-0.5 text-xs"
                    >
                        <p
                            v-if="item.delivery_period"
                            class="font-semibold text-slate-900 dark:text-white"
                        >
                            {{ item.delivery_period }}
                        </p>
                        <p
                            v-if="item.delivery_time || item.delivery_end_time"
                            class="text-[11px] text-slate-500 dark:text-slate-400"
                        >
                            Jam: {{ item.delivery_time || '-' }} s/d
                            {{ item.delivery_end_time || '-' }}
                        </p>
                    </div>
                    <span v-else class="text-xs text-slate-400">-</span>
                </td>

                <!-- Status -->
                <td class="px-5 py-4">
                    <Badge
                        :variant="
                            item.status === 'active' ? 'success' : 'danger'
                        "
                        dot
                        size="md"
                    >
                        {{ item.status === 'active' ? 'Active' : 'Nonactive' }}
                    </Badge>
                </td>

                <!-- Actions Desktop -->
                <td class="px-5 py-4 text-end">
                    <div class="flex items-center justify-end gap-1.5">
                        <Button
                            variant="secondary"
                            size="xs"
                            @click="openDetailModal(item)"
                            title="Lihat Detail Profil"
                        >
                            <Icon name="eye" class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="secondary"
                            size="xs"
                            @click="openEditModal(item)"
                            title="Ubah Data Supplier"
                        >
                            <Icon name="edit" class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="danger"
                            size="xs"
                            @click="openDeleteModal(item)"
                            title="Hapus Supplier"
                        >
                            <Icon name="trash" class="h-4 w-4" />
                        </Button>
                    </div>
                </td>
            </template>

            <!-- Pagination Slot -->
            <template #pagination>
                <Pagination :links="suppliers.links" />
            </template>
        </DataTable>

        <!-- CREATE MODAL (Organism) -->
        <SupplierFormModal
            :show="showCreateModal"
            title="Tambah Rekanan Supplier Baru"
            :form="createForm"
            :term-of-payments="termOfPayments"
            :currencies="currencies"
            :is-generating-code="isGeneratingCode"
            @close="showCreateModal = false"
            @submit="submitCreate"
            @type-change="onCreateTypeChange"
            @refresh-code="refreshCreateCode"
        />

        <!-- EDIT MODAL (Organism) -->
        <SupplierFormModal
            :show="showEditModal"
            :title="`Ubah Data Supplier: ${supplierToEdit?.supplier_name || ''}`"
            :form="editForm"
            :term-of-payments="termOfPayments"
            :currencies="currencies"
            :is-generating-code="isGeneratingCode"
            is-edit
            @close="showEditModal = false"
            @submit="submitEdit"
            @type-change="onEditTypeChange"
            @refresh-code="refreshEditCode"
        />

        <!-- DETAIL MODAL (Organism) -->
        <SupplierDetailModal
            :show="showDetailModal"
            :supplier="supplierDetail"
            @close="showDetailModal = false"
            @edit="
                (s) => {
                    showDetailModal = false;
                    openEditModal(s);
                }
            "
        />

        <!-- DELETE CONFIRMATION DIALOG (Molecule) -->
        <ConfirmDialog
            :show="showDeleteModal"
            title="Hapus Data Supplier"
            :message="`Apakah Anda yakin ingin menghapus rekanan '${supplierToDelete?.supplier_name}' (${supplierToDelete?.supplier_code})? Seluruh riwayat data terkait supplier ini akan dinonaktifkan.`"
            confirm-text="Ya, Hapus Supplier"
            confirm-variant="danger"
            @close="showDeleteModal = false"
            @confirm="executeDelete"
        />
    </AuthenticatedTemplate>
</template>
