<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from '@/Components/Atoms/Button.vue';
import Icon from '@/Components/Atoms/Icon.vue';
import InputError from '@/Components/Atoms/InputError.vue';
import InputLabel from '@/Components/Atoms/InputLabel.vue';
import Modal from '@/Components/Atoms/Modal.vue';
import TextInput from '@/Components/Atoms/TextInput.vue';
import ConfirmDialog from '@/Components/Molecules/ConfirmDialog.vue';
import Pagination from '@/Components/Molecules/Pagination.vue';
import StatsCard from '@/Components/Molecules/StatsCard.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import AuthenticatedTemplate from '@/Components/Templates/AuthenticatedTemplate.vue';
import CoaTreeNode from './CoaTreeNode.vue';

const props = defineProps({
    accounts: Object, // Paginated list for table view
    treeAccounts: Array, // Nested tree list for tree view
    stats: Object, // Total, postable, header, bs, pl
    availableParents: Array, // List for parent dropdown selector
    filters: Object, // search, kategori, jenis, postable, level, view_mode
});

// View Mode ('table' or 'tree')
const viewMode = ref(props.filters.view_mode || 'table');

// Filter States
const search = ref(props.filters.search || '');
const kategoriFilter = ref(props.filters.kategori || '');
const jenisFilter = ref(props.filters.jenis || '');
const postableFilter = ref(
    props.filters.postable !== undefined && props.filters.postable !== null
        ? String(props.filters.postable)
        : '',
);
const levelFilter = ref(props.filters.level || '');

