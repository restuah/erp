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
    roles: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const roleToDelete = ref(null);
const showDeleteModal = ref(false);

let searchTimeout = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('roles.index'),
            { search: val },
            { preserveState: true, replace: true },
        );
    }, 300);
});

const confirmDelete = (role) => {
    roleToDelete.value = role;
    showDeleteModal.value = true;
};

const deleteRole = () => {
    if (roleToDelete.value) {
        router.delete(route('roles.destroy', roleToDelete.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false;
                roleToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <Head title="Manajemen Peran & Hak Akses" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                        Manajemen Peran & Hak Akses (Roles)
                    </h1>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Atur peran pengguna dan distribusi izin akses fitur di
                        sistem ERP.
                    </p>
                </div>
                <Link :href="route('roles.create')">
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
                        Tambah Peran
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
                        placeholder="Cari nama peran..."
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pe-3 ps-10 text-sm text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:placeholder-gray-400 dark:focus:border-indigo-400"
                    />
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Total:
                    <span
                        class="font-semibold text-gray-700 dark:text-gray-200"
                        >{{ roles.total }}</span
                    >
                    peran
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
                                    Nama Peran
                                </th>
                                <th class="px-6 py-3.5 text-start">
                                    Izin Terkait
                                </th>
                                <th class="px-6 py-3.5 text-start">Pengguna</th>
                                <th class="px-6 py-3.5 text-start">Dibuat</th>
                                <th class="px-6 py-3.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700/60"
                        >
                            <tr
                                v-for="item in roles.data"
                                :key="item.id"
                                class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-700/30"
                            >
                                <td
                                    class="px-6 py-4 font-semibold text-gray-900 dark:text-white"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-semibold"
                                            :class="
                                                item.name === 'Superadmin'
                                                    ? 'border border-purple-200 bg-purple-100 text-purple-700 dark:border-purple-800/50 dark:bg-purple-950/60 dark:text-purple-300'
                                                    : 'border border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-800/50 dark:bg-indigo-950/60 dark:text-indigo-300'
                                            "
                                        >
                                            {{ item.name }}
                                        </span>
                                        <span
                                            v-if="item.name === 'Superadmin'"
                                            class="inline-flex items-center rounded-sm bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300"
                                        >
                                            Sistem
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 dark:bg-slate-700/60 dark:text-slate-300"
                                    >
                                        <svg
                                            class="h-3.5 w-3.5 text-slate-500"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                                            />
                                        </svg>
                                        {{ item.permissions_count }} Izin
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1 text-xs font-medium text-gray-600 dark:text-gray-400"
                                    >
                                        <svg
                                            class="h-3.5 w-3.5 text-gray-400"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                            />
                                        </svg>
                                        {{ item.users_count }} Pengguna
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
                                            :href="route('roles.edit', item.id)"
                                            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-indigo-400"
                                            title="Edit Peran & Izin"
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
                                            v-if="item.name !== 'Superadmin'"
                                            type="button"
                                            @click="confirmDelete(item)"
                                            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600 dark:text-gray-400 dark:hover:bg-red-900/30 dark:hover:text-red-400"
                                            title="Hapus Peran"
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
                                        <span
                                            v-else
                                            class="cursor-not-allowed p-1.5 text-gray-300 dark:text-gray-600"
                                            title="Peran Superadmin tidak dapat dihapus"
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
                                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                                />
                                            </svg>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="roles.data.length === 0">
                                <td
                                    colspan="5"
                                    class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada data peran yang ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    class="border-t border-gray-200 px-6 py-4 dark:border-gray-700/60"
                >
                    <Pagination :links="roles.links" />
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
                    Konfirmasi Hapus Peran
                </h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Apakah Anda yakin ingin menghapus peran
                    <strong class="text-gray-800 dark:text-gray-200">{{
                        roleToDelete?.name
                    }}</strong
                    >? Tindakan ini akan menerapkan Soft Delete ke database.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showDeleteModal = false">
                        Batal
                    </SecondaryButton>
                    <DangerButton @click="deleteRole"> Ya, Hapus </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
