<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    items: Object,
    counts: Object,
    activeTab: String,
    filters: Object,
});

const search = ref(props.filters.search || '');

// Confirmation modal state
const itemToForceDelete = ref(null);
const showForceDeleteModal = ref(false);
const showEmptyTrashModal = ref(false);
const showRestoreAllModal = ref(false);

let searchTimeout = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('recycle-bin.index'),
            { type: props.activeTab, search: val },
            { preserveState: true, replace: true },
        );
    }, 300);
});

const switchTab = (tab) => {
    search.value = '';
    router.get(
        route('recycle-bin.index'),
        { type: tab },
        { preserveState: false },
    );
};

const restoreItem = (item) => {
    router.post(
        route('recycle-bin.restore', { type: props.activeTab, id: item.id }),
        {},
        {
            preserveScroll: true,
        },
    );
};

const confirmForceDelete = (item) => {
    itemToForceDelete.value = item;
    showForceDeleteModal.value = true;
};

const executeForceDelete = () => {
    if (itemToForceDelete.value) {
        router.delete(
            route('recycle-bin.force-delete', {
                type: props.activeTab,
                id: itemToForceDelete.value.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    showForceDeleteModal.value = false;
                    itemToForceDelete.value = null;
                },
            },
        );
    }
};

const executeRestoreAll = () => {
    router.post(
        route('recycle-bin.restore-all', { type: props.activeTab }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showRestoreAllModal.value = false;
            },
        },
    );
};

const executeEmptyTrash = () => {
    router.delete(route('recycle-bin.empty', { type: props.activeTab }), {
        preserveScroll: true,
        onSuccess: () => {
            showEmptyTrashModal.value = false;
        },
    });
};
</script>

