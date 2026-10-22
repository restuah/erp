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
    classifications: Object,
    stats: Object,
    filters: Object,
});

// Search & filter state
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
        route('budget-classifications.index'),
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

// Create Modal State & Form
const showCreateModal = ref(false);
const createForm = useForm({
    name: '',
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
    createForm.post(route('budget-classifications.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// Edit Modal State & Form
const showEditModal = ref(false);
const classificationToEdit = ref(null);
const editForm = useForm({
    name: '',
    description: '',
    is_active: true,
});

const openEditModal = (item) => {
    classificationToEdit.value = item;
    editForm.reset();
    editForm.clearErrors();
    editForm.name = item.name;
    editForm.description = item.description || '';
    editForm.is_active = Boolean(item.is_active);
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!classificationToEdit.value) return;
    editForm.put(
        route('budget-classifications.update', classificationToEdit.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                showEditModal.value = false;
                classificationToEdit.value = null;
            },
        },
    );
};

// Detail Modal State
const showDetailModal = ref(false);
const classificationDetail = ref(null);

const openDetailModal = (item) => {
    classificationDetail.value = item;
    showDetailModal.value = true;
};

// Delete Modal State & Action
const showDeleteModal = ref(false);
const classificationToDelete = ref(null);

const openDeleteModal = (item) => {
    classificationToDelete.value = item;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!classificationToDelete.value) return;
    router.delete(
        route(
            'budget-classifications.destroy',
            classificationToDelete.value.id,
        ),
        {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                classificationToDelete.value = null;
            },
        },
    );
};
</script>

