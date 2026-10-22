<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Badge from '@/Components/Atoms/Badge.vue';
import Button from '@/Components/Atoms/Button.vue';
import Checkbox from '@/Components/Atoms/Checkbox.vue';
import Icon from '@/Components/Atoms/Icon.vue';
import SelectInput from '@/Components/Atoms/SelectInput.vue';
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
    departments: Object,
    users: Array,
    stats: Object,
    filters: Object,
});

// Search & filter state
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
let searchTimeout = null;

const activeFiltersCount = computed(() => {
    let count = 0;
    if (statusFilter.value !== '') count++;
    return count;
});

const applyFilters = () => {
    router.get(
        route('departments.index'),
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

// Date formatter helper
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

// -----------------------------------------------------------------------------
// CREATE MODAL
// -----------------------------------------------------------------------------
const showCreateModal = ref(false);
const createForm = useForm({
    code: '',
    name: '',
    manager_id: '',
    description: '',
    is_active: true,
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.is_active = true;
    showCreateModal.value = true;
};

const submitCreate = () => {
    createForm.code = createForm.code.toUpperCase().trim();
    createForm.post(route('departments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// -----------------------------------------------------------------------------
// EDIT MODAL
// -----------------------------------------------------------------------------
const showEditModal = ref(false);
const departmentToEdit = ref(null);
const editForm = useForm({
    code: '',
    name: '',
    manager_id: '',
    description: '',
    is_active: true,
});

const openEditModal = (item) => {
    departmentToEdit.value = item;
    editForm.reset();
    editForm.clearErrors();
    editForm.code = item.code || '';
    editForm.name = item.name || '';
    editForm.manager_id = item.manager_id || '';
    editForm.description = item.description || '';
    editForm.is_active = Boolean(item.is_active);
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!departmentToEdit.value) return;
    editForm.code = editForm.code.toUpperCase().trim();
    editForm.put(route('departments.update', departmentToEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            departmentToEdit.value = null;
        },
    });
};

// -----------------------------------------------------------------------------
// VIEW / DETAIL MODAL
// -----------------------------------------------------------------------------
const showDetailModal = ref(false);
const selectedDepartment = ref(null);
const isLoadingDetails = ref(false);

const openDetailModal = async (item) => {
    selectedDepartment.value = {
        ...item,
        users: item.users || [],
        budgets: item.budgets || [],
    };
    showDetailModal.value = true;

    if (!item.users || !item.budgets) {
        isLoadingDetails.value = true;
        try {
            const response = await fetch(route('departments.show', item.id), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            if (response.ok) {
                const data = await response.json();
                if (selectedDepartment.value && selectedDepartment.value.id === item.id) {
                    selectedDepartment.value = {
                        ...selectedDepartment.value,
                        users: data.users || [],
                        budgets: data.budgets || [],
                    };
                }
            }
        } catch (error) {
            console.error('Failed to load department details:', error);
        } finally {
            isLoadingDetails.value = false;
        }
    }
};

const openEditFromDetail = () => {
    if (!selectedDepartment.value) return;
    const dept = selectedDepartment.value;
    showDetailModal.value = false;
    openEditModal(dept);
};

const openDeleteFromDetail = () => {
    if (!selectedDepartment.value) return;
    const dept = selectedDepartment.value;
    showDetailModal.value = false;
    openDeleteModal(dept);
};

// -----------------------------------------------------------------------------
// DELETE MODAL
// -----------------------------------------------------------------------------
const showDeleteConfirm = ref(false);
const departmentToDelete = ref(null);
const deleteProcessing = ref(false);

const openDeleteModal = (item) => {
    departmentToDelete.value = item;
    showDeleteConfirm.value = true;
};

const submitDelete = () => {
    if (!departmentToDelete.value) return;
    deleteProcessing.value = true;

    router.delete(route('departments.destroy', departmentToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteConfirm.value = false;
            departmentToDelete.value = null;
        },
        onFinish: () => {
            deleteProcessing.value = false;
        },
    });
};
</script>

<template>
    <Head title="Master Departemen" />

    <AuthenticatedTemplate>
        <template #header>
            <PageHeader
                title="Master Departemen"
                subtitle="Kelola struktur departemen, penetapan Manager, relasi pengguna (Users), serta kontrol alokasi anggaran (Budgets)."
                :breadcrumbs="[
                    { label: 'Dashboard', href: route('dashboard') },
                    { label: 'Master Data' },
                    { label: 'Departemen' },
                ]"
            >
                <template #actions>
                    <Button
                        variant="primary"
                        size="md"
                        @click="openCreateModal"
                        title="Tambah Departemen Baru"
                    >
                        <template #icon-left>
                            <Icon name="plus" class="h-4 w-4" />
                        </template>
                        Tambah Departemen
                    </Button>
                </template>
            </PageHeader>
        </template>

        <!-- STATS CARDS GRID -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatsCard
                title="Total Departemen"
                :value="stats.total"
                subtitle="Seluruh departemen terdaftar"
                icon-name="building"
                icon-color="indigo"
            />
            <StatsCard
                title="Departemen Aktif"
                :value="stats.active"
                subtitle="Operasional & aktif berjalan"
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
                title="Memiliki Manager"
                :value="stats.with_manager"
                subtitle="Pimpinan departemen ditetapkan"
                icon-name="user"
                icon-color="blue"
            />
        </div>

        <!-- FILTER BAR ORGANISM -->
        <FilterBar
            v-model:search="search"
            search-placeholder="Cari kode, nama departemen, deskripsi, atau manager..."
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
            :items="departments.data"
            empty-title="Belum Ada Data Departemen"
            empty-description="Belum ada data departemen yang cocok dengan kriteria pencarian Anda."
            empty-action-text="Tambah Departemen Sekarang"
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
                                class="inline-block rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-bold text-slate-800 dark:bg-slate-700 dark:text-slate-200"
                            >
                                {{ item.code }}
                            </span>
                            <h3
                                class="mt-1 text-sm font-bold text-slate-900 dark:text-white"
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

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div
                            class="rounded-lg bg-slate-50 p-2 dark:bg-slate-800/60"
                        >
                            <span class="text-slate-500 dark:text-slate-400"
                                >Manager:</span
                            >
                            <div
                                class="mt-0.5 truncate font-semibold text-slate-800 dark:text-slate-200"
                            >
                                {{ item.manager?.name || 'Belum Ditetapkan' }}
                            </div>
                        </div>

                        <div
                            class="rounded-lg bg-slate-50 p-2 dark:bg-slate-800/60"
                        >
                            <span class="text-slate-500 dark:text-slate-400"
                                >Relasi:</span
                            >
                            <div
                                class="mt-0.5 flex items-center gap-2 font-medium text-slate-700 dark:text-slate-300"
                            >
                                <span>{{ item.users_count || 0 }} User</span>
                                <span>•</span>
                                <span
                                    >{{ item.budgets_count || 0 }} Budget</span
                                >
                            </div>
                        </div>
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
                            title="Lihat Detail Departemen"
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
                            title="Ubah Departemen"
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
                            title="Hapus Departemen"
                        >
                            <template #icon-left>
                                <Icon name="trash" class="h-4 w-4" />
                            </template>
                            Hapus
                        </Button>
                    </div>
                </div>
            </template>

            <!-- 2. DESKTOP DATA TABLE (>= md) -->
            <template #head>
                <tr>
                    <th scope="col" class="px-5 py-3.5 font-bold">Kode</th>
                    <th scope="col" class="px-5 py-3.5 font-bold">
                        Nama Departemen
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">
                        Pimpinan (Manager)
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">
                        Relasi Data
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
                        class="inline-block rounded-md bg-slate-100 px-2.5 py-1 font-mono text-xs font-bold text-slate-800 dark:bg-slate-700 dark:text-slate-200"
                    >
                        {{ item.code }}
                    </span>
                </td>
                <td class="px-5 py-4">
                    <span
                        class="text-sm font-bold text-slate-900 dark:text-white"
                    >
                        {{ item.name }}
                    </span>
                </td>
                <td class="px-5 py-4">
                    <div v-if="item.manager" class="flex items-center gap-2">
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-700 dark:bg-blue-900/60 dark:text-blue-300"
                        >
                            {{ item.manager.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <div
                                class="text-xs font-semibold text-slate-800 dark:text-slate-200"
                            >
                                {{ item.manager.name }}
                            </div>
                            <div
                                class="text-[11px] text-slate-400 dark:text-slate-500"
                            >
                                {{ item.manager.email }}
                            </div>
                        </div>
                    </div>
                    <span
                        v-else
                        class="inline-flex items-center gap-1 text-xs italic text-slate-400 dark:text-slate-500"
                    >
                        Belum Ditetapkan
                    </span>
                </td>
                <td class="px-5 py-4">
                    <div class="flex items-center gap-2 text-xs">
                        <span
                            class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                            title="Jumlah User/Karyawan di departemen ini"
                        >
                            <Icon name="user" class="h-3 w-3 text-slate-500" />
                            {{ item.users_count || 0 }} User
                        </span>
                        <span
                            class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                            title="Jumlah Alokasi Budget"
                        >
                            <Icon
                                name="calculator"
                                class="h-3 w-3 text-slate-500"
                            />
                            {{ item.budgets_count || 0 }} Budget
                        </span>
                    </div>
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
                            size="sm"
                            @click="openDetailModal(item)"
                            title="Lihat Detail Departemen"
                        >
                            <template #icon-left>
                                <Icon name="eye" class="h-3.5 w-3.5" />
                            </template>
                        </Button>
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="openEditModal(item)"
                            title="Ubah Departemen"
                        >
                            <template #icon-left>
                                <Icon name="edit" class="h-3.5 w-3.5" />
                            </template>
                        </Button>
                        <Button
                            variant="danger"
                            size="sm"
                            @click="openDeleteModal(item)"
                            title="Hapus Departemen"
                        >
                            <template #icon-left>
                                <Icon name="trash" class="h-3.5 w-3.5" />
                            </template>
                        </Button>
                    </div>
                </td>
            </template>
        </DataTable>

        <!-- PAGINATION MOLECULE -->
        <div v-if="departments.data.length > 0" class="mt-6">
            <Pagination :links="departments.links" />
        </div>

        <!-- ----------------------------------------------------------------- -->
        <!-- CREATE MODAL ORGANISM -->
        <!-- ----------------------------------------------------------------- -->
        <ModalDialog
            :show="showCreateModal"
            title="Tambah Departemen Baru"
            subtitle="Buat master departemen baru untuk struktur organisasi perusahaan"
            max-width="md"
            @close="showCreateModal = false"
        >
            <form @submit.prevent="submitCreate" class="space-y-4 p-6">
                <FormField
                    id="create-code"
                    label="Kode Departemen"
                    required
                    :error="createForm.errors.code"
                    hint="Singkatan unik departemen (contoh: ENG, FIN ACC, PURCH, PPIC)"
                >
                    <TextInput
                        id="create-code"
                        v-model="createForm.code"
                        placeholder="Contoh: ENG"
                        :error="Boolean(createForm.errors.code)"
                        maxlength="50"
                        class="font-mono uppercase"
                    />
                </FormField>

                <FormField
                    id="create-name"
                    label="Nama Departemen"
                    required
                    :error="createForm.errors.name"
                    hint="Nama resmi departemen (contoh: Engineering, Purchasing)"
                >
                    <TextInput
                        id="create-name"
                        v-model="createForm.name"
                        placeholder="Contoh: Engineering"
                        :error="Boolean(createForm.errors.name)"
                        maxlength="150"
                    />
                </FormField>

                <FormField
                    id="create-manager"
                    label="Pimpinan Departemen (Manager)"
                    :error="createForm.errors.manager_id"
                    hint="Opsional: Tentukan pengguna penanggung jawab departemen"
                >
                    <SelectInput
                        id="create-manager"
                        v-model="createForm.manager_id"
                        :error="Boolean(createForm.errors.manager_id)"
                    >
                        <option value="">-- Belum Ditetapkan --</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">
                            {{ u.name }} ({{ u.email }})
                        </option>
                    </SelectInput>
                </FormField>

                <FormField
                    id="create-description"
                    label="Deskripsi / Fungsi"
                    :error="createForm.errors.description"
                >
                    <TextareaInput
                        id="create-description"
                        v-model="createForm.description"
                        placeholder="Tuliskan deskripsi tugas atau fungsi departemen..."
                        :rows="3"
                        :error="Boolean(createForm.errors.description)"
                    />
                </FormField>

                <div class="pt-2">
                    <label class="flex cursor-pointer items-center gap-2">
                        <Checkbox v-model:checked="createForm.is_active" />
                        <span
                            class="text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Departemen Aktif
                        </span>
                    </label>
                </div>

                <div
                    class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-700/60"
                >
                    <Button
                        type="button"
                        variant="secondary"
                        @click="showCreateModal = false"
                    >
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        variant="primary"
                        :loading="createForm.processing"
                    >
                        Simpan Departemen
                    </Button>
                </div>
            </form>
        </ModalDialog>

        <!-- ----------------------------------------------------------------- -->
        <!-- EDIT MODAL ORGANISM -->
        <!-- ----------------------------------------------------------------- -->
        <ModalDialog
            :show="showEditModal"
            title="Ubah Departemen"
            subtitle="Perbarui informasi data departemen"
            max-width="md"
            @close="showEditModal = false"
        >
            <form @submit.prevent="submitEdit" class="space-y-4 p-6">
                <FormField
                    id="edit-code"
                    label="Kode Departemen"
                    required
                    :error="editForm.errors.code"
                >
                    <TextInput
                        id="edit-code"
                        v-model="editForm.code"
                        placeholder="Contoh: ENG"
                        :error="Boolean(editForm.errors.code)"
                        maxlength="50"
                        class="font-mono uppercase"
                    />
                </FormField>

                <FormField
                    id="edit-name"
                    label="Nama Departemen"
                    required
                    :error="editForm.errors.name"
                >
                    <TextInput
                        id="edit-name"
                        v-model="editForm.name"
                        placeholder="Contoh: Engineering"
                        :error="Boolean(editForm.errors.name)"
                        maxlength="150"
                    />
                </FormField>

                <FormField
                    id="edit-manager"
                    label="Pimpinan Departemen (Manager)"
                    :error="editForm.errors.manager_id"
                >
                    <SelectInput
                        id="edit-manager"
                        v-model="editForm.manager_id"
                        :error="Boolean(editForm.errors.manager_id)"
                    >
                        <option value="">-- Belum Ditetapkan --</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">
                            {{ u.name }} ({{ u.email }})
                        </option>
                    </SelectInput>
                </FormField>

                <FormField
                    id="edit-description"
                    label="Deskripsi / Fungsi"
                    :error="editForm.errors.description"
                >
                    <TextareaInput
                        id="edit-description"
                        v-model="editForm.description"
                        placeholder="Tuliskan deskripsi tugas atau fungsi departemen..."
                        :rows="3"
                        :error="Boolean(editForm.errors.description)"
                    />
                </FormField>

                <div class="pt-2">
                    <label class="flex cursor-pointer items-center gap-2">
                        <Checkbox v-model:checked="editForm.is_active" />
                        <span
                            class="text-sm font-medium text-slate-700 dark:text-slate-300"
                        >
                            Departemen Aktif
                        </span>
                    </label>
                </div>

                <div
                    class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-700/60"
                >
                    <Button
                        type="button"
                        variant="secondary"
                        @click="showEditModal = false"
                    >
                        Batal
                    </Button>
                    <Button
                        type="submit"
                        variant="primary"
                        :loading="editForm.processing"
                    >
                        Perbarui Departemen
                    </Button>
                </div>
            </form>
        </ModalDialog>

        <!-- ----------------------------------------------------------------- -->
        <!-- VIEW / DETAIL MODAL ORGANISM -->
        <!-- ----------------------------------------------------------------- -->
        <ModalDialog
            :show="showDetailModal"
            title="Detail Departemen"
            subtitle="Informasi lengkap departemen, manager, anggota, dan anggaran terkait"
            max-width="2xl"
            @close="showDetailModal = false"
        >
            <div v-if="selectedDepartment" class="space-y-6 p-6">
                <!-- Header Banner Card -->
                <div
                    class="flex flex-col gap-3 rounded-xl border border-slate-200/80 bg-slate-50 p-4 dark:border-slate-700/60 dark:bg-slate-800/60 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="shadow-xs flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-600 font-mono text-sm font-bold text-white dark:bg-blue-500"
                        >
                            {{ selectedDepartment.code }}
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold text-slate-900 dark:text-white sm:text-lg"
                            >
                                {{ selectedDepartment.name }}
                            </h3>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                {{
                                    selectedDepartment.description ||
                                    'Tidak ada deskripsi tambahan.'
                                }}
                            </p>
                        </div>
                    </div>
                    <div>
                        <Badge
                            :variant="
                                selectedDepartment.is_active
                                    ? 'success'
                                    : 'danger'
                            "
                            dot
                            size="md"
                        >
                            {{
                                selectedDepartment.is_active
                                    ? 'Departemen Aktif'
                                    : 'Nonaktif'
                            }}
                        </Badge>
                    </div>
                </div>

                <!-- 2-Column Info Grid -->
                <div class="grid grid-cols-1 gap-4 text-xs sm:grid-cols-2">
                    <!-- Manager Info -->
                    <div
                        class="space-y-2 rounded-xl border border-slate-200/80 p-4 dark:border-slate-700/60 dark:bg-slate-800/40"
                    >
                        <h4
                            class="flex items-center gap-1.5 font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                        >
                            <Icon name="user" class="h-4 w-4 text-blue-500" />
                            Pimpinan / Manager
                        </h4>
                        <div
                            v-if="selectedDepartment.manager"
                            class="flex items-center gap-3 pt-1"
                        >
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700 dark:bg-blue-900/60 dark:text-blue-300"
                            >
                                {{
                                    selectedDepartment.manager.name
                                        .charAt(0)
                                        .toUpperCase()
                                }}
                            </div>
                            <div>
                                <div
                                    class="text-sm font-bold text-slate-800 dark:text-slate-200"
                                >
                                    {{ selectedDepartment.manager.name }}
                                </div>
                                <div class="text-slate-500 dark:text-slate-400">
                                    {{ selectedDepartment.manager.email }}
                                </div>
                            </div>
                        </div>
                        <div
                            v-else
                            class="pt-1 italic text-slate-400 dark:text-slate-500"
                        >
                            Belum Ditetapkan
                        </div>
                    </div>

                    <!-- Statistics Info -->
                    <div
                        class="space-y-2 rounded-xl border border-slate-200/80 p-4 dark:border-slate-700/60 dark:bg-slate-800/40"
                    >
                        <h4
                            class="flex items-center gap-1.5 font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                        >
                            <Icon
                                name="database"
                                class="h-4 w-4 text-emerald-500"
                            />
                            Statistik Relasi Sistem
                        </h4>
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <div
                                class="rounded-lg bg-slate-50 p-2.5 dark:bg-slate-800/80"
                            >
                                <span
                                    class="block text-[11px] text-slate-500 dark:text-slate-400"
                                    >Anggota Pengguna:</span
                                >
                                <span
                                    class="text-base font-bold text-slate-800 dark:text-slate-100"
                                >
                                    {{ selectedDepartment.users_count || 0 }}
                                    Orang
                                </span>
                            </div>
                            <div
                                class="rounded-lg bg-slate-50 p-2.5 dark:bg-slate-800/80"
                            >
                                <span
                                    class="block text-[11px] text-slate-500 dark:text-slate-400"
                                    >Alokasi Budget:</span
                                >
                                <span
                                    class="text-base font-bold text-slate-800 dark:text-slate-100"
                                >
                                    {{ selectedDepartment.budgets_count || 0 }}
                                    Pos
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Async Loading State -->
                <div
                    v-if="isLoadingDetails"
                    class="flex items-center justify-center gap-2 rounded-lg border border-blue-100 bg-blue-50/50 py-3 text-xs text-blue-600 dark:border-blue-900/40 dark:bg-blue-950/20 dark:text-blue-400"
                >
                    <Icon name="refresh" class="h-4 w-4 animate-spin" />
                    <span>Memuat rincian anggota & alokasi anggaran...</span>
                </div>

                <!-- Linked Users Section -->
                <div
                    v-if="
                        selectedDepartment.users &&
                        selectedDepartment.users.length > 0
                    "
                    class="space-y-2"
                >
                    <h4
                        class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                    >
                        <Icon name="user" class="h-3.5 w-3.5 text-blue-500" />
                        Daftar Anggota / Karyawan ({{
                            selectedDepartment.users.length
                        }})
                    </h4>
                    <div
                        class="max-h-36 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200 text-xs dark:divide-slate-700/60 dark:border-slate-700"
                    >
                        <div
                            v-for="u in selectedDepartment.users"
                            :key="u.id"
                            class="flex items-center justify-between bg-white px-3 py-2 dark:bg-slate-800/50"
                        >
                            <span
                                class="font-semibold text-slate-800 dark:text-slate-200"
                                >{{ u.name }}</span
                            >
                            <span class="text-slate-400 dark:text-slate-500">{{
                                u.email
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Linked Budgets Section -->
                <div
                    v-if="
                        selectedDepartment.budgets &&
                        selectedDepartment.budgets.length > 0
                    "
                    class="space-y-2"
                >
                    <h4
                        class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400"
                    >
                        <Icon
                            name="calculator"
                            class="h-3.5 w-3.5 text-emerald-500"
                        />
                        Daftar Alokasi Anggaran / Budgets ({{
                            selectedDepartment.budgets.length
                        }})
                    </h4>
                    <div
                        class="max-h-36 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200 text-xs dark:divide-slate-700/60 dark:border-slate-700"
                    >
                        <div
                            v-for="b in selectedDepartment.budgets"
                            :key="b.id"
                            class="flex items-center justify-between bg-white px-3 py-2 dark:bg-slate-800/50"
                        >
                            <span
                                class="font-mono font-bold text-slate-700 dark:text-slate-300"
                                >{{ b.code }}</span
                            >
                            <span class="text-slate-800 dark:text-slate-200">{{
                                b.name
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Audit Footnote -->
                <div
                    class="rounded-lg border border-slate-100 bg-slate-50/50 p-3 text-[11px] text-slate-500 dark:border-slate-800 dark:bg-slate-900/40"
                >
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <div>
                            <span class="text-slate-400">Dibuat oleh:</span>
                            <span
                                class="ml-1 font-semibold text-slate-700 dark:text-slate-300"
                            >
                                {{ selectedDepartment.creator?.name || '-' }}
                            </span>
                            <span class="ml-1 text-slate-400"
                                >({{
                                    formatDate(selectedDepartment.created_at)
                                }})</span
                            >
                        </div>
                        <div>
                            <span class="text-slate-400">Terakhir diubah:</span>
                            <span
                                class="ml-1 font-semibold text-slate-700 dark:text-slate-300"
                            >
                                {{ selectedDepartment.updater?.name || '-' }}
                            </span>
                            <span class="ml-1 text-slate-400"
                                >({{
                                    formatDate(selectedDepartment.updated_at)
                                }})</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Modal Action Footer -->
                <div
                    class="flex items-center justify-between border-t border-slate-100 pt-4 dark:border-slate-700/60"
                >
                    <Button
                        variant="danger"
                        size="sm"
                        @click="openDeleteFromDetail"
                        title="Hapus Departemen Ini"
                    >
                        <template #icon-left>
                            <Icon name="trash" class="h-4 w-4" />
                        </template>
                        Hapus
                    </Button>

                    <div class="flex items-center gap-2">
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="showDetailModal = false"
                        >
                            Tutup
                        </Button>
                        <Button
                            variant="primary"
                            size="sm"
                            @click="openEditFromDetail"
                        >
                            <template #icon-left>
                                <Icon name="edit" class="h-4 w-4" />
                            </template>
                            Ubah Departemen
                        </Button>
                    </div>
                </div>
            </div>
        </ModalDialog>

        <!-- ----------------------------------------------------------------- -->
        <!-- DELETE CONFIRMATION DIALOG MOLECULE -->
        <!-- ----------------------------------------------------------------- -->
        <ConfirmDialog
            :show="showDeleteConfirm"
            title="Hapus Departemen?"
            :message="`Apakah Anda yakin ingin memindahkan departemen '${departmentToDelete?.name}' ke tempat sampah (Recycle Bin)?`"
            confirm-text="Ya, Pindahkan ke Tempat Sampah"
            cancel-text="Batal"
            confirm-variant="danger"
            :loading="deleteProcessing"
            @close="showDeleteConfirm = false"
            @confirm="submitDelete"
        />
    </AuthenticatedTemplate>
</template>
