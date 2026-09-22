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
    classifications: Object,
    stats: Object,
    filters: Object,
});

// Search & Status filters
const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
let searchTimeout = null;

const applyFilters = () => {
    router.get(
        route('budget-classifications.index'),
        {
            search: search.value || undefined,
            status: statusFilter.value !== '' ? statusFilter.value : undefined,
        },
        { preserveState: true, replace: true }
    );
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch(statusFilter, () => {
    applyFilters();
});

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

const openEditModal = (classification) => {
    classificationToEdit.value = classification;
    editForm.reset();
    editForm.clearErrors();
    editForm.name = classification.name;
    editForm.description = classification.description || '';
    editForm.is_active = Boolean(classification.is_active);
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!classificationToEdit.value) return;
    editForm.put(route('budget-classifications.update', classificationToEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            classificationToEdit.value = null;
        },
    });
};

// Delete Modal State & Action
const showDeleteModal = ref(false);
const classificationToDelete = ref(null);
const isDeleting = ref(false);

const openDeleteModal = (classification) => {
    classificationToDelete.value = classification;
    showDeleteModal.value = true;
};

const submitDelete = () => {
    if (!classificationToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('budget-classifications.destroy', classificationToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            classificationToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Master Klasifikasi Budget" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-900/50 dark:text-purple-400">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Master Klasifikasi Budget</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Kelola kategori pengelompokan anggaran manufaktur (misal: CAPEX, OPEX, Maintenance, dsb.)
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <PrimaryButton
                        type="button"
                        @click="openCreateModal"
                        class="flex items-center gap-2 shadow-sm shadow-purple-500/20"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Klasifikasi
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stat Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <!-- Card 1: Total -->
                <div class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700/60 dark:bg-gray-800">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Klasifikasi</p>
                        <p class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ stats.total }}</p>
                    </div>
                </div>

                <!-- Card 2: Aktif -->
                <div class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700/60 dark:bg-gray-800">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Klasifikasi Aktif</p>
                        <p class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">{{ stats.active }}</p>
                    </div>
                </div>

                <!-- Card 3: Non-Aktif -->
                <div class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700/60 dark:bg-gray-800">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Non-Aktif</p>
                        <p class="text-2xl font-extrabold text-amber-600 dark:text-amber-400">{{ stats.inactive }}</p>
                    </div>
                </div>
            </div>

            <!-- Search & Filter Toolbar -->
            <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700/60 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full max-w-md">
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5">
                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        v-model="search"
                        placeholder="Cari nama klasifikasi atau keterangan..."
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2 ps-10 pe-9 text-xs text-gray-900 focus:border-purple-500 focus:bg-white focus:ring-1 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-purple-400"
                    />
                    <button
                        v-if="search"
                        @click="search = ''"
                        type="button"
                        class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <select
                        v-model="statusFilter"
                        class="rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-xs text-gray-900 focus:border-purple-500 focus:bg-white focus:ring-1 focus:ring-purple-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-purple-400"
                    >
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Non-Aktif</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-700/60 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-xs dark:divide-gray-700/60">
                        <thead class="bg-gray-50/75 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-5 py-3.5 font-semibold">Nama Klasifikasi</th>
                                <th scope="col" class="px-5 py-3.5 font-semibold">Keterangan</th>
                                <th scope="col" class="px-5 py-3.5 font-semibold text-center">Budget Terkait</th>
                                <th scope="col" class="px-5 py-3.5 font-semibold text-center">Status</th>
                                <th scope="col" class="px-5 py-3.5 font-semibold text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                            <tr
                                v-for="item in classifications.data"
                                :key="item.id"
                                class="transition-colors hover:bg-gray-50/60 dark:hover:bg-gray-700/30"
                            >
                                <!-- Nama Klasifikasi -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400">
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 dark:text-white">
                                                {{ item.name }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Keterangan -->
                                <td class="px-5 py-4">
                                    <p class="line-clamp-2 text-gray-600 dark:text-gray-300">
                                        {{ item.description || '-' }}
                                    </p>
                                </td>

                                <!-- Budget Terkait -->
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 font-mono text-[11px] font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ item.budgets_count ?? 0 }} Budget
                                    </span>
                                </td>

                                <!-- Status Aktif -->
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            item.is_active
                                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-400 dark:ring-emerald-500/30'
                                                : 'bg-gray-100 text-gray-700 ring-gray-600/10 dark:bg-gray-700 dark:text-gray-300 dark:ring-gray-400/20',
                                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                item.is_active ? 'bg-emerald-500' : 'bg-gray-400',
                                                'h-1.5 w-1.5 rounded-full',
                                            ]"
                                        />
                                        {{ item.is_active ? 'Aktif' : 'Non-Aktif' }}
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="px-5 py-4 text-end whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Edit -->
                                        <button
                                            type="button"
                                            @click="openEditModal(item)"
                                            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                            title="Edit Klasifikasi"
                                        >
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>

                                        <!-- Delete -->
                                        <button
                                            type="button"
                                            @click="openDeleteModal(item)"
                                            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:text-gray-400 dark:hover:bg-rose-950/50 dark:hover:text-rose-400"
                                            title="Hapus Klasifikasi"
                                        >
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!classifications.data || classifications.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                                            <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                            </svg>
                                        </div>
                                        <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Tidak ada data klasifikasi</h3>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ search ? 'Tidak ditemukan data dengan kata kunci tersebut.' : 'Mulai dengan menambahkan klasifikasi budget baru.' }}
                                        </p>
                                        <div class="mt-4" v-if="!search">
                                            <PrimaryButton type="button" @click="openCreateModal" class="text-xs">
                                                + Tambah Klasifikasi
                                            </PrimaryButton>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="classifications.links && classifications.links.length > 3" class="border-t border-gray-200 px-5 py-4 dark:border-gray-700/60">
                    <Pagination :links="classifications.links" />
                </div>
            </div>
        </div>

        <!-- CREATE MODAL -->
        <Modal :show="showCreateModal" @close="showCreateModal = false" max-width="md">
            <form @submit.prevent="submitCreate" class="p-6">
                <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/50 dark:text-purple-400">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Tambah Klasifikasi Budget</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Daftarkan kategori klasifikasi anggaran baru</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showCreateModal = false"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                    >
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-5 space-y-4 text-xs">
                    <div>
                        <InputLabel for="create_name" value="Nama Klasifikasi *" />
                        <TextInput
                            id="create_name"
                            type="text"
                            v-model="createForm.name"
                            class="mt-1 block w-full"
                            placeholder="Contoh: CAPEX / OPEX / Maintenance"
                            required
                        />
                        <InputError :message="createForm.errors.name" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="create_description" value="Keterangan (Opsional)" />
                        <textarea
                            id="create_description"
                            v-model="createForm.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-purple-600 dark:focus:ring-purple-600 text-xs"
                            placeholder="Penjelasan kategori peruntukan klasifikasi budget ini."
                        ></textarea>
                        <InputError :message="createForm.errors.description" class="mt-1" />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            id="create_is_active"
                            v-model="createForm.is_active"
                            class="rounded border-gray-300 text-purple-600 shadow-xs focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-purple-600"
                        />
                        <label for="create_is_active" class="select-none text-xs font-medium text-gray-700 dark:text-gray-300">
                            Aktifkan klasifikasi ini
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <SecondaryButton type="button" @click="showCreateModal = false">
                        Batal
                    </SecondaryButton>
                    <PrimaryButton type="submit" :disabled="createForm.processing">
                        Simpan Klasifikasi
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- EDIT MODAL -->
        <Modal :show="showEditModal" @close="showEditModal = false" max-width="md">
            <form @submit.prevent="submitEdit" class="p-6">
                <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-100 text-purple-600 dark:bg-purple-900/50 dark:text-purple-400">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Edit Klasifikasi Budget</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Perbarui informasi klasifikasi anggaran</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showEditModal = false"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                    >
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-5 space-y-4 text-xs">
                    <div>
                        <InputLabel for="edit_name" value="Nama Klasifikasi *" />
                        <TextInput
                            id="edit_name"
                            type="text"
                            v-model="editForm.name"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="editForm.errors.name" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="edit_description" value="Keterangan (Opsional)" />
                        <textarea
                            id="edit_description"
                            v-model="editForm.description"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-purple-500 focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-purple-600 dark:focus:ring-purple-600 text-xs"
                        ></textarea>
                        <InputError :message="editForm.errors.description" class="mt-1" />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            id="edit_is_active"
                            v-model="editForm.is_active"
                            class="rounded border-gray-300 text-purple-600 shadow-xs focus:ring-purple-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-purple-600"
                        />
                        <label for="edit_is_active" class="select-none text-xs font-medium text-gray-700 dark:text-gray-300">
                            Aktifkan klasifikasi ini
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <SecondaryButton type="button" @click="showEditModal = false">
                        Batal
                    </SecondaryButton>
                    <PrimaryButton type="submit" :disabled="editForm.processing">
                        Simpan Perubahan
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- DELETE CONFIRMATION MODAL -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Konfirmasi Hapus Klasifikasi</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Apakah Anda yakin ingin menghapus klasifikasi budget ini?
                        </p>
                    </div>
                </div>

                <div v-if="classificationToDelete" class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800 text-xs">
                    <p class="font-bold text-gray-900 dark:text-white">{{ classificationToDelete.name }}</p>
                    <p v-if="classificationToDelete.budgets_count" class="mt-1 text-amber-600 dark:text-amber-400 font-medium">
                        Perhatian: Terdapat {{ classificationToDelete.budgets_count }} budget yang saat ini terhubung dengan klasifikasi ini.
                    </p>
                    <p class="mt-1 text-[11px] text-gray-400">
                        Data ini dapat dipulihkan kembali sewaktu-waktu melalui menu <strong>Recycle Bin</strong>.
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="showDeleteModal = false">
                        Batal
                    </SecondaryButton>
                    <DangerButton type="button" @click="submitDelete" :disabled="isDeleting">
                        {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Data' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
