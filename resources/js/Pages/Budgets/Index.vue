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
import BudgetDetailModal from './Partials/BudgetDetailModal.vue';
import BudgetFormModal from './Partials/BudgetFormModal.vue';

const props = defineProps({
    budgets: Object,
    users: Array,
    classifications: Array,
    stats: Object,
    filters: Object,
    suggestedCode: String,
});

// Search & Filter state
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const classificationFilter = ref(props.filters.classification_id || '');
let searchTimeout = null;

const activeFiltersCount = computed(() => {
    let count = 0;
    if (statusFilter.value !== '') count++;
    if (classificationFilter.value) count++;
    return count;
});

const applyFilters = () => {
    router.get(
        route('budgets.index'),
        {
            search: search.value || undefined,
            status: statusFilter.value !== '' ? statusFilter.value : undefined,
            classification_id: classificationFilter.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch([statusFilter, classificationFilter], () => {
    applyFilters();
});

const resetFilters = () => {
    search.value = '';
    statusFilter.value = '';
    classificationFilter.value = '';
    applyFilters();
};

// Helper to generate code
const isGeneratingCode = ref(false);
const fetchGeneratedCode = async (targetForm) => {
    isGeneratingCode.value = true;
    try {
        const response = await fetch(route('budgets.generate-code'));
        if (response.ok) {
            const data = await response.json();
            targetForm.code = data.code;
        } else {
            targetForm.code =
                props.suggestedCode || `BDG-${new Date().getFullYear()}-0001`;
        }
    } catch {
        targetForm.code =
            props.suggestedCode || `BDG-${new Date().getFullYear()}-0001`;
    } finally {
        isGeneratingCode.value = false;
    }
};

// Create Modal & Form State
const showCreateModal = ref(false);
const createForm = useForm({
    code: '',
    name: '',
    budget_classification_id: '',
    pic_id: '',
    description: '',
    is_active: true,
    checkers: [{ user_id: '', role_title: 'Checker 1' }],
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.code = props.suggestedCode || '';
    createForm.budget_classification_id = '';
    createForm.is_active = true;
    createForm.checkers = [{ user_id: '', role_title: 'Checker 1' }];
    showCreateModal.value = true;
};

const addCreateChecker = () => {
    const nextLevel = createForm.checkers.length + 1;
    createForm.checkers.push({
        user_id: '',
        role_title: `Checker ${nextLevel}`,
    });
};

const removeCreateChecker = (index) => {
    if (createForm.checkers.length > 1) {
        createForm.checkers.splice(index, 1);
    }
};

const submitCreate = () => {
    createForm.code = createForm.code.toUpperCase().trim();
    createForm.post(route('budgets.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// Edit Modal & Form State
const showEditModal = ref(false);
const budgetToEdit = ref(null);
const editForm = useForm({
    code: '',
    name: '',
    budget_classification_id: '',
    pic_id: '',
    description: '',
    is_active: true,
    checkers: [],
});

const openEditModal = (budget) => {
    budgetToEdit.value = budget;
    editForm.reset();
    editForm.clearErrors();

    editForm.code = budget.code;
    editForm.name = budget.name;
    editForm.budget_classification_id = budget.budget_classification_id || '';
    editForm.pic_id = budget.pic_id || '';
    editForm.description = budget.description || '';
    editForm.is_active = Boolean(budget.is_active);

    if (budget.checkers && budget.checkers.length > 0) {
        editForm.checkers = budget.checkers.map((c) => ({
            id: c.id,
            user_id: c.user_id,
            role_title: c.role_title,
        }));
    } else {
        editForm.checkers = [{ user_id: '', role_title: 'Checker 1' }];
    }

    showEditModal.value = true;
};

const addEditChecker = () => {
    const nextLevel = editForm.checkers.length + 1;
    editForm.checkers.push({
        user_id: '',
        role_title: `Checker ${nextLevel}`,
    });
};

const removeEditChecker = (index) => {
    if (editForm.checkers.length > 1) {
        editForm.checkers.splice(index, 1);
    }
};

const submitEdit = () => {
    if (!budgetToEdit.value) return;
    editForm.code = editForm.code.toUpperCase().trim();
    editForm.put(route('budgets.update', budgetToEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            budgetToEdit.value = null;
        },
    });
};

// Detail Modal State
const showDetailModal = ref(false);
const budgetDetail = ref(null);

const openDetailModal = (budget) => {
    budgetDetail.value = budget;
    showDetailModal.value = true;
};

// Delete Modal State & Action
const showDeleteModal = ref(false);
const budgetToDelete = ref(null);

const openDeleteModal = (budget) => {
    budgetToDelete.value = budget;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!budgetToDelete.value) return;
    router.delete(route('budgets.destroy', budgetToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            budgetToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Master Budget" />

    <AuthenticatedTemplate>
        <!-- PAGE HEADER ORGANISM -->
        <PageHeader
            title="Master Budget"
            subtitle="Kelola mata anggaran perusahaan, klasifikasi akun biaya, penanggung jawab (PIC), serta alur persetujuan bertingkat (checkers)."
            :breadcrumbs="[
                { label: 'Dashboard', href: route('dashboard') },
                { label: 'Master Data' },
                { label: 'Budget' },
            ]"
        >
            <template #badge>
                <Badge variant="primary" size="sm">
                    {{ stats.total }} Anggaran
                </Badge>
            </template>

            <template #actions>
                <Button variant="primary" size="md" @click="openCreateModal">
                    <template #icon-left>
                        <Icon name="plus" class="h-4 w-4" />
                    </template>
                    Tambah Budget Baru
                </Button>
            </template>
        </PageHeader>

        <!-- STATS OVERVIEW CARDS (Responsive Grid: 2 cols on mobile, 5 on lg) -->
        <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
            <StatsCard
                title="Total Budget"
                :value="stats.total"
                subtitle="Semua mata anggaran"
                icon-name="calendar"
                icon-color="blue"
            />
            <StatsCard
                title="Budget Aktif"
                :value="stats.active"
                subtitle="Dapat diajukan transaksi"
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
                title="PIC Terdaftar"
                :value="stats.total_pic"
                subtitle="Penanggung jawab"
                icon-name="user"
                icon-color="blue"
            />
            <StatsCard
                title="Klasifikasi"
                :value="stats.total_classifications"
                subtitle="Kategori anggaran"
                icon-name="building"
                icon-color="amber"
            />
        </div>

        <!-- FILTER BAR ORGANISM -->
        <FilterBar
            v-model:search="search"
            search-placeholder="Cari kode budget, nama anggaran, atau PIC..."
            :active-filters-count="activeFiltersCount"
            @reset="resetFilters"
        >
            <template #filters>
                <!-- Status Filter Pills -->
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

                <!-- Classification Select Filter -->
                <SelectInput
                    v-model="classificationFilter"
                    size="sm"
                    class="w-full sm:w-auto"
                >
                    <option value="">Semua Klasifikasi</option>
                    <option
                        v-for="c in classifications"
                        :key="c.id"
                        :value="c.id"
                    >
                        {{ c.name }}
                    </option>
                </SelectInput>
            </template>
        </FilterBar>

        <!-- RESPONSIVE DATA TABLE ORGANISM -->
        <DataTable
            :items="budgets.data"
            empty-title="Belum Ada Data Budget"
            empty-description="Belum ada data anggaran yang cocok dengan kriteria pencarian Anda."
            empty-action-text="Buat Budget Sekarang"
            @empty-action="openCreateModal"
        >
            <!-- 1. MOBILE CARD LIST VIEW (< md) -->
            <template #card="{ item }">
                <div class="space-y-3">
                    <!-- Card Header -->
                    <div
                        class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2 dark:border-slate-700/60"
                    >
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="font-mono text-xs font-bold text-slate-900 dark:text-white"
                                >
                                    {{ item.code }}
                                </span>
                                <Badge
                                    v-if="item.classification"
                                    variant="primary"
                                    size="sm"
                                >
                                    {{ item.classification.name }}
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

                    <!-- Card Body -->
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="block text-[11px] text-slate-400"
                                >PIC Budget</span
                            >
                            <span
                                class="font-medium text-slate-800 dark:text-slate-200"
                            >
                                {{ item.pic?.name || '-' }}
                            </span>
                        </div>
                        <div>
                            <span class="block text-[11px] text-slate-400"
                                >Alur Approval</span
                            >
                            <span
                                class="font-semibold text-blue-600 dark:text-blue-400"
                            >
                                {{ item.checkers?.length || 0 }} Tingkat Checker
                            </span>
                        </div>
                        <div
                            v-if="item.description"
                            class="col-span-2 truncate text-[11px] text-slate-500"
                        >
                            {{ item.description }}
                        </div>
                    </div>

                    <!-- Card Actions -->
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
                        Kode & Nama Budget
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">
                        Klasifikasi
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">
                        PIC Budget
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">
                        Alur Persetujuan (Checkers)
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">Status</th>
                    <th scope="col" class="px-5 py-3.5 text-end font-bold">
                        Aksi
                    </th>
                </tr>
            </template>

            <template #row="{ item }">
                <!-- Kode & Nama -->
                <td class="px-5 py-4">
                    <div class="flex flex-col">
                        <span
                            class="font-mono text-xs font-bold text-slate-900 dark:text-white"
                        >
                            {{ item.code }}
                        </span>
                        <span
                            class="mt-0.5 text-sm font-bold text-slate-900 dark:text-white"
                        >
                            {{ item.name }}
                        </span>
                        <span
                            v-if="item.description"
                            class="max-w-xs truncate text-[11px] text-slate-500 dark:text-slate-400"
                        >
                            {{ item.description }}
                        </span>
                    </div>
                </td>

                <!-- Klasifikasi -->
                <td class="px-5 py-4">
                    <Badge
                        v-if="item.classification"
                        variant="primary"
                        size="sm"
                    >
                        {{ item.classification.name }}
                    </Badge>
                    <span v-else class="text-xs text-slate-400">-</span>
                </td>

                <!-- PIC -->
                <td class="px-5 py-4">
                    <div v-if="item.pic" class="text-xs">
                        <p class="font-semibold text-slate-900 dark:text-white">
                            {{ item.pic.name }}
                        </p>
                        <p
                            class="text-[11px] text-slate-500 dark:text-slate-400"
                        >
                            {{ item.pic.email }}
                        </p>
                    </div>
                    <span v-else class="text-xs text-slate-400">-</span>
                </td>

                <!-- Checkers -->
                <td class="px-5 py-4">
                    <div
                        v-if="item.checkers && item.checkers.length > 0"
                        class="flex flex-wrap gap-1"
                    >
                        <span
                            v-for="chk in item.checkers"
                            :key="chk.id"
                            class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-300"
                        >
                            {{ chk.role_title }}: {{ chk.user?.name || '-' }}
                        </span>
                    </div>
                    <span v-else class="text-xs text-slate-400"
                        >Tidak ada checker</span
                    >
                </td>

                <!-- Status -->
                <td class="px-5 py-4">
                    <Badge
                        :variant="item.is_active ? 'success' : 'danger'"
                        dot
                        size="md"
                    >
                        {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                    </Badge>
                </td>

                <!-- Actions -->
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

            <!-- Pagination Slot -->
            <template #pagination>
                <Pagination :links="budgets.links" />
            </template>
        </DataTable>

        <!-- CREATE MODAL -->
        <BudgetFormModal
            :show="showCreateModal"
            title="Tambah Mata Anggaran (Budget) Baru"
            :form="createForm"
            :users="users"
            :classifications="classifications"
            :is-generating-code="isGeneratingCode"
            @close="showCreateModal = false"
            @submit="submitCreate"
            @refresh-code="fetchGeneratedCode(createForm)"
            @add-checker="addCreateChecker"
            @remove-checker="removeCreateChecker"
        />

        <!-- EDIT MODAL -->
        <BudgetFormModal
            :show="showEditModal"
            :title="`Ubah Data Budget: ${budgetToEdit?.name || ''}`"
            :form="editForm"
            :users="users"
            :classifications="classifications"
            :is-generating-code="isGeneratingCode"
            is-edit
            @close="showEditModal = false"
            @submit="submitEdit"
            @refresh-code="fetchGeneratedCode(editForm)"
            @add-checker="addEditChecker"
            @remove-checker="removeEditChecker"
        />

        <!-- DETAIL MODAL -->
        <BudgetDetailModal
            :show="showDetailModal"
            :budget="budgetDetail"
            @close="showDetailModal = false"
            @edit="
                (b) => {
                    showDetailModal = false;
                    openEditModal(b);
                }
            "
        />

        <!-- DELETE CONFIRMATION -->
        <ConfirmDialog
            :show="showDeleteModal"
            title="Hapus Data Budget"
            :message="`Apakah Anda yakin ingin menghapus budget '${budgetToDelete?.name}' (${budgetToDelete?.code})? Data transaksi anggaran terkait akan terpengaruh.`"
            confirm-text="Ya, Hapus Budget"
            confirm-variant="danger"
            @close="showDeleteModal = false"
            @confirm="executeDelete"
        />
    </AuthenticatedTemplate>
</template>
