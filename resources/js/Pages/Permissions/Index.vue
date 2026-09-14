<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    permissions: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const permissionToDelete = ref(null);
const showDeleteModal = ref(false);

let searchTimeout = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('permissions.index'),
            { search: val },
            { preserveState: true, replace: true },
        );
    }, 300);
});

const confirmDelete = (permission) => {
    permissionToDelete.value = permission;
    showDeleteModal.value = true;
};

const deletePermission = () => {
    if (permissionToDelete.value) {
        router.delete(
            route('permissions.destroy', permissionToDelete.value.id),
            {
                onSuccess: () => {
                    showDeleteModal.value = false;
                    permissionToDelete.value = null;
                },
            },
        );
    }
};
</script>

<template>
    <Head title="Manajemen Izin Sistem" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                        Manajemen Izin Sistem (Permissions)
                    </h1>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Kelola hak akses spesifik untuk modul-modul di dalam
                        sistem ERP.
                    </p>
                </div>
                <Link :href="route('permissions.create')">
                    <PrimaryButton class="flex items-center gap-2">
                        <svg
                            class="h-4 w-4"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        Tambah Izin
                    </PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Search & Filter Card -->
            <div
                class="shadow-xs flex items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700/60 dark:bg-gray-800"
            >
                <div class="relative w-full max-w-sm">
                    <div
                        class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3"
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
                        v-model="search"
                        type="text"
                        placeholder="Cari nama izin atau guard..."
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pe-3 ps-10 text-sm text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:placeholder-gray-400 dark:focus:border-indigo-400"
                    />
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Total:
                    <span
                        class="font-semibold text-gray-700 dark:text-gray-200"
                        >{{ permissions.total }}</span
                    >
                    izin
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
                            class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase text-gray-500 dark:border-gray-700/60 dark:bg-gray-900/40 dark:text-gray-400"
                        >
                            <tr>
                                <th class="px-6 py-3.5 text-start">
                                    Nama Izin
                                </th>
                                <th class="px-6 py-3.5 text-start">Guard</th>
                                <th class="px-6 py-3.5 text-start">
                                    Tanggal Dibuat
                                </th>
                                <th class="px-6 py-3.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700/60"
                        >
                            <tr
                                v-for="item in permissions.data"
                                :key="item.id"
                                class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-700/30"
                            >
                                <td
                                    class="px-6 py-4 font-semibold text-gray-900 dark:text-white"
                                >
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-md bg-indigo-50 px-2.5 py-1 font-mono text-xs font-medium text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300"
                                    >
                                        {{ item.name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        {{ item.guard_name }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {{
                                        new Date(
                                            item.created_at,
                                        ).toLocaleDateString('id-ID', {
                                            day: 'numeric',
                                            month: 'short',
                                            year: 'numeric',
                                        })
                                    }}
                                </td>
                                <td class="px-6 py-4 text-end">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'permissions.edit',
                                                    item.id,
                                                )
                                            "
                                            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-indigo-400"
                                            title="Edit Izin"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                />
                                            </svg>
                                        </Link>
                                        <button
                                            type="button"
                                            @click="confirmDelete(item)"
                                            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600 dark:text-gray-400 dark:hover:bg-red-900/30 dark:hover:text-red-400"
                                            title="Hapus Izin"
                                        >
                                            <svg
                                                class="h-4 w-4"
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
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="permissions.data.length === 0">
                                <td
                                    colspan="4"
                                    class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada data izin yang ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    class="border-t border-gray-200 px-6 py-4 dark:border-gray-700/60"
                >
                    <Pagination :links="permissions.links" />
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal
            :show="showDeleteModal"
            @close="showDeleteModal = false"
            max-width="md"
        >
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Konfirmasi Hapus Izin
                </h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Apakah Anda yakin ingin menghapus izin
                    <strong
                        class="font-mono text-gray-800 dark:text-gray-200"
                        >{{ permissionToDelete?.name }}</strong
                    >? Tindakan ini akan menerapkan Soft Delete ke database.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showDeleteModal = false">
                        Batal
                    </SecondaryButton>
                    <DangerButton @click="deletePermission">
                        Ya, Hapus
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
