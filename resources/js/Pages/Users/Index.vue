<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
});

const currentAuthId = usePage().props.auth.user.id;

const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');

const userToDelete = ref(null);
const showDeleteModal = ref(false);

let searchTimeout = null;
const applyFilters = () => {
    router.get(
        route('users.index'),
        { search: search.value, role: roleFilter.value },
        { preserveState: true, replace: true },
    );
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

watch(roleFilter, () => {
    applyFilters();
});

const confirmDelete = (user) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const deleteUser = () => {
    if (userToDelete.value) {
        router.delete(route('users.destroy', userToDelete.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false;
                userToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <Head title="Manajemen Pengguna" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                        Manajemen Pengguna (Users)
                    </h1>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Kelola akun pengguna, foto profil avatar, dan penugasan
                        peran sistem ERP.
                    </p>
                </div>
                <Link :href="route('users.create')">
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
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                            />
                        </svg>
                        Tambah Pengguna
                    </PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Filter & Search Toolbar -->
            <div
                class="shadow-xs flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700/60 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between"
            >
                <div
                    class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center"
                >
                    <!-- Search Input -->
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
                            placeholder="Cari nama atau email pengguna..."
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pe-3 ps-10 text-sm text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:placeholder-gray-400 dark:focus:border-indigo-400"
                        />
                    </div>

                    <!-- Role Filter Dropdown -->
                    <div class="w-full sm:w-48">
                        <select
                            v-model="roleFilter"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-indigo-400"
                        >
                            <option value="">Semua Peran</option>
                            <option v-for="r in roles" :key="r" :value="r">
                                {{ r }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Total:
                    <span
                        class="font-semibold text-gray-700 dark:text-gray-200"
                        >{{ users.total }}</span
                    >
                    pengguna
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
                                <th class="px-6 py-3.5 text-start">Pengguna</th>
                                <th class="px-6 py-3.5 text-start">
                                    Peran (Role)
                                </th>
                                <th class="px-6 py-3.5 text-start">
                                    Status Email
                                </th>
                                <th class="px-6 py-3.5 text-start">
                                    Terdaftar
                                </th>
                                <th class="px-6 py-3.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700/60"
                        >
                            <tr
                                v-for="u in users.data"
                                :key="u.id"
                                class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-700/30"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <!-- Avatar -->
                                        <div
                                            class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full border border-gray-200 bg-indigo-50 font-semibold text-indigo-700 dark:border-gray-700 dark:bg-indigo-950/60 dark:text-indigo-300"
                                        >
                                            <img
                                                v-if="u.avatar_url"
                                                :src="u.avatar_url"
                                                :alt="u.name"
                                                class="h-full w-full object-cover"
                                            />
                                            <span
                                                v-else
                                                class="text-sm font-bold uppercase"
                                            >
                                                {{ u.name.charAt(0) }}
                                            </span>
                                        </div>
                                        <div>
                                            <div
                                                class="flex items-center gap-2 font-semibold text-gray-900 dark:text-white"
                                            >
                                                {{ u.name }}
                                                <span
                                                    v-if="
                                                        u.id === currentAuthId
                                                    "
                                                    class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"
                                                >
                                                    Anda
                                                </span>
                                            </div>
                                            <div
                                                class="text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                {{ u.email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="role in u.roles"
                                            :key="role.id"
                                            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium"
                                            :class="
                                                role.name === 'Superadmin'
                                                    ? 'border border-purple-200 bg-purple-100 text-purple-700 dark:border-purple-800/50 dark:bg-purple-950/60 dark:text-purple-300'
                                                    : 'border border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-800/50 dark:bg-indigo-950/60 dark:text-indigo-300'
                                            "
                                        >
                                            {{ role.name }}
                                        </span>
                                        <span
                                            v-if="u.roles.length === 0"
                                            class="text-xs italic text-gray-400"
                                        >
                                            Tanpa Peran
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        v-if="u.email_verified_at"
                                        class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                        ></span>
                                        Terverifikasi
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-950/50 dark:text-amber-300"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-amber-500"
                                        ></span>
                                        Belum Verifikasi
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {{
                                        new Date(
                                            u.created_at,
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
                                            :href="route('users.edit', u.id)"
                                            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-indigo-400"
                                            title="Edit Pengguna"
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
                                            v-if="u.id !== currentAuthId"
                                            type="button"
                                            @click="confirmDelete(u)"
                                            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600 dark:text-gray-400 dark:hover:bg-red-900/30 dark:hover:text-red-400"
                                            title="Hapus Pengguna"
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
                                            title="Anda tidak dapat menghapus akun Anda sendiri"
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
                            <tr v-if="users.data.length === 0">
                                <td
                                    colspan="5"
                                    class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400"
                                >
                                    Tidak ada data pengguna yang ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    class="border-t border-gray-200 px-6 py-4 dark:border-gray-700/60"
                >
                    <Pagination :links="users.links" />
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
                    Konfirmasi Hapus Pengguna
                </h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Apakah Anda yakin ingin menghapus pengguna
                    <strong class="text-gray-800 dark:text-gray-200">{{
                        userToDelete?.name
                    }}</strong>
                    ({{ userToDelete?.email }})? Data pengguna ini akan
                    menerapkan Soft Delete ke database.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showDeleteModal = false">
                        Batal
                    </SecondaryButton>
                    <DangerButton @click="deleteUser"> Ya, Hapus </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