let searchTimeout = null;
const applyFilters = () => {
    router.get(
        route('chart-of-accounts.index'),
        {
            search: search.value || undefined,
            kategori: kategoriFilter.value || undefined,
            jenis: jenisFilter.value || undefined,
            postable:
                postableFilter.value !== '' ? postableFilter.value : undefined,
            level: levelFilter.value || undefined,
            view_mode: viewMode.value,
        },
        { preserveState: true, replace: true },
    );
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch(
    [kategoriFilter, jenisFilter, postableFilter, levelFilter, viewMode],
    () => {
        applyFilters();
    },
);

const resetFilters = () => {
    search.value = '';
    kategoriFilter.value = '';
    jenisFilter.value = '';
    postableFilter.value = '';
    levelFilter.value = '';
    applyFilters();
};

// Tree View State: Expanded Keys
const expandedKeys = reactive({});

// Auto-expand Level 1 by default
const initDefaultExpanded = (nodes) => {
    if (!nodes) return;
    nodes.forEach((n) => {
        if (n.level <= 2) {
            expandedKeys[n.id] = true;
        }
        if (n.children && n.children.length) {
            initDefaultExpanded(n.children);
        }
    });
};
initDefaultExpanded(props.treeAccounts);

const toggleNode = (nodeId) => {
    expandedKeys[nodeId] = !expandedKeys[nodeId];
};

const expandAll = () => {
    const expandRecursive = (nodes) => {
        if (!nodes) return;
        nodes.forEach((n) => {
            expandedKeys[n.id] = true;
            if (n.children && n.children.length) {
                expandRecursive(n.children);
            }
        });
    };
    expandRecursive(props.treeAccounts);
};

const collapseAll = () => {
    Object.keys(expandedKeys).forEach((k) => delete expandedKeys[k]);
};

// Form: Create Modal
const showCreateModal = ref(false);
const createForm = useForm({
    account_code: '',
    account_name: '',
    parent_code: '',
    level: 1,
    jenis: 'debit',
    kategori: 'bs',
    postable: true,
    description: '',
    is_active: true,
});

const onParentChangeCreate = () => {
    if (!createForm.parent_code) {
        createForm.level = 1;
        return;
    }
    const parent = props.availableParents.find(
        (p) => p.account_code === createForm.parent_code,
    );
    if (parent) {
        createForm.level = parent.level + 1;
        // Suggest same category and normal balance
        createForm.kategori = parent.kategori;
        createForm.jenis = parent.jenis;
    }
};

const openCreateModal = (parentAccount = null) => {
    createForm.reset();
    createForm.clearErrors();
    createForm.is_active = true;
    createForm.postable = true;

    if (parentAccount) {
        createForm.parent_code = parentAccount.account_code;
        createForm.level = parentAccount.level + 1;
        createForm.kategori = parentAccount.kategori;
        createForm.jenis = parentAccount.jenis;
    } else {
        createForm.parent_code = '';
        createForm.level = 1;
        createForm.jenis = 'debit';
        createForm.kategori = 'bs';
    }

    showCreateModal.value = true;
};

const submitCreate = () => {
    createForm.post(route('chart-of-accounts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// Form: Edit Modal
const showEditModal = ref(false);
const accountToEdit = ref(null);
const editForm = useForm({
    account_code: '',
    account_name: '',
    parent_code: '',
    level: 1,
    jenis: 'debit',
    kategori: 'bs',
    postable: true,
    description: '',
    is_active: true,
});

const editAccountHasChildren = computed(() => {
    if (!accountToEdit.value) return false;
    return (
        (accountToEdit.value.children_count &&
            accountToEdit.value.children_count > 0) ||
        (accountToEdit.value.children &&
            accountToEdit.value.children.length > 0)
    );
});

// Filter parents in edit modal to prevent selecting self
const availableParentsForEdit = computed(() => {
    if (!accountToEdit.value) return props.availableParents;
    return props.availableParents.filter(
        (p) => p.id !== accountToEdit.value.id,
    );
});

const onParentChangeEdit = () => {
    if (!editForm.parent_code) {
        editForm.level = 1;
        return;
    }
    const parent = props.availableParents.find(
        (p) => p.account_code === editForm.parent_code,
    );
    if (parent) {
        editForm.level = parent.level + 1;
    }
};

const openEditModal = (account) => {
    accountToEdit.value = account;
    editForm.reset();
    editForm.clearErrors();
    editForm.account_code = account.account_code;
    editForm.account_name = account.account_name;
    editForm.parent_code = account.parent_code || '';
    editForm.level = account.level;
    editForm.jenis = account.jenis;
    editForm.kategori = account.kategori;
    editForm.postable = account.postable;
    editForm.description = account.description || '';
    editForm.is_active = account.is_active;

    showEditModal.value = true;
};

const submitEdit = () => {
    if (!accountToEdit.value) return;
    editForm.put(route('chart-of-accounts.update', accountToEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            accountToEdit.value = null;
        },
    });
};

// Delete Modal State & Action
const showDeleteModal = ref(false);
const accountToDelete = ref(null);
const isDeleting = ref(false);

const deleteAccountHasChildren = computed(() => {
    if (!accountToDelete.value) return false;
    return (
        (accountToDelete.value.children_count &&
            accountToDelete.value.children_count > 0) ||
        (accountToDelete.value.children &&
            accountToDelete.value.children.length > 0)
    );
});

const openDeleteModal = (account) => {
    accountToDelete.value = account;
    showDeleteModal.value = true;
};

const submitDelete = () => {
    if (!accountToDelete.value || deleteAccountHasChildren.value) return;
    isDeleting.value = true;
    router.delete(
        route('chart-of-accounts.destroy', accountToDelete.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                accountToDelete.value = null;
                isDeleting.value = false;
            },
            onError: () => {
                isDeleting.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Master Bagan Akun (COA)" />

    <AuthenticatedTemplate>
        <template #header>
            <PageHeader
                title="Bagan Akun (Chart of Accounts)"
                subtitle="Kelola hierarki Chart of Accounts, klasifikasi Neraca/Laba Rugi, saldo normal, dan aturan akun postable."
                :breadcrumbs="[
                    { label: 'Dashboard', href: route('dashboard') },
                    { label: 'Bagan Akun (COA)' },
                ]"
            >
                <template #actions>
                    <div class="flex items-center gap-2">
                        <!-- Switch View Mode (Table vs Tree) -->
                        <div
                            class="inline-flex rounded-lg border border-slate-200 bg-slate-100 p-0.5 dark:border-slate-700 dark:bg-slate-900"
                        >
                            <button
                                type="button"
                                @click="viewMode = 'table'"
                                class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition-all"
                                :class="
                                    viewMode === 'table'
                                        ? 'shadow-xs bg-white text-blue-600 dark:bg-slate-800 dark:text-blue-400 dark:shadow-md'
                                        : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                                "
                            >
                                <Icon name="table" class="h-4 w-4" />
                                <span>Tabel</span>
                            </button>
                            <button
                                type="button"
                                @click="viewMode = 'tree'"
                                class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold transition-all"
                                :class="
                                    viewMode === 'tree'
                                        ? 'shadow-xs bg-white text-blue-600 dark:bg-slate-800 dark:text-blue-400 dark:shadow-md'
                                        : 'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white'
                                "
                            >
                                <Icon name="folder" class="h-4 w-4" />
                                <span>Pohon (Tree)</span>
                            </button>
                        </div>

                        <!-- Create Button -->
                        <Button
                            variant="primary"
                            size="sm"
                            @click="openCreateModal()"
                        >
                            <template #icon-left>
                                <Icon name="plus" class="h-4 w-4" />
                            </template>
                            <span>Tambah Akun</span>
                        </Button>
                    </div>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-6">
            <!-- STATS CARDS -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <StatsCard
                    title="Total Akun"
                    :value="stats.total"
                    unit="Akun"
                    variant="primary"
                    description="Seluruh hierarki COA"
                />
                <StatsCard
                    title="Akun Header (Induk)"
                    :value="stats.header"
                    unit="Header"
                    variant="warning"
                    description="Non-postable (induk)"
                />
                <StatsCard
                    title="Akun Postable"
                    :value="stats.postable"
                    unit="Postable"
                    variant="success"
                    description="Dapat dijurnal transaksi"
                />
                <StatsCard
                    title="Neraca (BS)"
                    :value="stats.bs"
                    unit="Akun"
                    variant="info"
                    description="Aset, Liabilitas, Ekuitas"
                />
                <StatsCard
                    title="Laba Rugi (PL)"
                    :value="stats.pl"
                    unit="Akun"
                    variant="primary"
                    description="Pendapatan & Beban"
                />
            </div>

            <!-- SEARCH & FILTER TOOLBAR -->
            <div
                class="shadow-xs rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700/60 dark:bg-gray-800"
            >
                <div
                    class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                >
                    <!-- Left: Search Box -->
                    <div class="relative w-full lg:w-72">
                        <div
                            class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400 dark:text-gray-500"
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
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari kode atau nama akun..."
                            class="w-full rounded-lg border border-gray-300 bg-white py-2 pe-3 ps-9 text-xs text-gray-800 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500 dark:focus:border-blue-400"
                        />
                    </div>

                    <!-- Right: Filter Dropdowns -->
                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Kategori Filter -->
                        <select
                            v-model="kategoriFilter"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400"
                        >
                            <option value="">Semua Kategori</option>
                            <option value="bs">Neraca (BS)</option>
                            <option value="pl">Laba Rugi (PL)</option>
                        </select>

                        <!-- Jenis Filter -->
                        <select
                            v-model="jenisFilter"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400"
                        >
                            <option value="">Semua Saldo</option>
                            <option value="debit">Debit</option>
                            <option value="credit">Kredit</option>
                        </select>

                        <!-- Postable Filter -->
                        <select
                            v-model="postableFilter"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400"
                        >
                            <option value="">Semua Postable</option>
                            <option value="1">Bisa Dijurnal (Postable)</option>
                            <option value="0">Akun Induk (Header)</option>
                        </select>

                        <!-- Level Filter -->
                        <select
                            v-model="levelFilter"
                            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400"
                        >
                            <option value="">Semua Level</option>
                            <option value="1">Level 1</option>
                            <option value="2">Level 2</option>
                            <option value="3">Level 3</option>
                            <option value="4">Level 4</option>
                        </select>

                        <!-- Reset Filter Button -->
                        <button
                            v-if="
                                search ||
                                kategoriFilter ||
                                jenisFilter ||
                                postableFilter !== '' ||
                                levelFilter
                            "
                            type="button"
                            @click="resetFilters"
                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-2 text-xs font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                            title="Reset Semua Filter"
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
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- VIEW 1: TABLE VIEW -->
            <div
                v-if="viewMode === 'table'"
                class="shadow-xs overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700/60 dark:bg-gray-800"
            >
                <div class="overflow-x-auto">
                    <table
                        class="w-full text-start text-sm text-gray-600 dark:text-gray-300"
                    >
                        <thead
                            class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase text-gray-500 dark:border-gray-700/60 dark:bg-gray-900/60 dark:text-gray-300"
                        >
                            <tr>
                                <th class="px-6 py-3.5 text-start">
                                    Kode Akun
                                </th>
                                <th class="px-6 py-3.5 text-start">
                                    Nama Akun
                                </th>
                                <th class="px-6 py-3.5 text-start">
                                    Induk (Parent)
                                </th>
                                <th class="px-6 py-3.5 text-center">Level</th>
                                <th class="px-6 py-3.5 text-start">Kategori</th>
                                <th class="px-6 py-3.5 text-start">
                                    Saldo Normal
                                </th>
                                <th class="px-6 py-3.5 text-center">
                                    Postable
                                </th>
                                <th class="px-6 py-3.5 text-center">Status</th>
                                <th class="px-6 py-3.5 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-200 dark:divide-gray-700/60"
                        >
                            <tr
                                v-for="item in accounts.data"
                                :key="item.id"
                                class="transition-colors hover:bg-gray-50/70 dark:hover:bg-gray-700/40"
                            >
                                <!-- Kode Akun -->
                                <td class="px-6 py-4">
                                    <span
                                        class="font-mono font-bold text-blue-600 dark:text-blue-400"
                                    >
                                        {{ item.account_code }}
                                    </span>
                                </td>

                                <!-- Nama Akun -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded text-xs"
                                            :class="
                                                item.children_count > 0 ||
                                                !item.postable
                                                    ? 'bg-amber-100 text-amber-700 dark:border dark:border-amber-800/40 dark:bg-amber-950/70 dark:text-amber-300'
                                                    : 'bg-emerald-100 text-emerald-700 dark:border dark:border-emerald-800/40 dark:bg-emerald-950/70 dark:text-emerald-300'
                                            "
                                        >
                                            <svg
                                                v-if="
                                                    item.children_count > 0 ||
                                                    !item.postable
                                                "
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
                                                    d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"
                                                />
                                            </svg>
                                            <svg
                                                v-else
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
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                />
                                            </svg>
                                        </div>
                                        <span
                                            class="font-semibold text-gray-900 dark:text-white"
                                        >
                                            {{ item.account_name }}
                                        </span>
                                        <span
                                            v-if="item.children_count > 0"
                                            class="dark:border-gray-750 rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-500 dark:border dark:bg-gray-900 dark:text-gray-400"
                                        >
                                            {{ item.children_count }} sub
                                        </span>
                                    </div>
                                </td>

                                <!-- Parent -->
                                <td
                                    class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    <span
                                        v-if="item.parent"
                                        class="font-medium text-gray-700 dark:text-gray-300"
                                    >
                                        {{ item.parent.account_code }} -
                                        {{ item.parent.account_name }}
                                    </span>
                                    <span
                                        v-else
                                        class="italic text-gray-400 dark:text-gray-500"
                                    >
                                        (Akun Utama / Root)
                                    </span>
                                </td>

                                <!-- Level -->
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="rounded bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700 dark:border dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                    >
                                        L{{ item.level }}
                                    </span>
                                </td>

                                <!-- Kategori -->
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded px-2.5 py-1 text-xs font-semibold uppercase"
                                        :class="
                                            item.kategori === 'bs'
                                                ? 'border border-purple-200 bg-purple-50 text-purple-700 dark:border-purple-800/60 dark:bg-purple-950/60 dark:text-purple-300'
                                                : 'border border-teal-200 bg-teal-50 text-teal-700 dark:border-teal-800/60 dark:bg-teal-950/60 dark:text-teal-300'
                                        "
                                    >
                                        {{
                                            item.kategori === 'bs'
                                                ? 'Neraca (BS)'
                                                : 'Laba Rugi (PL)'
                                        }}
                                    </span>
                                </td>

                                <!-- Saldo Normal -->
                                <td class="px-6 py-4">
                                    <span
                                        class="rounded px-2.5 py-1 text-xs font-semibold uppercase"
                                        :class="
                                            item.jenis === 'debit'
                                                ? 'border border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800/60 dark:bg-blue-950/60 dark:text-blue-300'
                                                : 'border border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800/60 dark:bg-emerald-950/60 dark:text-emerald-300'
                                        "
                                    >
                                        {{ item.jenis }}
                                    </span>
                                </td>

                                <!-- Postable -->
                                <td class="px-6 py-4 text-center">
                                    <span
                                        v-if="item.postable"
                                        class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:border-emerald-800/60 dark:bg-emerald-950/60 dark:text-emerald-300"
                                        title="Dapat dipilih dalam transaksi penjournalan"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                        ></span>
                                        Postable
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:border-amber-800/60 dark:bg-amber-950/60 dark:text-amber-300"
                                        title="Akun induk / header (tidak dapat dijurnal)"
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full bg-amber-500"
                                        ></span>
                                        Header
                                    </span>
                                </td>

                                <!-- Status Aktif -->
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="
                                            item.is_active
                                                ? 'bg-emerald-50 text-emerald-700 dark:border dark:border-emerald-800/50 dark:bg-emerald-950/60 dark:text-emerald-300'
                                                : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                                        "
                                    >
                                        <span
                                            class="h-1.5 w-1.5 rounded-full"
                                            :class="
                                                item.is_active
                                                    ? 'bg-emerald-500'
                                                    : 'bg-gray-400'
                                            "
                                        ></span>
                                        {{
                                            item.is_active
                                                ? 'Aktif'
                                                : 'Nonaktif'
                                        }}
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="px-6 py-4 text-end">
                                    <div
                                        class="flex items-center justify-end gap-1.5"
                                    >
                                        <!-- Quick Add Child -->
                                        <button
                                            type="button"
                                            @click="openCreateModal(item)"
                                            class="inline-flex items-center gap-1 rounded-md border border-blue-200 bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 transition-colors hover:bg-blue-100 dark:border-blue-700/70 dark:bg-blue-950/60 dark:text-blue-300 dark:hover:bg-blue-900/80"
                                            title="Tambah Sub-Akun"
                                        >
                                            <svg
                                                class="h-3 w-3"
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
                                            <span>Sub</span>
                                        </button>

                                        <!-- Edit -->
                                        <button
                                            type="button"
                                            @click="openEditModal(item)"
                                            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-blue-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-blue-300"
                                            title="Edit Akun"
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
                                        </button>

                                        <!-- Delete -->
                                        <button
                                            type="button"
                                            @click="openDeleteModal(item)"
                                            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600 dark:text-gray-400 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                                            title="Hapus Akun"
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
                        </tbody>
                    </table>
                </div>

                <!-- Empty State Table -->
                <div
                    v-if="accounts.data.length === 0"
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
                                stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                            />
                        </svg>
                    </div>
                    <h3
                        class="text-sm font-semibold text-gray-900 dark:text-white"
                    >
                        Tidak ada akun COA ditemukan
                    </h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Silakan ubah kata kunci pencarian atau buat akun baru.
                    </p>
                </div>

                <!-- Pagination -->
                <div
                    v-if="accounts.data.length > 0"
                    class="border-t border-gray-200 px-6 py-4 dark:border-gray-700/60"
                >
                    <Pagination :links="accounts.links" />
                </div>
            </div>

            <!-- VIEW 2: TREE VIEW -->
            <div
                v-else
                class="shadow-xs rounded-xl border border-gray-200 bg-white dark:border-gray-700/60 dark:bg-gray-800"
            >
                <!-- Tree Toolbar -->
                <div
                    class="flex items-center justify-between border-b border-gray-200 bg-gray-50/70 px-4 py-3 dark:border-gray-700/60 dark:bg-gray-900/60"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="text-xs font-semibold text-gray-700 dark:text-gray-200"
                        >
                            Struktur Bagan Akun Hierarki
                        </span>
                        <span
                            class="rounded bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-700 dark:border dark:border-blue-800/40 dark:bg-blue-950/60 dark:text-blue-300"
                        >
                            {{ treeAccounts.length }} Akun Utama
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="expandAll"
                            class="shadow-2xs inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
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
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                            Buka Semua
                        </button>
                        <button
                            type="button"
                            @click="collapseAll"
                            class="shadow-2xs inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
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
                                    d="M5 15l7-7 7 7"
                                />
                            </svg>
                            Tutup Semua
                        </button>
                    </div>
                </div>

                <!-- Tree Content Container -->
                <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                    <CoaTreeNode
                        v-for="rootNode in treeAccounts"
                        :key="rootNode.id"
                        :node="rootNode"
                        :expanded-keys="expandedKeys"
                        :depth="0"
                        :search="search"
                        @toggle="toggleNode"
                        @add-child="openCreateModal($event)"
                        @edit="openEditModal($event)"
                        @delete="openDeleteModal($event)"
                    />
                </div>

                <!-- Empty State Tree -->
                <div v-if="treeAccounts.length === 0" class="py-16 text-center">
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
                                stroke-width="2"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"
                            />
                        </svg>
                    </div>
                    <h3
                        class="text-sm font-semibold text-gray-900 dark:text-white"
                    >
                        Tidak ada struktur akun ditemukan
                    </h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Coba ubah kata kunci atau buat akun root baru.
                    </p>
                </div>
            </div>
        </div>

        <!-- MODAL 1: CREATE COA -->
        <Modal
            :show="showCreateModal"
            @close="showCreateModal = false"
            max-width="2xl"
        >
            <form @submit.prevent="submitCreate" class="p-6">
                <!-- Header -->
                <div
                    class="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-700"
                >
                    <div>
                        <h3
                            class="text-lg font-bold text-gray-900 dark:text-white"
                        >
                            Tambah Akun COA Baru
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Lengkapi informasi akun, posisi saldo normal, dan
                            hierarki induk.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="showCreateModal = false"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
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
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Form Fields -->
                <div class="mt-6 space-y-5">
                    <!-- Parent Account Selector -->
                    <div>
                        <InputLabel
                            for="create_parent_code"
                            value="Akun Induk (Parent Code)"
                        />
                        <select
                            id="create_parent_code"
                            v-model="createForm.parent_code"
                            @change="onParentChangeCreate"
                            class="shadow-xs mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400"
                        >
                            <option value="">
                                [ Tanpa Parent - Akun Utama Root (Level 1) ]
                            </option>
                            <option
                                v-for="p in availableParents"
                                :key="p.id"
                                :value="p.account_code"
                            >
                                {{ p.account_code }} -
                                {{ p.account_name }} (Level {{ p.level }} |
                                {{ p.kategori.toUpperCase() }})
                            </option>
                        </select>
                        <InputError
                            class="mt-1"
                            :message="createForm.errors.parent_code"
                        />
                        <p
                            v-if="createForm.parent_code"
                            class="mt-1 flex items-center gap-1 text-[11px] text-amber-600 dark:text-amber-400"
                        >
                            <svg
                                class="h-3.5 w-3.5 shrink-0"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            Akun parent yang dipilih akan otomatis disetel
                            menjadi akun non-postable (header).
                        </p>
                    </div>

                    <!-- Account Code & Name -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-1">
                            <InputLabel
                                for="create_account_code"
                                value="Kode Akun *"
                            />
                            <TextInput
                                id="create_account_code"
                                v-model="createForm.account_code"
                                type="text"
                                class="mt-1 block w-full font-mono text-sm"
                                placeholder="Contoh: 1111"
                                required
                            />
                            <InputError
                                class="mt-1"
                                :message="createForm.errors.account_code"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel
                                for="create_account_name"
                                value="Nama Akun *"
                            />
                            <TextInput
                                id="create_account_name"
                                v-model="createForm.account_name"
                                type="text"
                                class="mt-1 block w-full text-sm"
                                placeholder="Contoh: Kas Operasional"
                                required
                            />
                            <InputError
                                class="mt-1"
                                :message="createForm.errors.account_name"
                            />
                        </div>
                    </div>

                    <!-- Level (Computed) & Kategori & Jenis -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <!-- Level -->
                        <div>
                            <InputLabel value="Tingkat (Level)" />
                            <div
                                class="mt-1 flex h-10 items-center rounded-md border border-gray-200 bg-gray-100 px-3 text-sm font-bold text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            >
                                Level {{ createForm.level }}
                                <span
                                    class="ms-2 text-[10px] font-normal text-gray-500 dark:text-gray-400"
                                    >(Otomatis)</span
                                >
                            </div>
                        </div>

                        <!-- Kategori (PL / BS) -->
                        <div>
                            <InputLabel
                                for="create_kategori"
                                value="Kategori Laporan *"
                            />
                            <select
                                id="create_kategori"
                                v-model="createForm.kategori"
                                class="shadow-xs mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400"
                            >
                                <option value="bs">
                                    Neraca (Balance Sheet)
                                </option>
                                <option value="pl">
                                    Laba Rugi (Profit & Loss)
                                </option>
                            </select>
                            <InputError
                                class="mt-1"
                                :message="createForm.errors.kategori"
                            />
                        </div>

                        <!-- Jenis (Debit / Credit) -->
                        <div>
                            <InputLabel
                                for="create_jenis"
                                value="Saldo Normal *"
                            />
                            <select
                                id="create_jenis"
                                v-model="createForm.jenis"
                                class="shadow-xs mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400"
                            >
                                <option value="debit">Debit</option>
                                <option value="credit">Kredit</option>
                            </select>
                            <InputError
                                class="mt-1"
                                :message="createForm.errors.jenis"
                            />
                        </div>
                    </div>

                    <!-- Postable Switch -->
                    <div
                        class="rounded-lg border border-gray-200 bg-gray-50 p-3.5 dark:border-gray-700 dark:bg-gray-900/60"
                    >
                        <label class="flex cursor-pointer items-start gap-3">
                            <input
                                v-model="createForm.postable"
                                type="checkbox"
                                class="shadow-xs mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900"
                            />
                            <div>
                                <span
                                    class="text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    Akun Postable (Dapat Dijurnal)
                                </span>
                                <p
                                    class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Jika dicentang, akun ini dapat dipilih dalam
                                    formulir transaksi jurnal umum / voucher.
                                    Jika akun memiliki sub-akun (child), sistem
                                    akan otomatis mengubahnya menjadi
                                    non-postable.
                                </p>
                            </div>
                        </label>
                    </div>

                    <!-- Description -->
                    <div>
                        <InputLabel
                            for="create_description"
                            value="Keterangan (Opsional)"
                        />
                        <textarea
                            id="create_description"
                            v-model="createForm.description"
                            rows="2"
                            class="shadow-xs mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400"
                            placeholder="Penjelasan fungsi akun atau pedoman penggunaan..."
                        ></textarea>
                        <InputError
                            class="mt-1"
                            :message="createForm.errors.description"
                        />
                    </div>

                    <!-- Status Aktif -->
                    <div class="flex items-center gap-2">
                        <input
                            id="create_is_active"
                            v-model="createForm.is_active"
                            type="checkbox"
                            class="shadow-xs rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900"
                        />
                        <InputLabel
                            for="create_is_active"
                            value="Status Akun Aktif"
                            class="!mb-0"
                        />
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div
                    class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700"
                >
                    <Button
                        variant="secondary"
                        @click="showCreateModal = false"
                    >
                        Batal
                    </Button>
                    <Button variant="primary" :disabled="createForm.processing">
                        {{
                            createForm.processing
                                ? 'Menyimpan...'
                                : 'Simpan Akun COA'
                        }}
                    </Button>
                </div>
            </form>
        </Modal>

        <!-- MODAL 2: EDIT COA -->
        <Modal
            :show="showEditModal"
            @close="showEditModal = false"
            max-width="2xl"
        >
            <form @submit.prevent="submitEdit" class="p-6">
                <!-- Header -->
                <div
                    class="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-700"
                >
                    <div>
                        <h3
                            class="text-lg font-bold text-gray-900 dark:text-white"
                        >
                            Edit Akun COA: {{ accountToEdit?.account_code }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Perbarui informasi akun, level hierarki, dan
                            pengaturan lainnya.
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="showEditModal = false"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
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
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Form Fields -->
                <div class="mt-6 space-y-5">
                    <!-- Parent Account Selector -->
                    <div>
                        <InputLabel
                            for="edit_parent_code"
                            value="Akun Induk (Parent Code)"
                        />
                        <select
                            id="edit_parent_code"
                            v-model="editForm.parent_code"
                            @change="onParentChangeEdit"
                            class="shadow-xs mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400"
                        >
                            <option value="">
                                [ Tanpa Parent - Akun Utama Root (Level 1) ]
                            </option>
                            <option
                                v-for="p in availableParentsForEdit"
                                :key="p.id"
                                :value="p.account_code"
                            >
                                {{ p.account_code }} -
                                {{ p.account_name }} (Level {{ p.level }})
                            </option>
                        </select>
                        <InputError
                            class="mt-1"
                            :message="editForm.errors.parent_code"
                        />
                    </div>

                    <!-- Account Code & Name -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-1">
                            <InputLabel
                                for="edit_account_code"
                                value="Kode Akun *"
                            />
                            <TextInput
                                id="edit_account_code"
                                v-model="editForm.account_code"
                                type="text"
                                class="mt-1 block w-full font-mono text-sm"
                                required
                            />
                            <InputError
                                class="mt-1"
                                :message="editForm.errors.account_code"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel
                                for="edit_account_name"
                                value="Nama Akun *"
                            />
                            <TextInput
                                id="edit_account_name"
                                v-model="editForm.account_name"
                                type="text"
                                class="mt-1 block w-full text-sm"
                                required
                            />
                            <InputError
                                class="mt-1"
                                :message="editForm.errors.account_name"
                            />
                        </div>
                    </div>

                    <!-- Level & Kategori & Jenis -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <InputLabel value="Tingkat (Level)" />
                            <div
                                class="mt-1 flex h-10 items-center rounded-md border border-gray-200 bg-gray-100 px-3 text-sm font-bold text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                            >
                                Level {{ editForm.level }}
                                <span
                                    class="ms-2 text-[10px] font-normal text-gray-500 dark:text-gray-400"
                                    >(Otomatis)</span
                                >
                            </div>
                        </div>

                        <div>
                            <InputLabel
                                for="edit_kategori"
                                value="Kategori Laporan *"
                            />
                            <select
                                id="edit_kategori"
                                v-model="editForm.kategori"
                                class="shadow-xs mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400"
                            >
                                <option value="bs">
                                    Neraca (Balance Sheet)
                                </option>
                                <option value="pl">
                                    Laba Rugi (Profit & Loss)
                                </option>
                            </select>
                            <InputError
                                class="mt-1"
                                :message="editForm.errors.kategori"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="edit_jenis"
                                value="Saldo Normal *"
                            />
                            <select
                                id="edit_jenis"
                                v-model="editForm.jenis"
                                class="shadow-xs mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400"
                            >
                                <option value="debit">Debit</option>
                                <option value="credit">Kredit</option>
                            </select>
                            <InputError
                                class="mt-1"
                                :message="editForm.errors.jenis"
                            />
                        </div>
                    </div>

                    <!-- Postable Switch -->
                    <div
                        class="rounded-lg border border-gray-200 bg-gray-50 p-3.5 dark:border-gray-700 dark:bg-gray-900/60"
                    >
                        <label
                            class="flex items-start gap-3"
                            :class="
                                editAccountHasChildren
                                    ? 'cursor-not-allowed opacity-75'
                                    : 'cursor-pointer'
                            "
                        >
                            <input
                                v-model="editForm.postable"
                                :disabled="editAccountHasChildren"
                                type="checkbox"
                                class="shadow-xs mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900"
                            />
                            <div>
                                <span
                                    class="text-sm font-semibold text-gray-900 dark:text-white"
                                >
                                    Akun Postable (Dapat Dijurnal)
                                </span>
                                <p
                                    v-if="editAccountHasChildren"
                                    class="mt-0.5 text-xs font-semibold text-amber-600 dark:text-amber-400"
                                >
                                    Akun ini memiliki sub-akun (child), sehingga
                                    otomatis menjadi akun header dan tidak dapat
                                    dipilih dalam penjournalan (postable =
                                    false).
                                </p>
                                <p
                                    v-else
                                    class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Centang jika akun ini dapat dipilih langsung
                                    saat membuat jurnal transaksi umum.
                                </p>
                            </div>
                        </label>
                    </div>

                    <!-- Description -->
                    <div>
                        <InputLabel
                            for="edit_description"
                            value="Keterangan (Opsional)"
                        />
                        <textarea
                            id="edit_description"
                            v-model="editForm.description"
                            rows="2"
                            class="shadow-xs mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-blue-400"
                        ></textarea>
                        <InputError
                            class="mt-1"
                            :message="editForm.errors.description"
                        />
                    </div>

                    <!-- Status Aktif -->
                    <div class="flex items-center gap-2">
                        <input
                            id="edit_is_active"
                            v-model="editForm.is_active"
                            type="checkbox"
                            class="shadow-xs rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900"
                        />
                        <InputLabel
                            for="edit_is_active"
                            value="Status Akun Aktif"
                            class="!mb-0"
                        />
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div
                    class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700"
                >
                    <Button variant="secondary" @click="showEditModal = false">
                        Batal
                    </Button>
                    <Button variant="primary" :disabled="editForm.processing">
                        {{
                            editForm.processing
                                ? 'Menyimpan...'
                                : 'Perbarui Akun COA'
                        }}
                    </Button>
                </div>
            </form>
        </Modal>

        <!-- MODAL 3: DELETE CONFIRMATION -->
        <Modal
            :show="showDeleteModal"
            @close="showDeleteModal = false"
            max-width="md"
        >
            <div class="p-6">
                <!-- Icon -->
                <div
                    class="mx-auto flex h-12 w-12 items-center justify-center rounded-full"
                    :class="
                        deleteAccountHasChildren
                            ? 'bg-amber-100 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400'
                            : 'bg-red-100 text-red-600 dark:bg-red-950/60 dark:text-red-400'
                    "
                >
                    <svg
                        class="h-6 w-6"
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

                <!-- Title & Message -->
                <div class="mt-4 text-center">
                    <h3
                        class="text-base font-bold text-gray-900 dark:text-white"
                    >
                        {{
                            deleteAccountHasChildren
                                ? 'Akun Tidak Dapat Dihapus'
                                : 'Hapus Akun COA'
                        }}
                    </h3>

                    <!-- If account has children -->
                    <div
                        v-if="deleteAccountHasChildren"
                        class="mt-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-start text-xs text-amber-800 dark:border-amber-800/60 dark:bg-amber-950/50 dark:text-amber-300"
                    >
                        <p class="font-bold">Perhatian:</p>
                        <p class="mt-1">
                            Akun
                            <strong
                                >{{ accountToDelete?.account_code }} -
                                {{ accountToDelete?.account_name }}</strong
                            >
                            memiliki sub-akun (child).
                        </p>
                        <p class="mt-1">
                            Untuk menjaga integritas buku besar dan laporan
                            keuangan, Anda harus menghapus atau memindahkan
                            seluruh sub-akun terlebih dahulu sebelum menghapus
                            akun ini.
                        </p>
                    </div>

                    <!-- If account has NO children -->
                    <p
                        v-else
                        class="mt-2 text-xs text-gray-500 dark:text-gray-400"
                    >
                        Apakah Anda yakin ingin menghapus akun
                        <span class="font-bold text-gray-900 dark:text-white">
                            {{ accountToDelete?.account_code }} -
                            {{ accountToDelete?.account_name }} </span
                        >? Data akan dipindahkan ke Tempat Sampah (Recycle Bin)
                        dan dapat dipulihkan kapan saja.
                    </p>
                </div>

                <!-- Footer Buttons -->
                <div class="mt-6 flex justify-end gap-3">
                    <Button
                        variant="secondary"
                        @click="showDeleteModal = false"
                    >
                        {{ deleteAccountHasChildren ? 'Tutup' : 'Batal' }}
                    </Button>
                    <Button
                        variant="danger"
                        v-if="!deleteAccountHasChildren"
                        @click="submitDelete"
                        :disabled="isDeleting"
                    >
                        {{
                            isDeleting
                                ? 'Menghapus...'
                                : 'Hapus ke Tempat Sampah'
                        }}
                    </Button>
                </div>
            </div>
        </Modal>
    </AuthenticatedTemplate>
</template>
