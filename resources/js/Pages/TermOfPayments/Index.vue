<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Badge from '@/Components/Atoms/Badge.vue';
import Button from '@/Components/Atoms/Button.vue';
import Checkbox from '@/Components/Atoms/Checkbox.vue';
import Icon from '@/Components/Atoms/Icon.vue';
import InputLabel from '@/Components/Atoms/InputLabel.vue';
import TextInput from '@/Components/Atoms/TextInput.vue';
import TextareaInput from '@/Components/Atoms/TextareaInput.vue';
import ConfirmDialog from '@/Components/Molecules/ConfirmDialog.vue';
import FilterPills from '@/Components/Molecules/FilterPills.vue';
import FormField from '@/Components/Molecules/FormField.vue';
import Pagination from '@/Components/Molecules/Pagination.vue';
import StatsCard from '@/Components/Molecules/StatsCard.vue';
import DataTable from '@/Components/Organisms/DataTable.vue';
import FilterBar from '@/Components/Organisms/FilterBar.vue';
import ModalDialog from '@/Components/Organisms/ModalDialog.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import AuthenticatedTemplate from '@/Components/Templates/AuthenticatedTemplate.vue';

const props = defineProps({
    terms: Object,
    stats: Object,
    filters: Object,
});

// Search & Filter state
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
let searchTimeout = null;

const activeFiltersCount = computed(() => {
    let count = 0;
    if (statusFilter.value !== '') count++;
    return count;
});

