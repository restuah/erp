<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    units: Object,
    stats: Object,
    categories: Object,
    filters: Object,
});

// Search & filter states
const search = ref(props.filters.search || '');
const categoryFilter = ref(props.filters.category || '');
const statusFilter = ref(props.filters.status || '');
let searchTimeout = null;

const applyFilters = () => {
    router.get(
        route('unit-of-measures.index'),
        {
            search: search.value || undefined,
            category: categoryFilter.value || undefined,
            status: statusFilter.value !== '' ? statusFilter.value : undefined,
        },
        { preserveState: true, replace: true }
    );
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch(categoryFilter, () => {
    applyFilters();
});

watch(statusFilter, () => {
    applyFilters();
});

const resetFilters = () => {
    search.value = '';
    categoryFilter.value = '';
    statusFilter.value = '';
    applyFilters();
};

// Helper for category badge
const getCategoryBadgeClass = (catKey) => {
    switch (catKey) {
        case 'count':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800/40';
        case 'weight':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/40';
        case 'length':
            return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800/40';
        case 'volume':
            return 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800/40';
        case 'area':
            return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/40';
        case 'time':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 border border-purple-200 dark:border-purple-800/40';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700';
    }
};

const getCategoryLabel = (catKey) => {
    return props.categories[catKey]?.label || catKey;
};

// Create Modal State & Form
const showCreateModal = ref(false);
const createForm = useForm({
    code: '',
    name: '',
    symbol: '',
    category: 'count',
    description: '',
    is_active: true,
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.category = 'count';
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
    symbol: '',
    category: 'count',
    description: '',
    is_active: true,
});

const openEditModal = (unit) => {
    unitToEdit.value = unit;
    editForm.reset();
    editForm.clearErrors();
    editForm.code = unit.code;
    editForm.name = unit.name;
    editForm.symbol = unit.symbol || '';
    editForm.category = unit.category;
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

// Delete Modal State & Action
const showDeleteModal = ref(false);
const unitToDelete = ref(null);
const isDeleting = ref(false);

const openDeleteModal = (unit) => {
    unitToDelete.value = unit;
    showDeleteModal.value = true;
};

const submitDelete = () => {
    if (!unitToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('unit-of-measures.destroy', unitToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            unitToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Master Satuan Ukur (UOM)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="flex items-center gap-2.5 text-xl font-bold text-gray-900 dark:text-white">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </span>
                        Master Satuan Ukur (UOM)
                    </h1>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Kelola data master satuan ukuran (Unit of Measure) untuk transaksi inventaris, pembelian, dan operasional.
                    </p>
                </div>
                <div>
                    <PrimaryButton
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 shadow-xs"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Satuan
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stats Widgets -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total UOM -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs transition-shadow hover:shadow-md dark:border-gray-700/60 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Satuan</p>
                            <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</h3>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Active UOM -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs transition-shadow hover:shadow-md dark:border-gray-700/60 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Satuan Aktif</p>
                            <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ stats.active }}</h3>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Inactive UOM -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs transition-shadow hover:shadow-md dark:border-gray-700/60 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Satuan Nonaktif</p>
                            <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ stats.inactive }}</h3>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100 text-gray-500 dark:bg-gray-700/60 dark:text-gray-400">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Categories Count -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs transition-shadow hover:shadow-md dark:border-gray-700/60 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Kategori Satuan</p>
                            <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ stats.categories_count }}</h3>
                        </div>
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-xs md:flex-row md:items-center md:justify-between dark:border-gray-700/60 dark:bg-gray-800">
                <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-72">
                        <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                            <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari kode, nama, simbol..."
                            class="block w-full rounded-lg border border-gray-300 bg-white py-2 ps-9 pe-3 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder-gray-500"
                        />
                    </div>

                    <!-- Category Filter -->
                    <div class="w-full sm:w-52">
                        <select
                            v-model="categoryFilter"
                            class="block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        >
                            <option value="">Semua Kategori</option>
                            <option
                                v-for="(cat, key) in categories"
                                :key="key"
                                :value="key"
                            >
                                {{ cat.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="w-full sm:w-44">
                        <select
                            v-model="statusFilter"
                            class="block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        >
                            <option value="">Semua Status</option>
                            <option value="true">Aktif</option>
                            <option value="false">Nonaktif</option>
                        </select>
                    </div>

                    <!-- Reset Filter Button -->
                    <button
                        v-if="search || categoryFilter || statusFilter !== ''"
                        @click="resetFilters"
                        type="button"
                        class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                    >
                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reset Filter
                    </button>
                </div>
            </div>

            <!-- Table Data Card -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-700/60 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-sm text-gray-600 dark:text-gray-300">
                        <thead class="border-b border-gray-200 bg-gray-50/80 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:border-gray-700/60 dark:bg-gray-900/40 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3.5 text-start">Kode Satuan</th>
                                <th class="px-6 py-3.5 text-start">Nama Satuan</th>
                                <th class="px-6 py-3.5 text-start">Simbol</th>
                                <th class="px-6 py-3.5 text-start">Kategori</th>
                                <th class="px-6 py-3.5 text-start">Keterangan</th>
                                <th class="px-6 py-3.5 text-start">Status</th>
                                <th class="px-6 py-3.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700/60">
                            <tr
                                v-for="unit in units.data"
                                :key="unit.id"
                                class="transition-colors hover:bg-gray-50/70 dark:hover:bg-gray-700/40"
                            >
                                <!-- Kode Satuan -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1 font-mono text-xs font-bold text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-950/50 dark:text-indigo-300 dark:ring-indigo-400/20">
                                        {{ unit.code }}
                                    </span>
                                </td>

                                <!-- Nama Satuan -->
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                                    {{ unit.name }}
                                </td>

                                <!-- Simbol -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="unit.symbol" class="rounded bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ unit.symbol }}
                                    </span>
                                    <span v-else class="text-gray-400 dark:text-gray-500 text-xs">-</span>
                                </td>

                                <!-- Kategori -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="getCategoryBadgeClass(unit.category)"
                                    >
                                        {{ getCategoryLabel(unit.category) }}
                                    </span>
                                </td>

                                <!-- Keterangan -->
                                <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                    {{ unit.description || '-' }}
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="
                                            unit.is_active
                                                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/30'
                                                : 'bg-gray-100 text-gray-600 ring-1 ring-inset ring-gray-500/20 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-600/30'
                                        "
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="unit.is_active ? 'bg-emerald-500' : 'bg-gray-400'"
                                        ></span>
                                        {{ unit.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 whitespace-nowrap text-end">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Edit -->
                                        <button
                                            @click="openEditModal(unit)"
                                            type="button"
                                            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-indigo-400"
                                            title="Ubah Satuan"
                                        >
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <!-- Delete -->
                                        <button
                                            @click="openDeleteModal(unit)"
                                            type="button"
                                            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600 dark:text-gray-400 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                                            title="Hapus Satuan"
                                        >
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="units.data.length === 0" class="py-16 text-center">
                        <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-700/60 dark:text-gray-500">
                            <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Tidak ada data satuan ukur</h4>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ search || categoryFilter || statusFilter !== '' ? 'Coba sesuaikan kata kunci atau filter pencarian Anda.' : 'Mulai tambahkan satuan ukuran baru untuk operasional ERP.' }}
                        </p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="units.data.length > 0" class="border-t border-gray-200 px-6 py-4 dark:border-gray-700/60">
                    <Pagination :links="units.links" />
                </div>
            </div>
        </div>

        <!-- MODAL: Tambah Satuan -->
        <Modal :show="showCreateModal" @close="showCreateModal = false" max-width="lg">
            <form @submit.prevent="submitCreate" class="p-6">
                <div class="mb-5 flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-700/60">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </span>
                        Tambah Satuan (UOM)
                    </h3>
                    <button @click="showCreateModal = false" type="button" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Kode & Simbol Grid -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Kode Satuan -->
                        <div>
                            <InputLabel for="create_code" value="Kode Satuan *" />
                            <TextInput
                                id="create_code"
                                v-model="createForm.code"
                                type="text"
                                class="mt-1 block w-full uppercase font-mono"
                                placeholder="cth: PCS, KG, M"
                                required
                            />
                            <InputError class="mt-1" :message="createForm.errors.code" />
                        </div>

                        <!-- Simbol -->
                        <div>
                            <InputLabel for="create_symbol" value="Simbol (Opsional)" />
                            <TextInput
                                id="create_symbol"
                                v-model="createForm.symbol"
                                type="text"
                                class="mt-1 block w-full font-mono"
                                placeholder="cth: pcs, kg, m"
                            />
                            <InputError class="mt-1" :message="createForm.errors.symbol" />
                        </div>
                    </div>

                    <!-- Nama Satuan -->
                    <div>
                        <InputLabel for="create_name" value="Nama Satuan *" />
                        <TextInput
                            id="create_name"
                            v-model="createForm.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="cth: Pieces / Buah, Kilogram"
                            required
                        />
                        <InputError class="mt-1" :message="createForm.errors.name" />
                    </div>

                    <!-- Kategori -->
                    <div>
                        <InputLabel for="create_category" value="Kategori Satuan *" />
                        <select
                            id="create_category"
                            v-model="createForm.category"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            required
                        >
                            <option
                                v-for="(cat, key) in categories"
                                :key="key"
                                :value="key"
                            >
                                {{ cat.label }}
                            </option>
                        </select>
                        <InputError class="mt-1" :message="createForm.errors.category" />
                    </div>

                    <!-- Keterangan -->
                    <div>
                        <InputLabel for="create_description" value="Keterangan (Opsional)" />
                        <textarea
                            id="create_description"
                            v-model="createForm.description"
                            rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm"
                            placeholder="Penjelasan penggunaan satuan..."
                        ></textarea>
                        <InputError class="mt-1" :message="createForm.errors.description" />
                    </div>

                    <!-- Status Aktif -->
                    <div class="flex items-center pt-1">
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input
                                type="checkbox"
                                v-model="createForm.is_active"
                                class="peer sr-only"
                            />
                            <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:top-[2px] after:start-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:border-gray-600 dark:bg-gray-700"></div>
                            <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Satuan Aktif
                            </span>
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700/60">
                    <SecondaryButton @click="showCreateModal = false" :disabled="createForm.processing">
                        Batal
                    </SecondaryButton>
                    <PrimaryButton :disabled="createForm.processing">
                        Simpan Satuan
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- MODAL: Ubah Satuan -->
        <Modal :show="showEditModal" @close="showEditModal = false" max-width="lg">
            <form @submit.prevent="submitEdit" class="p-6">
                <div class="mb-5 flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-700/60">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </span>
                        Ubah Satuan (UOM)
                    </h3>
                    <button @click="showEditModal = false" type="button" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Kode & Simbol Grid -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Kode Satuan -->
                        <div>
                            <InputLabel for="edit_code" value="Kode Satuan *" />
                            <TextInput
                                id="edit_code"
                                v-model="editForm.code"
                                type="text"
                                class="mt-1 block w-full uppercase font-mono"
                                placeholder="cth: PCS, KG, M"
                                required
                            />
                            <InputError class="mt-1" :message="editForm.errors.code" />
                        </div>

                        <!-- Simbol -->
                        <div>
                            <InputLabel for="edit_symbol" value="Simbol (Opsional)" />
                            <TextInput
                                id="edit_symbol"
                                v-model="editForm.symbol"
                                type="text"
                                class="mt-1 block w-full font-mono"
                                placeholder="cth: pcs, kg, m"
                            />
                            <InputError class="mt-1" :message="editForm.errors.symbol" />
                        </div>
                    </div>

                    <!-- Nama Satuan -->
                    <div>
                        <InputLabel for="edit_name" value="Nama Satuan *" />
                        <TextInput
                            id="edit_name"
                            v-model="editForm.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="cth: Pieces / Buah, Kilogram"
                            required
                        />
                        <InputError class="mt-1" :message="editForm.errors.name" />
                    </div>

                    <!-- Kategori -->
                    <div>
                        <InputLabel for="edit_category" value="Kategori Satuan *" />
                        <select
                            id="edit_category"
                            v-model="editForm.category"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            required
                        >
                            <option
                                v-for="(cat, key) in categories"
                                :key="key"
                                :value="key"
                            >
                                {{ cat.label }}
                            </option>
                        </select>
                        <InputError class="mt-1" :message="editForm.errors.category" />
                    </div>

                    <!-- Keterangan -->
                    <div>
                        <InputLabel for="edit_description" value="Keterangan (Opsional)" />
                        <textarea
                            id="edit_description"
                            v-model="editForm.description"
                            rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm"
                            placeholder="Penjelasan penggunaan satuan..."
                        ></textarea>
                        <InputError class="mt-1" :message="editForm.errors.description" />
                    </div>

                    <!-- Status Aktif -->
                    <div class="flex items-center pt-1">
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input
                                type="checkbox"
                                v-model="editForm.is_active"
                                class="peer sr-only"
                            />
                            <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:top-[2px] after:start-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:border-gray-600 dark:bg-gray-700"></div>
                            <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Satuan Aktif
                            </span>
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700/60">
                    <SecondaryButton @click="showEditModal = false" :disabled="editForm.processing">
                        Batal
                    </SecondaryButton>
                    <PrimaryButton :disabled="editForm.processing">
                        Perbarui Satuan
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- MODAL: Hapus Satuan (Pindah ke Tempat Sampah) -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false" max-width="md">
            <div class="p-6">
                <div class="mb-4 flex items-center gap-3 text-red-600 dark:text-red-400">
                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-red-100 dark:bg-red-950/60">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Pindahkan ke Tempat Sampah?</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Data dapat dipulihkan kapan saja melalui Recycle Bin.</p>
                    </div>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Apakah Anda yakin ingin memindahkan satuan
                    <strong class="text-gray-900 dark:text-white">{{ unitToDelete?.code }} - {{ unitToDelete?.name }}</strong>
                    ke tempat sampah?
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showDeleteModal = false" :disabled="isDeleting">
                        Batal
                    </SecondaryButton>
                    <DangerButton @click="submitDelete" :disabled="isDeleting">
                        Ya, Pindahkan ke Tempat Sampah
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
