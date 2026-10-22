<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Badge from '@/Components/Atoms/Badge.vue';
import Button from '@/Components/Atoms/Button.vue';
import Checkbox from '@/Components/Atoms/Checkbox.vue';
import Icon from '@/Components/Atoms/Icon.vue';
import InputLabel from '@/Components/Atoms/InputLabel.vue';
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
    units: Object,
    stats: Object,
    categories: Object,
    filters: Object,
});

// Search & filter state
const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category || '');
const statusFilter = ref(props.filters.status || '');
let searchTimeout = null;

const activeFiltersCount = computed(() => {
    let count = 0;
    if (categoryFilter.value) count++;
    if (statusFilter.value !== '') count++;
    return count;
});

const applyFilters = () => {
    router.get(
        route('unit-of-measures.index'),
        {
            search: search.value || undefined,
            category: categoryFilter.value || undefined,
            status: statusFilter.value !== '' ? statusFilter.value : undefined,
        },
        { preserveState: true, replace: true },
    );
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch([categoryFilter, statusFilter], () => {
    applyFilters();
});

const resetFilters = () => {
    search.value = '';
    categoryFilter.value = '';
    statusFilter.value = '';
    applyFilters();
};

// Create Modal State & Form
const showCreateModal = ref(false);
const createForm = useForm({
    code: '',
    name: '',
    category: '',
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
    createForm.post(route('unit-of-measures.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// Edit Modal State & Form
const showEditModal = ref(false);
const unitToEdit = ref(null);
const editForm = useForm({
    code: '',
    name: '',
    category: '',
    description: '',
    is_active: true,
});

const openEditModal = (unit) => {
    unitToEdit.value = unit;
    editForm.reset();
    editForm.clearErrors();
    editForm.code = unit.code;
    editForm.name = unit.name;
    editForm.category = unit.category || '';
    editForm.description = unit.description || '';
    editForm.is_active = Boolean(unit.is_active);
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!unitToEdit.value) return;
    editForm.code = editForm.code.toUpperCase().trim();
    editForm.put(route('unit-of-measures.update', unitToEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            unitToEdit.value = null;
        },
    });
};

// Detail Modal State
const showDetailModal = ref(false);
const unitDetail = ref(null);

const openDetailModal = (unit) => {
    unitDetail.value = unit;
    showDetailModal.value = true;
};

// Delete Modal State & Action
const showDeleteModal = ref(false);
const unitToDelete = ref(null);

const openDeleteModal = (unit) => {
    unitToDelete.value = unit;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!unitToDelete.value) return;
    router.delete(route('unit-of-measures.destroy', unitToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            unitToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Master Satuan Ukur (UOM)" />

    <AuthenticatedTemplate>
        <!-- PAGE HEADER ORGANISM -->
        <PageHeader
            title="Satuan Ukur (UOM)"
            subtitle="Standarisasi unit satuan pengukuran barang (kg, pcs, box, meter, liter, dsb) untuk inventaris dan pengadaan."
            :breadcrumbs="[
                { label: 'Dashboard', href: route('dashboard') },
                { label: 'Master Data' },
                { label: 'Satuan Ukur' },
            ]"
        >
            <template #badge>
                <Badge variant="primary" size="sm">
                    {{ stats.total }} Satuan
                </Badge>
            </template>

            <template #actions>
                <Button variant="primary" size="md" @click="openCreateModal">
                    <template #icon-left>
                        <Icon name="plus" class="h-4 w-4" />
                    </template>
                    Tambah Satuan Baru
                </Button>
            </template>
        </PageHeader>

        <!-- STATS OVERVIEW CARDS -->
        <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <StatsCard
                title="Total Satuan"
                :value="stats.total"
                subtitle="Semua UOM terdaftar"
                icon-name="building"
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
                title="Kategori"
                :value="Object.keys(categories || {}).length"
                subtitle="Berat, Volume, Jumlah, dll"
                icon-name="calendar"
                icon-color="blue"
            />
        </div>

        <!-- FILTER BAR ORGANISM -->
        <FilterBar
            v-model:search="search"
            search-placeholder="Cari kode satuan (cth: KG, PCS) atau nama..."
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

                <SelectInput
                    v-model="categoryFilter"
                    size="sm"
                    class="w-full sm:w-auto"
                >
                    <option value="">Semua Kategori</option>
                    <option
                        v-for="(label, key) in categories"
                        :key="key"
                        :value="key"
                    >
                        {{ label }}
                    </option>
                </SelectInput>
            </template>
        </FilterBar>

        <!-- RESPONSIVE DATA TABLE ORGANISM -->
        <DataTable
            :items="units.data"
            empty-title="Belum Ada Satuan Ukur"
            empty-description="Belum ada data satuan ukur yang cocok dengan kriteria pencarian Anda."
            empty-action-text="Tambah Satuan Sekarang"
            @empty-action="openCreateModal"
        >
            <!-- 1. MOBILE CARD LIST VIEW (< md) -->
            <template #card="{ item }">
                <div class="space-y-3">
                    <div
                        class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2 dark:border-slate-700/60"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="font-mono text-xs font-bold text-blue-600 dark:text-blue-400"
                                >
                                    {{ item.code }}
                                </span>
                                <Badge
                                    v-if="
                                        categories && categories[item.category]
                                    "
                                    variant="primary"
                                    size="sm"
                                >
                                    {{ categories[item.category] }}
                                </Badge>
                            </div>
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
                        Kode Satuan
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">
                        Nama Satuan
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">Kategori</th>
                    <th scope="col" class="px-5 py-3.5 font-bold">Deskripsi</th>
                    <th scope="col" class="px-5 py-3.5 font-bold">Status</th>
                    <th scope="col" class="px-5 py-3.5 text-end font-bold">
                        Aksi
                    </th>
                </tr>
            </template>

            <template #row="{ item }">
                <td
                    class="px-5 py-4 font-mono text-xs font-bold text-blue-600 dark:text-blue-400"
                >
                    {{ item.code }}
                </td>
                <td class="px-5 py-4 font-bold text-slate-900 dark:text-white">
                    {{ item.name }}
                </td>
                <td class="px-5 py-4">
                    <Badge
                        v-if="categories && categories[item.category]"
                        variant="primary"
                        size="sm"
                    >
                        {{ categories[item.category] }}
                    </Badge>
                    <span v-else class="text-xs text-slate-400">-</span>
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
                <Pagination :links="units.links" />
            </template>
        </DataTable>

        <!-- CREATE MODAL -->
        <ModalDialog
            :show="showCreateModal"
            title="Tambah Satuan Ukur Baru"
            max-width="md"
            @close="showCreateModal = false"
        >
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField
                        label="Kode Satuan"
                        required
                        :error="createForm.errors.code"
                    >
                        <TextInput
                            v-model="createForm.code"
                            placeholder="Contoh: KG, PCS, BOX"
                            class="font-mono font-bold uppercase"
                            :error="!!createForm.errors.code"
                        />
                    </FormField>

                    <FormField
                        label="Kategori Satuan"
                        required
                        :error="createForm.errors.category"
                    >
                        <SelectInput
                            v-model="createForm.category"
                            :error="!!createForm.errors.category"
                        >
                            <option value="">-- Pilih Kategori --</option>
                            <option
                                v-for="(label, key) in categories"
                                :key="key"
                                :value="key"
                            >
                                {{ label }}
                            </option>
                        </SelectInput>
                    </FormField>
                </div>

                <FormField
                    label="Nama Satuan"
                    required
                    :error="createForm.errors.name"
                >
                    <TextInput
                        v-model="createForm.name"
                        placeholder="Contoh: Kilogram / Pieces"
                        :error="!!createForm.errors.name"
                    />
                </FormField>

                <FormField
                    label="Deskripsi"
                    :error="createForm.errors.description"
                >
                    <TextareaInput
                        v-model="createForm.description"
                        rows="2"
                        placeholder="Keterangan penggunaan satuan ini..."
                        :error="!!createForm.errors.description"
                    />
                </FormField>

                <div class="flex items-center gap-2 pt-1">
                    <Checkbox
                        id="create_unit_active"
                        v-model:checked="createForm.is_active"
                    />
                    <InputLabel
                        for="create_unit_active"
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
                    Simpan Satuan
                </Button>
            </template>
        </ModalDialog>

        <!-- EDIT MODAL -->
        <ModalDialog
            :show="showEditModal"
            :title="`Ubah Satuan: ${unitToEdit?.code || ''}`"
            max-width="md"
            @close="showEditModal = false"
        >
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField
                        label="Kode Satuan"
                        required
                        :error="editForm.errors.code"
                    >
                        <TextInput
                            v-model="editForm.code"
                            class="font-mono font-bold uppercase"
                            :error="!!editForm.errors.code"
                        />
                    </FormField>

                    <FormField
                        label="Kategori Satuan"
                        required
                        :error="editForm.errors.category"
                    >
                        <SelectInput
                            v-model="editForm.category"
                            :error="!!editForm.errors.category"
                        >
                            <option value="">-- Pilih Kategori --</option>
                            <option
                                v-for="(label, key) in categories"
                                :key="key"
                                :value="key"
                            >
                                {{ label }}
                            </option>
                        </SelectInput>
                    </FormField>
                </div>

                <FormField
                    label="Nama Satuan"
                    required
                    :error="editForm.errors.name"
                >
                    <TextInput
                        v-model="editForm.name"
                        :error="!!editForm.errors.name"
                    />
                </FormField>

                <FormField
                    label="Deskripsi"
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
                        id="edit_unit_active"
                        v-model:checked="editForm.is_active"
                    />
                    <InputLabel
                        for="edit_unit_active"
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
            title="Detail Satuan Ukur"
            max-width="md"
            @close="showDetailModal = false"
        >
            <div v-if="unitDetail" class="space-y-4 text-xs">
                <div
                    class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-slate-50 p-3 dark:border-slate-700/60 dark:bg-slate-800/60"
                >
                    <div>
                        <span
                            class="font-mono text-sm font-bold text-blue-600 dark:text-blue-400"
                        >
                            {{ unitDetail.code }}
                        </span>
                        <h4
                            class="mt-0.5 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            {{ unitDetail.name }}
                        </h4>
                    </div>
                    <Badge
                        :variant="unitDetail.is_active ? 'success' : 'danger'"
                        dot
                        size="sm"
                    >
                        {{ unitDetail.is_active ? 'Aktif' : 'Nonaktif' }}
                    </Badge>
                </div>

                <div
                    class="space-y-2 rounded-xl border border-slate-200/80 p-3 dark:border-slate-700/60"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Kategori:</span>
                        <Badge
                            v-if="categories && categories[unitDetail.category]"
                            variant="primary"
                            size="sm"
                        >
                            {{ categories[unitDetail.category] }}
                        </Badge>
                        <span v-else class="text-slate-500">-</span>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <span class="text-slate-400">Deskripsi:</span>
                        <span
                            class="text-end text-slate-800 dark:text-slate-200"
                            >{{ unitDetail.description || '-' }}</span
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
                        openEditModal(unitDetail);
                    "
                >
                    <template #icon-left>
                        <Icon name="edit" class="h-4 w-4" />
                    </template>
                    Ubah Satuan
                </Button>
            </template>
        </ModalDialog>

        <!-- DELETE CONFIRMATION -->
        <ConfirmDialog
            :show="showDeleteModal"
            title="Hapus Satuan Ukur"
            :message="`Apakah Anda yakin ingin menghapus satuan '${unitToDelete?.code}' (${unitToDelete?.name})?`"
            confirm-text="Ya, Hapus Satuan"
            @close="showDeleteModal = false"
            @confirm="executeDelete"
        />
    </AuthenticatedTemplate>
</template>
