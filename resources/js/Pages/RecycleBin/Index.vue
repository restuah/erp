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

                    <button
                        type="button"
                        @click="switchTab('currencies')"
                        class="group inline-flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'currencies'
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
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        Mata Uang (Currencies)
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-bold"
                            :class="
                                counts.currencies > 0
                                    ? 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300'
                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                            "
                        >
                            {{ counts.currencies }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="switchTab('budgets')"
                        class="group inline-flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'budgets'
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
                                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                            />
                        </svg>
                        Master Budget
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-bold"
                            :class="
                                counts.budgets > 0
                                    ? 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300'
                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                            "
                        >
                            {{ counts.budgets }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="switchTab('budget_classifications')"
                        class="group inline-flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'budget_classifications'
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
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                            />
                        </svg>
                        Klasifikasi Budget
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-bold"
                            :class="
                                counts.budget_classifications > 0
                                    ? 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300'
                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                            "
                        >
                            {{ counts.budget_classifications }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="switchTab('chart_of_accounts')"
                        class="group inline-flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'chart_of_accounts'
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
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                            />
                        </svg>
                        Bagan Akun (COA)
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-bold"
                            :class="
                                counts.chart_of_accounts > 0
                                    ? 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300'
                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                            "
                        >
                            {{ counts.chart_of_accounts }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="switchTab('unit_of_measures')"
                        class="group inline-flex items-center gap-2 border-b-2 px-1 py-3 text-sm font-medium transition-colors"
                        :class="
                            activeTab === 'unit_of_measures'
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
                                d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"
                            />
                        </svg>
                        Satuan (UOM)
                        <span
                            class="rounded-full px-2 py-0.5 text-xs font-bold"
                            :class="
                                counts.unit_of_measures > 0
                                    ? 'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300'
                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                            "
                        >
                            {{ counts.unit_of_measures }}
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

                    <!-- TABLE 4: CURRENCIES -->
                    <table
                        v-else-if="activeTab === 'currencies'"
                        class="w-full text-start text-sm text-gray-600 dark:text-gray-300"
                    >
                        <thead
                            class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase text-gray-500 dark:border-gray-700/60 dark:bg-gray-900/40 dark:text-gray-400"
                        >
                            <tr>
                                <th class="px-6 py-3.5 text-start">
                                    Kode Mata Uang
                                </th>
                                <th class="px-6 py-3.5 text-start">
                                    Nama Mata Uang
                                </th>
                                <th class="px-6 py-3.5 text-start">
                                    Dihapus Pada
                                </th>
                                <th class="px-6 py-3.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-200 dark:divide-gray-700/60"
                        >
                            <tr
                                v-for="c in items.data"
                                :key="c.id"
                                class="transition-colors hover:bg-gray-50/70 dark:hover:bg-gray-750"
                            >
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-bold tracking-wider text-indigo-700 ring-1 ring-inset ring-indigo-700/10 dark:bg-indigo-950/50 dark:text-indigo-300 dark:ring-indigo-400/20"
                                    >
                                        {{ c.code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ c.name }}
                                </td>
                                <td
                                    class="px-6 py-4 text-xs font-medium text-red-600 dark:text-red-400"
                                >
                                    {{
                                        new Date(c.deleted_at).toLocaleString(
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
                                            @click="restoreItem(c)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                                            title="Pulihkan Mata Uang"
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
                                            @click="confirmForceDelete(c)"
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

                    <!-- TABLE 6: BUDGETS -->
                    <table
                        v-else-if="activeTab === 'budgets'"
                        class="w-full text-start text-sm text-gray-600 dark:text-gray-300"
                    >
                        <thead
                            class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase text-gray-500 dark:border-gray-700/60 dark:bg-gray-900/40 dark:text-gray-400"
                        >
                            <tr>
                                <th class="px-6 py-3.5 text-start">
                                    Kode & Nama Budget
                                </th>
                                <th class="px-6 py-3.5 text-start">
                                    PIC Budget (Owner)
                                </th>
                                <th class="px-6 py-3.5 text-start">
                                    Dihapus Pada
                                </th>
                                <th class="px-6 py-3.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-200 dark:divide-gray-700/60"
                        >
                            <tr
                                v-for="b in items.data"
                                :key="b.id"
                                class="transition-colors hover:bg-gray-50/70 dark:hover:bg-gray-750"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2.5">
                                        <span
                                            class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 font-mono text-xs font-bold text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300"
                                        >
                                            {{ b.code }}
                                        </span>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ b.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs">
                                        <p class="font-semibold text-gray-800 dark:text-gray-200">
                                            {{ b.pic?.name || '-' }}
                                        </p>
                                        <p class="text-gray-500 dark:text-gray-400">
                                            {{ b.pic?.email }}
                                        </p>
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 text-xs font-medium text-red-600 dark:text-red-400"
                                >
                                    {{
                                        new Date(b.deleted_at).toLocaleString(
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
                                            @click="restoreItem(b)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                                            title="Pulihkan Budget"
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
                                            @click="confirmForceDelete(b)"
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

                    <!-- TABLE 7: BUDGET CLASSIFICATIONS -->
                    <table
                        v-else-if="activeTab === 'budget_classifications'"
                        class="w-full text-start text-sm text-gray-600 dark:text-gray-300"
                    >
                        <thead
                            class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase text-gray-500 dark:border-gray-700/60 dark:bg-gray-900/40 dark:text-gray-400"
                        >
                            <tr>
                                <th class="px-6 py-3.5 text-start">
                                    Nama Klasifikasi
                                </th>
                                <th class="px-6 py-3.5 text-start">
                                    Keterangan
                                </th>
                                <th class="px-6 py-3.5 text-start">
                                    Dihapus Pada
                                </th>
                                <th class="px-6 py-3.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-200 dark:divide-gray-700/60"
                        >
                            <tr
                                v-for="bc in items.data"
                                :key="bc.id"
                                class="transition-colors hover:bg-gray-50/70 dark:hover:bg-gray-750"
                            >
                                <td class="px-6 py-4">
                                    <span class="font-bold text-gray-900 dark:text-white">
                                        {{ bc.name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                    {{ bc.description || '-' }}
                                </td>
                                <td
                                    class="px-6 py-4 text-xs font-medium text-red-600 dark:text-red-400"
                                >
                                    {{
                                        new Date(bc.deleted_at).toLocaleString(
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
                                            @click="restoreItem(bc)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                                            title="Pulihkan Klasifikasi"
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
                                            @click="confirmForceDelete(bc)"
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

                    <!-- TABLE 8: CHART OF ACCOUNTS -->
                    <table
                        v-else-if="activeTab === 'chart_of_accounts'"
                        class="w-full text-start text-sm text-gray-600 dark:text-gray-300"
                    >
                        <thead
                            class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase text-gray-500 dark:border-gray-700/60 dark:bg-gray-900/40 dark:text-gray-400"
                        >
                            <tr>
                                <th class="px-6 py-3.5 text-start">Kode Akun</th>
                                <th class="px-6 py-3.5 text-start">Nama Akun</th>
                                <th class="px-6 py-3.5 text-start">Kategori / Posisi</th>
                                <th class="px-6 py-3.5 text-start">Dihapus Pada</th>
                                <th class="px-6 py-3.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700/60">
                            <tr
                                v-for="coa in items.data"
                                :key="coa.id"
                                class="transition-colors hover:bg-gray-50/70 dark:hover:bg-gray-700/50"
                            >
                                <td class="px-6 py-4">
                                    <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ coa.account_code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-gray-900 dark:text-white">
                                        {{ coa.account_name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="uppercase text-xs font-semibold px-2 py-0.5 rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 mr-1.5">
                                        {{ coa.kategori === 'bs' ? 'Neraca (BS)' : 'Laba Rugi (PL)' }}
                                    </span>
                                    <span
                                        class="uppercase text-xs font-semibold px-2 py-0.5 rounded"
                                        :class="coa.jenis === 'debit' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'"
                                    >
                                        {{ coa.jenis }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs font-medium text-red-600 dark:text-red-400">
                                    {{
                                        new Date(coa.deleted_at).toLocaleString('id-ID', {
                                            dateStyle: 'medium',
                                            timeStyle: 'short',
                                        })
                                    }}
                                </td>
                                <td class="px-6 py-4 text-end">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="restoreItem(coa)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                                            title="Pulihkan Akun"
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
                                            @click="confirmForceDelete(coa)"
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

                    <!-- TABLE 9: UNIT OF MEASURES -->
                    <table
                        v-else-if="activeTab === 'unit_of_measures'"
                        class="w-full text-start text-sm text-gray-600 dark:text-gray-300"
                    >
                        <thead
                            class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase text-gray-500 dark:border-gray-700/60 dark:bg-gray-900/40 dark:text-gray-400"
                        >
                            <tr>
                                <th class="px-6 py-3.5 text-start">Kode Satuan</th>
                                <th class="px-6 py-3.5 text-start">Nama Satuan</th>
                                <th class="px-6 py-3.5 text-start">Simbol</th>
                                <th class="px-6 py-3.5 text-start">Kategori</th>
                                <th class="px-6 py-3.5 text-start">Dihapus Pada</th>
                                <th class="px-6 py-3.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700/60">
                            <tr
                                v-for="uom in items.data"
                                :key="uom.id"
                                class="transition-colors hover:bg-gray-50/70 dark:hover:bg-gray-700/50"
                            >
                                <td class="px-6 py-4">
                                    <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ uom.code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-gray-900 dark:text-white">
                                        {{ uom.name }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded bg-gray-100 px-2 py-0.5 text-xs font-mono text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ uom.symbol || '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                        {{ uom.category }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs font-medium text-red-600 dark:text-red-400">
                                    {{
                                        new Date(uom.deleted_at).toLocaleString('id-ID', {
                                            dateStyle: 'medium',
                                            timeStyle: 'short',
                                        })
                                    }}
                                </td>
                                <td class="px-6 py-4 text-end">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="restoreItem(uom)"
                                            class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                                            title="Pulihkan Satuan"
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
                                            @click="confirmForceDelete(uom)"
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