const applyFilters = () => {
    router.get(
        route('term-of-payments.index'),
        {
            search: search.value || undefined,
            status: statusFilter.value !== '' ? statusFilter.value : undefined,
        },
        { preserveState: true, replace: true },
    );
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch(statusFilter, () => {
    applyFilters();
});

const resetFilters = () => {
    search.value = '';
    statusFilter.value = '';
    applyFilters();
};

// Create Modal & Form State
const showCreateModal = ref(false);
const createForm = useForm({
    payment: '',
    name: '',
    days: 0,
    description: '',
    is_active: true,
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.days = 0;
    createForm.is_active = true;
    showCreateModal.value = true;
};

const submitCreate = () => {
    createForm.payment = createForm.payment.toUpperCase().trim();
    createForm.post(route('term-of-payments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// Edit Modal & Form State
const showEditModal = ref(false);
const termToEdit = ref(null);
const editForm = useForm({
    payment: '',
    name: '',
    days: 0,
    description: '',
    is_active: true,
});

const openEditModal = (term) => {
    termToEdit.value = term;
    editForm.reset();
    editForm.clearErrors();
    editForm.payment = term.payment;
    editForm.name = term.name;
    editForm.days = term.days;
    editForm.description = term.description || '';
    editForm.is_active = Boolean(term.is_active);
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!termToEdit.value) return;
    editForm.payment = editForm.payment.toUpperCase().trim();
    editForm.put(route('term-of-payments.update', termToEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            termToEdit.value = null;
        },
    });
};

// Detail Modal State
const showDetailModal = ref(false);
const termDetail = ref(null);

const openDetailModal = (term) => {
    termDetail.value = term;
    showDetailModal.value = true;
};

// Delete Modal State & Action
const showDeleteModal = ref(false);
const termToDelete = ref(null);

const openDeleteModal = (term) => {
    termToDelete.value = term;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!termToDelete.value) return;
    router.delete(route('term-of-payments.destroy', termToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            termToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Master Term of Payment (TOP)" />

    <AuthenticatedTemplate>
        <!-- PAGE HEADER ORGANISM -->
        <PageHeader
            title="Term of Payment (TOP)"
            subtitle="Konfigurasi ketentuan jangka waktu pembayaran faktur kepada rekanan vendor / supplier maupun pelanggan."
            :breadcrumbs="[
                { label: 'Dashboard', href: route('dashboard') },
                { label: 'Master Data' },
                { label: 'Term of Payment' },
            ]"
        >
            <template #badge>
                <Badge variant="primary" size="sm">
                    {{ stats.total }} Termin
                </Badge>
            </template>

            <template #actions>
                <Button variant="primary" size="md" @click="openCreateModal">
                    <template #icon-left>
                        <Icon name="plus" class="h-4 w-4" />
                    </template>
                    Tambah Termin Baru
                </Button>
            </template>
        </PageHeader>

        <!-- STATS OVERVIEW CARDS -->
        <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <StatsCard
                title="Total Termin"
                :value="stats.total"
                subtitle="Semua ketentuan pembayaran"
                icon-name="calendar"
                icon-color="blue"
            />
            <StatsCard
                title="Status Aktif"
                :value="stats.active"
                subtitle="Dapat dipilih di transaksi"
                icon-name="check"
                icon-color="emerald"
            />
            <StatsCard
                title="Nonaktif"
                :value="stats.inactive"
                subtitle="Ditangguhkan"
                icon-name="alert"
                icon-color="rose"
            />
            <StatsCard
                title="Rata-rata Hari"
                :value="`${stats.avg_days || 0} Hari`"
                subtitle="Rata-rata jatuh tempo"
                icon-name="dollar"
                icon-color="blue"
            />
        </div>

        <!-- FILTER BAR ORGANISM -->
        <FilterBar
            v-model:search="search"
            search-placeholder="Cari kode termin (cth: COD, NET 30) atau nama..."
            :active-filters-count="activeFiltersCount"
            @reset="resetFilters"
        >
            <template #filters>
                <FilterPills
                    v-model="statusFilter"
                    :options="[
                        { label: 'Semua Status', value: '' },
                        { label: 'Aktif', value: '1', count: stats.active },
                        {
                            label: 'Nonaktif',
                            value: '0',
                            count: stats.inactive,
                        },
                    ]"
                />
            </template>
        </FilterBar>

        <!-- RESPONSIVE DATA TABLE ORGANISM -->
        <DataTable
            :items="terms.data"
            empty-title="Belum Ada Term of Payment"
            empty-description="Belum ada data ketentuan pembayaran yang cocok dengan pencarian Anda."
            empty-action-text="Tambah Termin Sekarang"
            @empty-action="openCreateModal"
        >
            <!-- 1. MOBILE CARD LIST VIEW (< md) -->
            <template #card="{ item }">
                <div class="space-y-3">
                    <div
                        class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2 dark:border-slate-700/60"
                    >
                        <div>
                            <span
                                class="font-mono text-xs font-bold text-blue-600 dark:text-blue-400"
                            >
                                {{ item.payment }}
                            </span>
                            <h3
                                class="mt-0.5 text-sm font-bold text-slate-900 dark:text-white"
                            >
                                {{ item.name }}
                            </h3>
                        </div>

                        <Badge
                            :variant="item.is_active ? 'success' : 'danger'"
                            dot
                            size="sm"
                        >
                            {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                        </Badge>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500">Jangka Waktu:</span>
                        <span class="font-bold text-slate-900 dark:text-white"
                            >{{ item.days }} Hari</span
                        >
                    </div>

                    <p
                        v-if="item.description"
                        class="truncate text-xs text-slate-500 dark:text-slate-400"
                    >
                        {{ item.description }}
                    </p>

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

            <!-- 2. DESKTOP DATA TABLE (>= md) -->
            <template #head>
                <tr>
                    <th scope="col" class="px-5 py-3.5 font-bold">
                        Kode Pembayaran
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">
                        Nama Ketentuan
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">
                        Jangka Waktu (Hari)
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">Deskripsi</th>
                    <th scope="col" class="px-5 py-3.5 font-bold">Status</th>
                    <th scope="col" class="px-5 py-3.5 text-end font-bold">
                        Aksi
                    </th>
                </tr>
            </template>

            <template #row="{ item }">
                <td class="px-5 py-4">
                    <span
                        class="font-mono text-xs font-bold text-blue-600 dark:text-blue-400"
                    >
                        {{ item.payment }}
                    </span>
                </td>
                <td class="px-5 py-4">
                    <span
                        class="text-sm font-bold text-slate-900 dark:text-white"
                    >
                        {{ item.name }}
                    </span>
                </td>
                <td
                    class="px-5 py-4 font-semibold text-slate-800 dark:text-slate-200"
                >
                    {{ item.days }} Hari
                </td>
                <td
                    class="max-w-xs truncate px-5 py-4 text-xs text-slate-500 dark:text-slate-400"
                >
                    {{ item.description || '-' }}
                </td>
                <td class="px-5 py-4">
                    <Badge
                        :variant="item.is_active ? 'success' : 'danger'"
                        dot
                        size="md"
                    >
                        {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                    </Badge>
                </td>
                <td class="px-5 py-4 text-end">
                    <div class="flex items-center justify-end gap-1.5">
                        <Button
                            variant="secondary"
                            size="xs"
                            @click="openDetailModal(item)"
                            title="Detail"
                        >
                            <Icon name="eye" class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="secondary"
                            size="xs"
                            @click="openEditModal(item)"
                            title="Ubah"
                        >
                            <Icon name="edit" class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="danger"
                            size="xs"
                            @click="openDeleteModal(item)"
                            title="Hapus"
                        >
                            <Icon name="trash" class="h-4 w-4" />
                        </Button>
                    </div>
                </td>
            </template>

            <template #pagination>
                <Pagination :links="terms.links" />
            </template>
        </DataTable>

        <!-- CREATE MODAL -->
        <ModalDialog
            :show="showCreateModal"
            title="Tambah Term of Payment Baru"
            max-width="lg"
            @close="showCreateModal = false"
        >
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField
                        label="Kode Pembayaran (Payment)"
                        required
                        :error="createForm.errors.payment"
                    >
                        <TextInput
                            v-model="createForm.payment"
                            placeholder="Contoh: NET 30, COD"
                            class="font-mono font-bold uppercase"
                            :error="!!createForm.errors.payment"
                        />
                    </FormField>

                    <FormField
                        label="Jumlah Hari Jatuh Tempo"
                        required
                        :error="createForm.errors.days"
                    >
                        <TextInput
                            v-model="createForm.days"
                            type="number"
                            min="0"
                            placeholder="30"
                            :error="!!createForm.errors.days"
                        />
                    </FormField>
                </div>

                <FormField
                    label="Nama Lengkap Ketentuan"
                    required
                    :error="createForm.errors.name"
                >
                    <TextInput
                        v-model="createForm.name"
                        placeholder="Contoh: Pembayaran 30 Hari Setelah Invoice"
                        :error="!!createForm.errors.name"
                    />
                </FormField>

                <FormField
                    label="Deskripsi / Catatan"
                    :error="createForm.errors.description"
                >
                    <TextareaInput
                        v-model="createForm.description"
                        rows="2"
                        placeholder="Keterangan tambahan termin ini..."
                        :error="!!createForm.errors.description"
                    />
                </FormField>

                <div class="flex items-center gap-2 pt-1">
                    <Checkbox
                        id="create_term_active"
                        v-model:checked="createForm.is_active"
                    />
                    <InputLabel
                        for="create_term_active"
                        value="Status Aktif (Tersedia untuk transaksi)"
                        class="mb-0 cursor-pointer"
                    />
                </div>
            </form>

            <template #footer>
                <Button
                    variant="secondary"
                    size="md"
                    @click="showCreateModal = false"
                >
                    Batal
                </Button>
                <Button
                    variant="primary"
                    size="md"
                    :loading="createForm.processing"
                    @click="submitCreate"
                >
                    <template #icon-left>
                        <Icon name="check" class="h-4 w-4" />
                    </template>
                    Simpan Termin
                </Button>
            </template>
        </ModalDialog>

        <!-- EDIT MODAL -->
        <ModalDialog
            :show="showEditModal"
            :title="`Ubah Term of Payment: ${termToEdit?.payment || ''}`"
            max-width="lg"
            @close="showEditModal = false"
        >
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField
                        label="Kode Pembayaran (Payment)"
                        required
                        :error="editForm.errors.payment"
                    >
                        <TextInput
                            v-model="editForm.payment"
                            placeholder="Contoh: NET 30"
                            class="font-mono font-bold uppercase"
                            :error="!!editForm.errors.payment"
                        />
                    </FormField>

                    <FormField
                        label="Jumlah Hari Jatuh Tempo"
                        required
                        :error="editForm.errors.days"
                    >
                        <TextInput
                            v-model="editForm.days"
                            type="number"
                            min="0"
                            :error="!!editForm.errors.days"
                        />
                    </FormField>
                </div>

                <FormField
                    label="Nama Lengkap Ketentuan"
                    required
                    :error="editForm.errors.name"
                >
                    <TextInput
                        v-model="editForm.name"
                        :error="!!editForm.errors.name"
                    />
                </FormField>

                <FormField
                    label="Deskripsi / Catatan"
                    :error="editForm.errors.description"
                >
                    <TextareaInput
                        v-model="editForm.description"
                        rows="2"
                        :error="!!editForm.errors.description"
                    />
                </FormField>

                <div class="flex items-center gap-2 pt-1">
                    <Checkbox
                        id="edit_term_active"
                        v-model:checked="editForm.is_active"
                    />
                    <InputLabel
                        for="edit_term_active"
                        value="Status Aktif (Tersedia untuk transaksi)"
                        class="mb-0 cursor-pointer"
                    />
                </div>
            </form>

            <template #footer>
                <Button
                    variant="secondary"
                    size="md"
                    @click="showEditModal = false"
                >
                    Batal
                </Button>
                <Button
                    variant="primary"
                    size="md"
                    :loading="editForm.processing"
                    @click="submitEdit"
                >
                    <template #icon-left>
                        <Icon name="check" class="h-4 w-4" />
                    </template>
                    Simpan Perubahan
                </Button>
            </template>
        </ModalDialog>

        <!-- DETAIL MODAL -->
        <ModalDialog
            :show="showDetailModal"
            title="Detail Term of Payment"
            max-width="md"
            @close="showDetailModal = false"
        >
            <div v-if="termDetail" class="space-y-4 text-xs">
                <div
                    class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-slate-50 p-3 dark:border-slate-700/60 dark:bg-slate-800/60"
                >
                    <div>
                        <span
                            class="font-mono text-sm font-bold text-blue-600 dark:text-blue-400"
                        >
                            {{ termDetail.payment }}
                        </span>
                        <h4
                            class="mt-0.5 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            {{ termDetail.name }}
                        </h4>
                    </div>
                    <Badge
                        :variant="termDetail.is_active ? 'success' : 'danger'"
                        dot
                        size="sm"
                    >
                        {{ termDetail.is_active ? 'Aktif' : 'Nonaktif' }}
                    </Badge>
                </div>

                <div
                    class="space-y-2 rounded-xl border border-slate-200/80 p-3 dark:border-slate-700/60"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Jangka Waktu Hari:</span>
                        <span
                            class="font-bold text-slate-800 dark:text-slate-200"
                            >{{ termDetail.days }} Hari</span
                        >
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-slate-400">Deskripsi:</span>
                        <span
                            class="text-end text-slate-800 dark:text-slate-200"
                            >{{ termDetail.description || '-' }}</span
                        >
                    </div>
                </div>
            </div>

            <template #footer>
                <Button
                    variant="secondary"
                    size="md"
                    @click="showDetailModal = false"
                >
                    Tutup
                </Button>
                <Button
                    variant="primary"
                    size="md"
                    @click="
                        showDetailModal = false;
                        openEditModal(termDetail);
                    "
                >
                    <template #icon-left>
                        <Icon name="edit" class="h-4 w-4" />
                    </template>
                    Ubah Termin
                </Button>
            </template>
        </ModalDialog>

        <!-- DELETE CONFIRMATION -->
        <ConfirmDialog
            :show="showDeleteModal"
            title="Hapus Term of Payment"
            :message="`Apakah Anda yakin ingin menghapus termin '${termToDelete?.payment}' (${termToDelete?.name})?`"
            confirm-text="Ya, Hapus Termin"
            @close="showDeleteModal = false"
            @confirm="executeDelete"
        />
    </AuthenticatedTemplate>
</template>