<template>
    <Head title="Master Klasifikasi Budget" />

    <AuthenticatedTemplate>
        <!-- PAGE HEADER ORGANISM -->
        <PageHeader
            title="Klasifikasi Budget"
            subtitle="Pengelompokan kategori pos beban anggaran perusahaan (Capex, Opex, Biaya Operasional, dsb)."
            :breadcrumbs="[
                { label: 'Dashboard', href: route('dashboard') },
                { label: 'Master Data' },
                { label: 'Klasifikasi Budget' },
            ]"
        >
            <template #badge>
                <Badge variant="primary" size="sm">
                    {{ stats.total }} Klasifikasi
                </Badge>
            </template>

            <template #actions>
                <Button variant="primary" size="md" @click="openCreateModal">
                    <template #icon-left>
                        <Icon name="plus" class="h-4 w-4" />
                    </template>
                    Tambah Klasifikasi
                </Button>
            </template>
        </PageHeader>

        <!-- STATS OVERVIEW CARDS -->
        <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
            <StatsCard
                title="Total Klasifikasi"
                :value="stats.total"
                subtitle="Semua kategori anggaran"
                icon-name="building"
                icon-color="blue"
            />
            <StatsCard
                title="Status Aktif"
                :value="stats.active"
                subtitle="Dapat digunakan di budget"
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
        </div>

        <!-- FILTER BAR ORGANISM -->
        <FilterBar
            v-model:search="search"
            search-placeholder="Cari nama klasifikasi atau deskripsi..."
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
            :items="classifications.data"
            empty-title="Belum Ada Klasifikasi Budget"
            empty-description="Belum ada kategori anggaran yang cocok dengan pencarian Anda."
            empty-action-text="Tambah Klasifikasi Sekarang"
            @empty-action="openCreateModal"
        >
            <!-- 1. MOBILE CARD LIST VIEW (< md) -->
            <template #card="{ item }">
                <div class="space-y-3">
                    <div
                        class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2 dark:border-slate-700/60"
                    >
                        <h3
                            class="text-sm font-bold text-slate-900 dark:text-white"
                        >
                            {{ item.name }}
                        </h3>

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
                        Nama Klasifikasi
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
                        class="text-sm font-bold text-slate-900 dark:text-white"
                    >
                        {{ item.name }}
                    </span>
                </td>
                <td
                    class="max-w-md truncate px-5 py-4 text-xs text-slate-500 dark:text-slate-400"
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
                <Pagination :links="classifications.links" />
            </template>
        </DataTable>

        <!-- CREATE MODAL -->
        <ModalDialog
            :show="showCreateModal"
            title="Tambah Klasifikasi Budget Baru"
            max-width="md"
            @close="showCreateModal = false"
        >
            <form @submit.prevent="submitCreate" class="space-y-4">
                <FormField
                    label="Nama Klasifikasi"
                    required
                    :error="createForm.errors.name"
                >
                    <TextInput
                        v-model="createForm.name"
                        placeholder="Contoh: Operasional / Capex / Opex"
                        :error="!!createForm.errors.name"
                    />
                </FormField>

                <FormField
                    label="Deskripsi"
                    :error="createForm.errors.description"
                >
                    <TextareaInput
                        v-model="createForm.description"
                        rows="3"
                        placeholder="Keterangan klasifikasi anggaran ini..."
                        :error="!!createForm.errors.description"
                    />
                </FormField>

                <div class="flex items-center gap-2 pt-1">
                    <Checkbox
                        id="create_classification_active"
                        v-model:checked="createForm.is_active"
                    />
                    <InputLabel
                        for="create_classification_active"
                        value="Status Aktif (Tersedia untuk anggaran)"
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
                    Simpan Klasifikasi
                </Button>
            </template>
        </ModalDialog>

        <!-- EDIT MODAL -->
        <ModalDialog
            :show="showEditModal"
            :title="`Ubah Klasifikasi: ${classificationToEdit?.name || ''}`"
            max-width="md"
            @close="showEditModal = false"
        >
            <form @submit.prevent="submitEdit" class="space-y-4">
                <FormField
                    label="Nama Klasifikasi"
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
                        rows="3"
                        :error="!!editForm.errors.description"
                    />
                </FormField>

                <div class="flex items-center gap-2 pt-1">
                    <Checkbox
                        id="edit_classification_active"
                        v-model:checked="editForm.is_active"
                    />
                    <InputLabel
                        for="edit_classification_active"
                        value="Status Aktif (Tersedia untuk anggaran)"
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
            title="Detail Klasifikasi Budget"
            max-width="md"
            @close="showDetailModal = false"
        >
            <div v-if="classificationDetail" class="space-y-4 text-xs">
                <div
                    class="flex items-center justify-between rounded-xl border border-slate-200/80 bg-slate-50 p-3 dark:border-slate-700/60 dark:bg-slate-800/60"
                >
                    <h4
                        class="text-sm font-bold text-slate-900 dark:text-white"
                    >
                        {{ classificationDetail.name }}
                    </h4>
                    <Badge
                        :variant="
                            classificationDetail.is_active
                                ? 'success'
                                : 'danger'
                        "
                        dot
                        size="sm"
                    >
                        {{
                            classificationDetail.is_active
                                ? 'Aktif'
                                : 'Nonaktif'
                        }}
                    </Badge>
                </div>

                <div
                    class="space-y-1 rounded-xl border border-slate-200/80 p-3 dark:border-slate-700/60"
                >
                    <span class="block text-[11px] text-slate-400"
                        >Deskripsi:</span
                    >
                    <p class="text-slate-800 dark:text-slate-200">
                        {{ classificationDetail.description || '-' }}
                    </p>
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
                        openEditModal(classificationDetail);
                    "
                >
                    <template #icon-left>
                        <Icon name="edit" class="h-4 w-4" />
                    </template>
                    Ubah Klasifikasi
                </Button>
            </template>
        </ModalDialog>

        <!-- DELETE CONFIRMATION -->
        <ConfirmDialog
            :show="showDeleteModal"
            title="Hapus Klasifikasi Budget"
            :message="`Apakah Anda yakin ingin menghapus klasifikasi '${classificationToDelete?.name}'? Seluruh budget yang menggunakan klasifikasi ini akan terpengaruh.`"
            confirm-text="Ya, Hapus Klasifikasi"
            @close="showDeleteModal = false"
            @confirm="executeDelete"
        />
    </AuthenticatedTemplate>
</template>