<template>
    <Head title="Tempat Sampah (Recycle Bin)" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="flex items-center gap-2.5 text-xl font-bold text-gray-900 dark:text-white"
                    >
                        <svg
                            class="h-6 w-6 text-red-500 dark:text-red-400"
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
                        Tempat Sampah (Recycle Bin)
                    </h1>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Data yang dihapus sementara (Soft Delete). Anda dapat
                        memulihkan (*Restore*) atau menghapus data secara
                        permanen.
                    </p>
                </div>

                <div v-if="items.total > 0" class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="showRestoreAllModal = true"
                        class="shadow-xs inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        <svg
                            class="h-4 w-4 text-emerald-600 dark:text-emerald-400"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        Pulihkan Semua
                    </button>
                    <button
                        type="button"
                        @click="showEmptyTrashModal = true"
                        class="shadow-xs inline-flex items-center gap-1.5 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition-colors hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-900/50"
                    >
                        <svg
                            class="h-4 w-4 text-red-600 dark:text-red-400"
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
                        Kosongkan Tempat Sampah
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tabs Navigation -->
            <div class="border-b border-gray-200 dark:border-gray-700/80">
                <nav class="-mb-px flex space-x-6" aria-label="Tabs">
                    <button
                        type="button"
                        @click="switchTab('users')"
                        class="group inline-flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'users'
                                ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                        "
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
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>
                        Pengguna
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-bold"
                            :class="
                                counts.users > 0
                                    ? 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300'
                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                            "
                        >
                            {{ counts.users }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="switchTab('roles')"
                        class="group inline-flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'roles'
                                ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                        "
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
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                            />
                        </svg>
                        Peran (Roles)
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-bold"
                            :class="
                                counts.roles > 0
                                    ? 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300'
                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                            "
                        >
                            {{ counts.roles }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="switchTab('permissions')"
                        class="group inline-flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'permissions'
                                ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                        "
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
                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                            />
                        </svg>
                        Izin (Permissions)
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-bold"
                            :class="
                                counts.permissions > 0
                                    ? 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300'
                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                            "
                        >
                            {{ counts.permissions }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Search Toolbar -->
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
                        :placeholder="`Cari di tempat sampah ${activeTab}...`"
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2 pe-3 ps-10 text-sm text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:placeholder-gray-400 dark:focus:border-indigo-400"
                    />
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Terhapus:
                    <span
                        class="font-semibold text-gray-700 dark:text-gray-200"
                        >{{ items.total }}</span
                    >
                    data
                </div>
            </div>

            <!-- Table Card -->
            <div
                class="shadow-xs overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700/60 dark:bg-gray-800"
            >
                <div class="overflow-x-auto">
                    <!-- TABLE 1: USERS -->
                    <table
                        v-if="activeTab === 'users'"
                        class="w-full text-start text-sm text-gray-600 dark:text-gray-300"
                    >
                        <thead
                            class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase text-gray-500 dark:border-gray-700/60 dark:bg-gray-900/40 dark:text-gray-400"
                        >
                            <tr>
                                <th class="px-6 py-3.5 text-start">Pengguna</th>
                                <th class="px-6 py-3.5 text-start">Peran</th>
                                <th class="px-6 py-3.5 text-start">
                                    Tanggal Dihapus
                                </th>
                                <th class="px-6 py-3.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700/60"
                        >
                            <tr
                                v-for="u in items.data"
                                :key="u.id"
                                class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-700/30"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full border border-gray-200 bg-gray-100 font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                        >
                                            <img
                                                v-if="u.avatar_url"
                                                :src="u.avatar_url"
                                                :alt="u.name"
                                                class="h-full w-full object-cover opacity-75 grayscale"
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
                                                class="font-semibold text-gray-900 dark:text-white"
                                            >
                                                {{ u.name }}
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
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="r in u.roles"
                                            :key="r.id"
                                            class="rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                        >
                                            {{ r.name }}
                                        </span>
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 text-xs font-medium text-red-600 dark:text-red-400"
                                >
                                    {{
                                        new Date(u.deleted_at).toLocaleString(
                                            'id-ID',
                                            {
                                                dateStyle: 'medium',
                                                timeStyle: 'short',
                                            },
                                        )
                                    }}
                                </td>
                                <td class="px-6 py-4 text-end">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <button
                                            type="button"
                                            @click="restoreItem(u)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                                            title="Pulihkan Pengguna"
                                        >
                                            <svg
                                                class="h-3.5 w-3.5"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                                />
                                            </svg>
                                            Pulihkan
                                        </button>
                                        <button
                                            type="button"
                                            @click="confirmForceDelete(u)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 transition-colors hover:bg-red-100 dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-900/50"
                                            title="Hapus Permanen"
                                        >
                                            <svg
                                                class="h-3.5 w-3.5"
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
                                            Hapus Permanen
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- TABLE 2: ROLES -->
                    <table
                        v-else-if="activeTab === 'roles'"
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
                                <th class="px-6 py-3.5 text-start">
                                    Tanggal Dihapus
                                </th>
                                <th class="px-6 py-3.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700/60"
                        >
                            <tr
                                v-for="r in items.data"
                                :key="r.id"
                                class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-700/30"
                            >
                                <td
                                    class="px-6 py-4 font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ r.name }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        {{ r.permissions_count }} Izin
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 text-xs font-medium text-red-600 dark:text-red-400"
                                >
                                    {{
                                        new Date(r.deleted_at).toLocaleString(
                                            'id-ID',
                                            {
                                                dateStyle: 'medium',
                                                timeStyle: 'short',
                                            },
                                        )
                                    }}
                                </td>
                                <td class="px-6 py-4 text-end">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <button
                                            type="button"
                                            @click="restoreItem(r)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                                        >
                                            <svg
                                                class="h-3.5 w-3.5"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                                />
                                            </svg>
                                            Pulihkan
                                        </button>
                                        <button
                                            type="button"
                                            @click="confirmForceDelete(r)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 transition-colors hover:bg-red-100 dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-900/50"
                                        >
                                            <svg
                                                class="h-3.5 w-3.5"
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
                                            Hapus Permanen
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- TABLE 3: PERMISSIONS -->
                    <table
                        v-else-if="activeTab === 'permissions'"
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
                                    Tanggal Dihapus
                                </th>
                                <th class="px-6 py-3.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700/60"
                        >
                            <tr
                                v-for="p in items.data"
                                :key="p.id"
                                class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-700/30"
                            >
                                <td
                                    class="px-6 py-4 font-mono font-semibold text-gray-900 dark:text-white"
                                >
                                    {{ p.name }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        {{ p.guard_name }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 text-xs font-medium text-red-600 dark:text-red-400"
                                >
                                    {{
                                        new Date(p.deleted_at).toLocaleString(
                                            'id-ID',
                                            {
                                                dateStyle: 'medium',
                                                timeStyle: 'short',
                                            },
                                        )
                                    }}
                                </td>
                                <td class="px-6 py-4 text-end">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <button
                                            type="button"
                                            @click="restoreItem(p)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                                        >
                                            <svg
                                                class="h-3.5 w-3.5"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                                />
                                            </svg>
                                            Pulihkan
                                        </button>
                                        <button
                                            type="button"
                                            @click="confirmForceDelete(p)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 transition-colors hover:bg-red-100 dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-900/50"
                                        >
                                            <svg
                                                class="h-3.5 w-3.5"
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
                                            Hapus Permanen
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div
                        v-if="items.data.length === 0"
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
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                            </svg>
                        </div>
                        <h4
                            class="text-sm font-semibold text-gray-900 dark:text-white"
                        >
                            Tempat Sampah Bersih
                        </h4>
                        <p
                            class="mx-auto mt-1 max-w-sm text-xs text-gray-500 dark:text-gray-400"
                        >
                            Tidak ada data di kategori
                            <strong>{{ activeTab }}</strong> yang sedang berada
                            di tempat sampah.
                        </p>
                    </div>
                </div>

                <!-- Pagination -->
                <div
                    v-if="items.data.length > 0"
                    class="border-t border-gray-200 px-6 py-4 dark:border-gray-700/60"
                >
                    <Pagination :links="items.links" />
                </div>
            </div>
        </div>

        <!-- Modal: Force Delete Single Item -->
        <Modal
            :show="showForceDeleteModal"
            @close="showForceDeleteModal = false"
            max-width="md"
        >
            <div class="p-6">
                <div
                    class="mb-3 flex items-center gap-3 text-red-600 dark:text-red-400"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-950/60"
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
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Hapus Permanen?
                    </h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Apakah Anda yakin ingin menghapus data
                    <strong class="text-gray-800 dark:text-gray-200">{{
                        itemToForceDelete?.name
                    }}</strong>
                    secara permanen? Data yang sudah dihapus permanen
                    <strong>tidak dapat dikembalikan lagi</strong>.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showForceDeleteModal = false">
                        Batal
                    </SecondaryButton>
                    <DangerButton @click="executeForceDelete">
                        Ya, Hapus Permanen
                    </DangerButton>
                </div>
            </div>
        </Modal>

        <!-- Modal: Restore All Items -->
        <Modal
            :show="showRestoreAllModal"
            @close="showRestoreAllModal = false"
            max-width="md"
        >
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Pulihkan Semua Data?
                </h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Semua data di kategori <strong>{{ activeTab }}</strong> yang
                    ada di tempat sampah akan dikembalikan ke daftar aktif.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showRestoreAllModal = false">
                        Batal
                    </SecondaryButton>
                    <PrimaryButton @click="executeRestoreAll">
                        Ya, Pulihkan Semua
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Modal: Empty Trash -->
        <Modal
            :show="showEmptyTrashModal"
            @close="showEmptyTrashModal = false"
            max-width="md"
        >
            <div class="p-6">
                <div
                    class="mb-3 flex items-center gap-3 text-red-600 dark:text-red-400"
                >
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-950/60"
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
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Kosongkan Tempat Sampah?
                    </h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Tindakan ini akan
                    <strong>menghapus secara permanen semua data</strong> pada
                    kategori <strong>{{ activeTab }}</strong> di tempat sampah.
                    Tindakan ini tidak dapat dibatalkan.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showEmptyTrashModal = false">
                        Batal
                    </SecondaryButton>
                    <DangerButton @click="executeEmptyTrash">
                        Ya, Kosongkan
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
