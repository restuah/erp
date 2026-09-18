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
    currencies: Object,
    stats: Object,
    filters: Object,
});

// Search filter
const search = ref(props.filters.search || '');
let searchTimeout = null;

watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('currencies.index'),
            { search: val },
            { preserveState: true, replace: true }
        );
    }, 300);
});

// Create Modal State & Form
const showCreateModal = ref(false);
const createForm = useForm({
    code: '',
    name: '',
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
};

const submitCreate = () => {
    createForm.post(route('currencies.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// Edit Modal State & Form
const showEditModal = ref(false);
const currencyToEdit = ref(null);
const editForm = useForm({
    code: '',
    name: '',
});

const openEditModal = (currency) => {
    currencyToEdit.value = currency;
    editForm.reset();
    editForm.clearErrors();
    editForm.code = currency.code;
    editForm.name = currency.name;
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!currencyToEdit.value) return;
    editForm.put(route('currencies.update', currencyToEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            currencyToEdit.value = null;
        },
    });
};

// Delete Modal State & Action
const showDeleteModal = ref(false);
const currencyToDelete = ref(null);
const isDeleting = ref(false);

const openDeleteModal = (currency) => {
    currencyToDelete.value = currency;
    showDeleteModal.value = true;
};

const submitDelete = () => {
    if (!currencyToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('currencies.destroy', currencyToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            currencyToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Master Mata Uang" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            <svg
                                class="h-5 w-5"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h1
                                class="text-xl font-bold text-gray-900 dark:text-white"
                            >
                                Master Mata Uang (Currency)
                            </h1>
                            <p
                                class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                            >
                                Kelola daftar mata uang internasional dan lokal
                                untuk operasional sistem ERP.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <PrimaryButton
                        type="button"
                        @click="openCreateModal"
                        class="flex items-center gap-2"
                    >
                        <svg
                            class="h-4 w-4"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        Tambah Mata Uang
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stat Card & Search Toolbar -->
            <div
                class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-4"
            >
                <div
                    class="shadow-xs flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700/60 dark:bg-gray-800"
                >
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                    >
                        <svg
                            class="h-6 w-6"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                    </div>
                    <div>
                        <p
                            class="text-xs font-medium text-gray-500 dark:text-gray-400"
                        >
                            Total Mata Uang Aktif
                        </p>
                        <p
                            class="text-2xl font-extrabold text-gray-900 dark:text-white"
                        >
                            {{ stats.total }}
                        </p>
                    </div>
                </div>

                <div
                    class="shadow-xs flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700/60 dark:bg-gray-800 sm:col-span-2 lg:col-span-3"
                >
                    <div class="relative w-full max-w-md">
                        <div
                            class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5"
                        >
                            <svg
                                class="h-4 w-4 text-gray-400"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                        <input
                            type="text"
                            v-model="search"
                            placeholder="Cari berdasarkan kode atau nama mata uang..."
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2 ps-10 pe-9 text-xs text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-indigo-400"
                        />
                        <button
                            v-if="search"
                            @click="search = ''"
                            type="button"
                            class="absolute inset-y-0 end-0 flex items-center pe-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        >
                            <svg
                                class="h-4 w-4"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"
                                />
                            </svg>
                        </button>
                    </div>

                    <span
                        class="hidden text-xs text-gray-500 dark:text-gray-400 sm:inline"
                    >
                        Menampilkan {{ currencies.data.length }} dari
                        {{ currencies.total }} mata uang
                    </span>
                </div>
            </div>

            <!-- Table Card -->
            <div
                class="shadow-xs overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700/60 dark:bg-gray-800"
            >
                <div class="overflow-x-auto">
                    <table
                        class="w-full text-start text-sm text-gray-600 dark:text-gray-300"
                    >
                        <thead
                            class="border-b border-gray-200 bg-gray-50/80 text-xs font-semibold uppercase text-gray-500 dark:border-gray-700/60 dark:bg-gray-900/40 dark:text-gray-400"
                        >
                            <tr>
                                <th class="w-16 px-6 py-3.5 text-center">No</th>
                                <th class="px-6 py-3.5 text-start">
                                    Currency Code
                                </th>
                                <th class="px-6 py-3.5 text-start">
                                    Currency Name
                                </th>
                                <th class="px-6 py-3.5 text-start">
                                    Diperbarui
                                </th>
                                <th class="w-32 px-6 py-3.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-200 dark:divide-gray-700/60"
                        >
                            <tr
                                v-for="(currency, index) in currencies.data"
                                :key="currency.id"
                                class="transition-colors hover:bg-gray-50/70 dark:hover:bg-gray-750"
                            >
                                <td
                                    class="px-6 py-4 text-center text-xs text-gray-400"
                                >
                                    {{
                                        (currencies.current_page - 1) *
                                            currencies.per_page +
                                        index +
                                        1
                                    }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1 font-mono text-xs font-bold tracking-wider text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-950/50 dark:text-indigo-300 dark:ring-indigo-400/20"
                                    >
                                        {{ currency.code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {{ currency.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{
                                            new Date(
                                                currency.updated_at
                                            ).toLocaleDateString('id-ID', {
                                                day: 'numeric',
                                                month: 'short',
                                                year: 'numeric',
                                            })
                                        }}
                                    </div>
                                    <div
                                        v-if="currency.updater"
                                        class="mt-0.5 text-[11px] text-gray-400 dark:text-gray-500"
                                    >
                                        oleh {{ currency.updater.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-end">
                                    <div
                                        class="flex items-center justify-end gap-1.5"
                                    >
                                        <!-- Edit Button -->
                                        <button
                                            type="button"
                                            @click="openEditModal(currency)"
                                            class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none dark:hover:bg-indigo-950/50 dark:hover:text-indigo-400"
                                            title="Ubah Mata Uang"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                />
                                            </svg>
                                        </button>

                                        <!-- Delete Button -->
                                        <button
                                            type="button"
                                            @click="openDeleteModal(currency)"
                                            class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600 focus:outline-none dark:hover:bg-red-950/50 dark:hover:text-red-400"
                                            title="Hapus Mata Uang"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div
                    v-if="currencies.data.length === 0"
                    class="py-16 text-center"
                >
                    <div
                        class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-700/60 dark:text-gray-500"
                    >
                        <svg
                            class="h-7 w-7"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="text-sm font-semibold text-gray-900 dark:text-white"
                    >
                        Tidak ada data mata uang ditemukan
                    </h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{
                            search
                                ? 'Coba gunakan kata kunci pencarian yang berbeda.'
                                : 'Belum ada data mata uang yang ditambahkan ke sistem.'
                        }}
                    </p>
                    <div v-if="!search" class="mt-4">
                        <PrimaryButton @click="openCreateModal" class="text-xs">
                            Tambah Mata Uang Pertama
                        </PrimaryButton>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="currencies.total > currencies.per_page"
                    class="border-t border-gray-200 p-4 dark:border-gray-700/60"
                >
                    <Pagination :links="currencies.links" />
                </div>
            </div>
        </div>

        <!-- CREATE MODAL -->
        <Modal :show="showCreateModal" @close="showCreateModal = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                            Tambah Mata Uang
                        </h2>
                    </div>
                    <button
                        @click="showCreateModal = false"
                        class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="mt-5 space-y-4">
                    <div>
                        <InputLabel for="create_code" value="Currency Code" />
                        <TextInput
                            id="create_code"
                            v-model="createForm.code"
                            type="text"
                            class="mt-1 block w-full font-mono uppercase tracking-wider text-sm"
                            placeholder="contoh: USD, EUR, JPY"
                            maxlength="10"
                            required
                            autofocus
                        />
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            Kode mata uang standar ISO (maksimal 10 karakter, otomatis dikonversi ke huruf kapital).
                        </p>
                        <InputError class="mt-1" :message="createForm.errors.code" />
                    </div>

                    <div>
                        <InputLabel for="create_name" value="Currency Name" />
                        <TextInput
                            id="create_name"
                            v-model="createForm.name"
                            type="text"
                            class="mt-1 block w-full text-sm"
                            placeholder="contoh: Indonesian Rupiah, US Dollar"
                            maxlength="100"
                            required
                        />
                        <InputError class="mt-1" :message="createForm.errors.name" />
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <SecondaryButton @click="showCreateModal = false" type="button">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton :disabled="createForm.processing" class="flex items-center gap-2">
                            <svg v-if="createForm.processing" class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Simpan Mata Uang</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- EDIT MODAL -->
        <Modal :show="showEditModal" @close="showEditModal = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                            Ubah Mata Uang
                        </h2>
                    </div>
                    <button
                        @click="showEditModal = false"
                        class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="mt-5 space-y-4">
                    <div>
                        <InputLabel for="edit_code" value="Currency Code" />
                        <TextInput
                            id="edit_code"
                            v-model="editForm.code"
                            type="text"
                            class="mt-1 block w-full font-mono uppercase tracking-wider text-sm"
                            placeholder="contoh: USD, EUR, JPY"
                            maxlength="10"
                            required
                            autofocus
                        />
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            Kode mata uang standar ISO (maksimal 10 karakter, otomatis dikonversi ke huruf kapital).
                        </p>
                        <InputError class="mt-1" :message="editForm.errors.code" />
                    </div>

                    <div>
                        <InputLabel for="edit_name" value="Currency Name" />
                        <TextInput
                            id="edit_name"
                            v-model="editForm.name"
                            type="text"
                            class="mt-1 block w-full text-sm"
                            placeholder="contoh: Indonesian Rupiah, US Dollar"
                            maxlength="100"
                            required
                        />
                        <InputError class="mt-1" :message="editForm.errors.name" />
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <SecondaryButton @click="showEditModal = false" type="button">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton :disabled="editForm.processing" class="flex items-center gap-2">
                            <svg v-if="editForm.processing" class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Simpan Perubahan</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- DELETE CONFIRMATION MODAL -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-950/60 dark:text-red-400"
                    >
                        <svg
                            class="h-5 w-5"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                    </div>
                    <div>
                        <h3
                            class="text-base font-bold text-gray-900 dark:text-white"
                        >
                            Hapus Mata Uang?
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Tindakan ini akan memindahkan data ke Tempat Sampah.
                        </p>
                    </div>
                </div>

                <div class="mt-4 rounded-lg bg-gray-50 p-3 dark:bg-gray-700/50">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Mata uang yang akan dihapus:
                    </div>
                    <div
                        class="mt-1 flex items-center gap-2 font-medium text-gray-900 dark:text-white"
                    >
                        <span
                            class="inline-block rounded bg-indigo-100 px-1.5 py-0.5 font-mono text-xs font-bold text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300"
                        >
                            {{ currencyToDelete?.code }}
                        </span>
                        <span>{{ currencyToDelete?.name }}</span>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <SecondaryButton
                        type="button"
                        @click="showDeleteModal = false"
                    >
                        Batal
                    </SecondaryButton>
                    <DangerButton
                        type="button"
                        :disabled="isDeleting"
                        @click="submitDelete"
                        class="flex items-center gap-2"
                    >
                        <svg
                            v-if="isDeleting"
                            class="h-4 w-4 animate-spin text-white"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            ></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                        <span>Hapus ke Tempat Sampah</span>
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
