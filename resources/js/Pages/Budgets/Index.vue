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
import { ref, watch, computed } from 'vue';

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

const applyFilters = () => {
    router.get(
        route('budgets.index'),
        {
            search: search.value || undefined,
            status: statusFilter.value !== '' ? statusFilter.value : undefined,
            classification_id: classificationFilter.value || undefined,
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

watch(classificationFilter, () => {
    applyFilters();
});

// Helper to generate code via endpoint or suggested fallback
const isGeneratingCode = ref(false);
const fetchGeneratedCode = async (targetForm) => {
    isGeneratingCode.value = true;
    try {
        const response = await fetch(route('budgets.generate-code'));
        if (response.ok) {
            const data = await response.json();
            targetForm.code = data.code;
        } else {
            targetForm.code = props.suggestedCode || `BDG-${new Date().getFullYear()}-0001`;
        }
    } catch {
        targetForm.code = props.suggestedCode || `BDG-${new Date().getFullYear()}-0001`;
    } finally {
        isGeneratingCode.value = false;
    }
};

// Create Modal State & Form
const showCreateModal = ref(false);
const createForm = useForm({
    code: '',
    name: '',
    budget_classification_id: '',
    pic_id: '',
    description: '',
    is_active: true,
    checkers: [
        { user_id: '', role_title: 'Checker 1' },
    ],
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.code = props.suggestedCode || '';
    createForm.budget_classification_id = '';
    createForm.checkers = [
        { user_id: '', role_title: 'Checker 1' },
    ];
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
    createForm.checkers.splice(index, 1);
};

const submitCreate = () => {
    createForm.post(route('budgets.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// Edit Modal State & Form
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
    editForm.pic_id = budget.pic_id;
    editForm.description = budget.description || '';
    editForm.is_active = Boolean(budget.is_active);
    editForm.checkers = (budget.checkers || []).map((c, i) => ({
        user_id: c.user_id,
        role_title: c.role_title || `Checker ${i + 1}`,
    }));

    if (editForm.checkers.length === 0) {
        editForm.checkers.push({ user_id: '', role_title: 'Checker 1' });
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
    editForm.checkers.splice(index, 1);
};

const submitEdit = () => {
    if (!budgetToEdit.value) return;
    editForm.put(route('budgets.update', budgetToEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            budgetToEdit.value = null;
        },
    });
};

// Detail / Flow Modal State
const showDetailModal = ref(false);
const selectedBudget = ref(null);

const openDetailModal = (budget) => {
    selectedBudget.value = budget;
    showDetailModal.value = true;
};

// Delete Modal State & Action
const showDeleteModal = ref(false);
const budgetToDelete = ref(null);
const isDeleting = ref(false);

const openDeleteModal = (budget) => {
    budgetToDelete.value = budget;
    showDeleteModal.value = true;
};

const submitDelete = () => {
    if (!budgetToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('budgets.destroy', budgetToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            budgetToDelete.value = null;
        },
    });
};

// Helper: Get initials for avatar
const getInitials = (name) => {
    if (!name) return '?';
    return name
        .split(' ')
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
};
</script>

<template>
    <Head title="Master Budget" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Master Budget</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Kelola data master anggaran, PIC (Owner Anggaran), dan hierarki approval dinamis
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <PrimaryButton
                        type="button"
                        @click="openCreateModal"
                        class="flex items-center gap-2 shadow-sm shadow-indigo-500/20"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Budget
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stat Cards -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Card 1: Total Budget -->
                <div class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700/60 dark:bg-gray-800">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Budget</p>
                        <p class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ stats.total }}</p>
                    </div>
                </div>

                <!-- Card 2: Budget Aktif -->
                <div class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700/60 dark:bg-gray-800">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Budget Aktif</p>
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

                <!-- Card 4: Total PIC -->
                <div class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700/60 dark:bg-gray-800">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">PIC Budget Terdaftar</p>
                        <p class="text-2xl font-extrabold text-sky-600 dark:text-sky-400">{{ stats.total_pic }}</p>
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
                        placeholder="Cari kode budget, nama anggaran, atau PIC..."
                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2 ps-10 pe-9 text-xs text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-indigo-400"
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
                        v-model="classificationFilter"
                        class="rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-xs text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-indigo-400"
                    >
                        <option value="">Semua Klasifikasi</option>
                        <option v-for="c in classifications" :key="c.id" :value="c.id">
                            {{ c.name }}
                        </option>
                    </select>

                    <select
                        v-model="statusFilter"
                        class="rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-xs text-gray-900 focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-indigo-400"
                    >
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Non-Aktif</option>
                    </select>
                </div>
            </div>

            <!-- Table of Budgets -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-700/60 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-left text-xs dark:divide-gray-700/60">
                        <thead class="bg-gray-50/75 text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-5 py-3.5 font-semibold">Kode & Nama Budget</th>
                                <th scope="col" class="px-5 py-3.5 font-semibold">PIC Budget (Owner)</th>
                                <th scope="col" class="px-5 py-3.5 font-semibold">Hierarki Approval (Checkers)</th>
                                <th scope="col" class="px-5 py-3.5 font-semibold text-center">Status</th>
                                <th scope="col" class="px-5 py-3.5 font-semibold text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700/50">
                            <tr
                                v-for="budget in budgets.data"
                                :key="budget.id"
                                class="transition-colors hover:bg-gray-50/60 dark:hover:bg-gray-700/30"
                            >
                                <!-- Kode & Nama Budget -->
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 font-mono text-xs font-bold text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                                            BDG
                                        </div>
                                        <div>
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span class="font-mono text-xs font-bold text-gray-900 dark:text-white">
                                                    {{ budget.code }}
                                                </span>
                                                <span
                                                    v-if="budget.classification"
                                                    class="inline-flex items-center rounded-md bg-purple-50 px-1.5 py-0.5 text-[10px] font-semibold text-purple-700 ring-1 ring-inset ring-purple-600/20 dark:bg-purple-950/50 dark:text-purple-300 dark:ring-purple-400/20"
                                                >
                                                    {{ budget.classification.name }}
                                                </span>
                                            </div>
                                            <p class="font-medium text-gray-700 dark:text-gray-300">
                                                {{ budget.name }}
                                            </p>
                                            <p v-if="budget.description" class="line-clamp-1 text-[11px] text-gray-400 dark:text-gray-500">
                                                {{ budget.description }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- PIC Budget -->
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white shadow-xs">
                                            {{ getInitials(budget.pic?.name) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                {{ budget.pic?.name || 'Tidak Ditemukan' }}
                                            </p>
                                            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                                {{ budget.pic?.email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Approval Chain (Checkers) -->
                                <td class="px-5 py-4">
                                    <div v-if="budget.checkers && budget.checkers.length > 0" class="flex flex-wrap items-center gap-1.5">
                                        <div
                                            v-for="(checker, idx) in budget.checkers"
                                            :key="checker.id"
                                            class="inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-[11px] font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-700/60 dark:text-gray-300"
                                            :title="`${checker.role_title || ('Checker ' + (idx + 1))}: ${checker.user?.name} (${checker.user?.email})`"
                                        >
                                            <span class="flex h-4 w-4 items-center justify-center rounded-full bg-indigo-100 text-[9px] font-bold text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                                                {{ idx + 1 }}
                                            </span>
                                            <span class="max-w-[120px] truncate">
                                                {{ checker.user?.name || 'User' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div v-else class="text-[11px] italic text-gray-400 dark:text-gray-500">
                                        Belum ada checker
                                    </div>
                                </td>

                                <!-- Status Aktif -->
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <span
                                        :class="[
                                            budget.is_active
                                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-400 dark:ring-emerald-500/30'
                                                : 'bg-gray-100 text-gray-700 ring-gray-600/10 dark:bg-gray-700 dark:text-gray-300 dark:ring-gray-400/20',
                                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                budget.is_active ? 'bg-emerald-500' : 'bg-gray-400',
                                                'h-1.5 w-1.5 rounded-full',
                                            ]"
                                        />
                                        {{ budget.is_active ? 'Aktif' : 'Non-Aktif' }}
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="px-5 py-4 text-end whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- View Flow Detail -->
                                        <button
                                            type="button"
                                            @click="openDetailModal(budget)"
                                            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-indigo-50 hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-indigo-950/50 dark:hover:text-indigo-400"
                                            title="Lihat Alur Approval Pipeline"
                                        >
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>

                                        <!-- Edit -->
                                        <button
                                            type="button"
                                            @click="openEditModal(budget)"
                                            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                            title="Edit Budget"
                                        >
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>

                                        <!-- Delete -->
                                        <button
                                            type="button"
                                            @click="openDeleteModal(budget)"
                                            class="rounded-lg p-1.5 text-gray-500 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:text-gray-400 dark:hover:bg-rose-950/50 dark:hover:text-rose-400"
                                            title="Hapus Budget"
                                        >
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!budgets.data || budgets.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                                            <svg class="h-8 w-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Tidak ada data budget</h3>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ search ? 'Tidak ditemukan budget dengan kata kunci tersebut.' : 'Mulai dengan menambahkan master budget baru.' }}
                                        </p>
                                        <div class="mt-4" v-if="!search">
                                            <PrimaryButton type="button" @click="openCreateModal" class="text-xs">
                                                + Tambah Master Budget
                                            </PrimaryButton>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="budgets.links && budgets.links.length > 3" class="border-t border-gray-200 px-5 py-4 dark:border-gray-700/60">
                    <Pagination :links="budgets.links" />
                </div>
            </div>
        </div>

        <!-- CREATE BUDGET MODAL -->
        <Modal :show="showCreateModal" @close="showCreateModal = false" max-width="2xl">
            <form @submit.prevent="submitCreate" class="p-6">
                <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Tambah Master Budget</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Daftarkan budget anggaran dan konfigurasikan alur approval</p>
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
                    <!-- Kode Budget with Auto Generate Helper -->
                    <div>
                        <div class="flex items-center justify-between">
                            <InputLabel for="create_code" value="Kode Budget *" />
                            <button
                                type="button"
                                @click="fetchGeneratedCode(createForm)"
                                :disabled="isGeneratingCode"
                                class="inline-flex items-center gap-1 font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300"
                            >
                                <svg class="h-3.5 w-3.5" :class="{ 'animate-spin': isGeneratingCode }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span>Generate Kode</span>
                            </button>
                        </div>
                        <TextInput
                            id="create_code"
                            type="text"
                            v-model="createForm.code"
                            class="mt-1 block w-full font-mono uppercase"
                            placeholder="Contoh: BDG-2026-0001"
                            required
                        />
                        <InputError :message="createForm.errors.code" class="mt-1" />
                    </div>

                    <!-- Nama Budget -->
                    <div>
                        <InputLabel for="create_name" value="Nama Budget *" />
                        <TextInput
                            id="create_name"
                            type="text"
                            v-model="createForm.name"
                            class="mt-1 block w-full"
                            placeholder="Contoh: Biaya Pemeliharaan Mesin Line 1"
                            required
                        />
                        <InputError :message="createForm.errors.name" class="mt-1" />
                    </div>

                    <!-- Klasifikasi Budget (Opsional) -->
                    <div>
                        <InputLabel for="create_classification_id" value="Klasifikasi Budget (Opsional)" />
                        <select
                            id="create_classification_id"
                            v-model="createForm.budget_classification_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600 text-xs"
                        >
                            <option value="">-- Tanpa Klasifikasi (Opsional) --</option>
                            <option v-for="c in classifications" :key="c.id" :value="c.id">
                                {{ c.name }}
                            </option>
                        </select>
                        <InputError :message="createForm.errors.budget_classification_id" class="mt-1" />
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            Pengelompokan jenis anggaran (misal: CAPEX, OPEX, Maintenance, dsb.)
                        </p>
                    </div>

                    <!-- PIC (Owner Anggaran) -->
                    <div>
                        <InputLabel for="create_pic_id" value="PIC Budget (Owner Anggaran) *" />
                        <select
                            id="create_pic_id"
                            v-model="createForm.pic_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600 text-xs"
                            required
                        >
                            <option value="" disabled>-- Pilih User sebagai PIC Budget --</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }} ({{ user.email }})
                            </option>
                        </select>
                        <InputError :message="createForm.errors.pic_id" class="mt-1" />
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            User ini bertindak sebagai pemohon (inisiator) belanja atas budget ini.
                        </p>
                    </div>

                    <!-- Dynamic Checkers Section -->
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-4 dark:border-gray-700/80 dark:bg-gray-800/40">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white">Rantai Persetujuan (Approval Flow)</h4>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                    Tentukan hierarki checker secara dinamis (Checker 1, Checker 2, dst.). Rantai approval akan diproses berurutan.
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="addCreateChecker"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-2.5 py-1.5 text-[11px] font-semibold text-white shadow-xs hover:bg-indigo-700 focus:outline-none dark:bg-indigo-500 dark:hover:bg-indigo-600"
                            >
                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Level Checker
                            </button>
                        </div>

                        <div class="mt-3 space-y-2.5">
                            <div
                                v-for="(checker, index) in createForm.checkers"
                                :key="index"
                                class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white p-2.5 shadow-xs dark:border-gray-700 dark:bg-gray-800"
                            >
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-indigo-100 text-xs font-bold text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300">
                                    {{ index + 1 }}
                                </div>
                                <div class="w-32 shrink-0">
                                    <TextInput
                                        type="text"
                                        v-model="checker.role_title"
                                        :placeholder="`Checker ${index + 1}`"
                                        class="block w-full py-1.5 text-xs"
                                    />
                                </div>
                                <div class="flex-1">
                                    <select
                                        v-model="checker.user_id"
                                        class="block w-full rounded-md border-gray-300 py-1.5 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-xs"
                                        required
                                    >
                                        <option value="" disabled>-- Pilih User Checker {{ index + 1 }} --</option>
                                        <option v-for="user in users" :key="user.id" :value="user.id">
                                            {{ user.name }} ({{ user.email }})
                                        </option>
                                    </select>
                                </div>
                                <button
                                    type="button"
                                    @click="removeCreateChecker(index)"
                                    class="rounded-md p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/50 dark:hover:text-rose-400"
                                    title="Hapus Level Checker"
                                >
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>

                            <div v-if="createForm.checkers.length === 0" class="rounded-lg border border-dashed border-gray-300 p-4 text-center text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                Belum ada checker ditambahkan. Klik tombol "Tambah Level Checker" di atas.
                            </div>
                        </div>

                        <!-- Checkers errors if any -->
                        <div v-if="Object.keys(createForm.errors).some(k => k.startsWith('checkers'))" class="mt-2 text-[11px] text-rose-500">
                            Harap pastikan semua baris checker memilih user yang valid dan tidak ada user pemeriksa yang duplikat.
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <InputLabel for="create_description" value="Keterangan / Catatan (Opsional)" />
                        <textarea
                            id="create_description"
                            v-model="createForm.description"
                            rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600 text-xs"
                            placeholder="Deskripsi peruntukan budget, lingkup departemen, dsb."
                        ></textarea>
                        <InputError :message="createForm.errors.description" class="mt-1" />
                    </div>

                    <!-- Status Aktif -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            id="create_is_active"
                            v-model="createForm.is_active"
                            class="rounded border-gray-300 text-indigo-600 shadow-xs focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600"
                        />
                        <label for="create_is_active" class="select-none text-xs font-medium text-gray-700 dark:text-gray-300">
                            Aktifkan Master Budget ini
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <SecondaryButton type="button" @click="showCreateModal = false">
                        Batal
                    </SecondaryButton>
                    <PrimaryButton type="submit" :disabled="createForm.processing">
                        Simpan Master Budget
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- EDIT BUDGET MODAL -->
        <Modal :show="showEditModal" @close="showEditModal = false" max-width="2xl">
            <form @submit.prevent="submitEdit" class="p-6">
                <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Edit Master Budget</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Perbarui informasi budget dan alur persetujuan</p>
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
                    <!-- Kode Budget -->
                    <div>
                        <InputLabel for="edit_code" value="Kode Budget *" />
                        <TextInput
                            id="edit_code"
                            type="text"
                            v-model="editForm.code"
                            class="mt-1 block w-full font-mono uppercase"
                            required
                        />
                        <InputError :message="editForm.errors.code" class="mt-1" />
                    </div>

                    <!-- Nama Budget -->
                    <div>
                        <InputLabel for="edit_name" value="Nama Budget *" />
                        <TextInput
                            id="edit_name"
                            type="text"
                            v-model="editForm.name"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="editForm.errors.name" class="mt-1" />
                    </div>

                    <!-- Klasifikasi Budget (Opsional) -->
                    <div>
                        <InputLabel for="edit_classification_id" value="Klasifikasi Budget (Opsional)" />
                        <select
                            id="edit_classification_id"
                            v-model="editForm.budget_classification_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600 text-xs"
                        >
                            <option value="">-- Tanpa Klasifikasi (Opsional) --</option>
                            <option v-for="c in classifications" :key="c.id" :value="c.id">
                                {{ c.name }}
                            </option>
                        </select>
                        <InputError :message="editForm.errors.budget_classification_id" class="mt-1" />
                    </div>

                    <!-- PIC (Owner Anggaran) -->
                    <div>
                        <InputLabel for="edit_pic_id" value="PIC Budget (Owner Anggaran) *" />
                        <select
                            id="edit_pic_id"
                            v-model="editForm.pic_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600 text-xs"
                            required
                        >
                            <option value="" disabled>-- Pilih User sebagai PIC Budget --</option>
                            <option v-for="user in users" :key="user.id" :value="user.id">
                                {{ user.name }} ({{ user.email }})
                            </option>
                        </select>
                        <InputError :message="editForm.errors.pic_id" class="mt-1" />
                    </div>

                    <!-- Dynamic Checkers Section -->
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-4 dark:border-gray-700/80 dark:bg-gray-800/40">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white">Rantai Persetujuan (Approval Flow)</h4>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                    Sesuaikan urutan dan daftar pemeriksa secara dinamis
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="addEditChecker"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-2.5 py-1.5 text-[11px] font-semibold text-white shadow-xs hover:bg-indigo-700 focus:outline-none dark:bg-indigo-500 dark:hover:bg-indigo-600"
                            >
                                <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                Tambah Level Checker
                            </button>
                        </div>

                        <div class="mt-3 space-y-2.5">
                            <div
                                v-for="(checker, index) in editForm.checkers"
                                :key="index"
                                class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white p-2.5 shadow-xs dark:border-gray-700 dark:bg-gray-800"
                            >
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-indigo-100 text-xs font-bold text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300">
                                    {{ index + 1 }}
                                </div>
                                <div class="w-32 shrink-0">
                                    <TextInput
                                        type="text"
                                        v-model="checker.role_title"
                                        :placeholder="`Checker ${index + 1}`"
                                        class="block w-full py-1.5 text-xs"
                                    />
                                </div>
                                <div class="flex-1">
                                    <select
                                        v-model="checker.user_id"
                                        class="block w-full rounded-md border-gray-300 py-1.5 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-xs"
                                        required
                                    >
                                        <option value="" disabled>-- Pilih User Checker {{ index + 1 }} --</option>
                                        <option v-for="user in users" :key="user.id" :value="user.id">
                                            {{ user.name }} ({{ user.email }})
                                        </option>
                                    </select>
                                </div>
                                <button
                                    type="button"
                                    @click="removeEditChecker(index)"
                                    class="rounded-md p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/50 dark:hover:text-rose-400"
                                    title="Hapus Level Checker"
                                >
                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>

                            <div v-if="editForm.checkers.length === 0" class="rounded-lg border border-dashed border-gray-300 p-4 text-center text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                Belum ada checker. Klik "Tambah Level Checker".
                            </div>
                        </div>

                        <div v-if="Object.keys(editForm.errors).some(k => k.startsWith('checkers'))" class="mt-2 text-[11px] text-rose-500">
                            Harap pastikan semua baris checker memilih user yang valid dan tidak ada user pemeriksa yang duplikat.
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <InputLabel for="edit_description" value="Keterangan / Catatan (Opsional)" />
                        <textarea
                            id="edit_description"
                            v-model="editForm.description"
                            rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:focus:border-indigo-600 dark:focus:ring-indigo-600 text-xs"
                        ></textarea>
                        <InputError :message="editForm.errors.description" class="mt-1" />
                    </div>

                    <!-- Status Aktif -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            id="edit_is_active"
                            v-model="editForm.is_active"
                            class="rounded border-gray-300 text-indigo-600 shadow-xs focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:focus:ring-indigo-600"
                        />
                        <label for="edit_is_active" class="select-none text-xs font-medium text-gray-700 dark:text-gray-300">
                            Aktifkan Master Budget ini
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

        <!-- DETAIL FLOW PIPELINE MODAL -->
        <Modal :show="showDetailModal" @close="showDetailModal = false" max-width="3xl">
            <div class="p-6">
                <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-700">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Alur Persetujuan (Approval Flow)</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Visualisasi tahapan persetujuan transaksi anggaran ERP
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showDetailModal = false"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                    >
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div v-if="selectedBudget" class="mt-5 space-y-6">
                    <!-- Budget Summary Card -->
                    <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ selectedBudget.code }}
                                    </span>
                                    <span
                                        v-if="selectedBudget.classification"
                                        class="inline-flex items-center rounded-md bg-purple-50 px-1.5 py-0.5 text-[10px] font-semibold text-purple-700 ring-1 ring-inset ring-purple-600/20 dark:bg-purple-950/50 dark:text-purple-300 dark:ring-purple-400/20"
                                    >
                                        {{ selectedBudget.classification.name }}
                                    </span>
                                </div>
                                <h4 class="text-base font-bold text-gray-900 dark:text-white">
                                    {{ selectedBudget.name }}
                                </h4>
                                <p v-if="selectedBudget.description" class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ selectedBudget.description }}
                                </p>
                            </div>
                            <div>
                                <span
                                    :class="[
                                        selectedBudget.is_active
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-400 dark:ring-emerald-500/30'
                                            : 'bg-gray-100 text-gray-700 ring-gray-600/10 dark:bg-gray-700 dark:text-gray-300 dark:ring-gray-400/20',
                                        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                    ]"
                                >
                                    {{ selectedBudget.is_active ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Flow Stepper / Pipeline -->
                    <div>
                        <h5 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Rantai Eksekusi Approval
                        </h5>

                        <div class="mt-4 flex flex-col gap-4">
                            <!-- Stage 0: PIC (Pemohon / Owner) -->
                            <div class="relative flex items-start gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-600 font-bold text-white shadow-md shadow-blue-500/20">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <div class="flex-1 rounded-xl border border-blue-200 bg-blue-50/50 p-3.5 dark:border-blue-900/50 dark:bg-blue-950/20">
                                    <div class="flex items-center justify-between">
                                        <span class="rounded-md bg-blue-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-800 dark:bg-blue-900/70 dark:text-blue-300">
                                            Tahap Awal: Pemohon / Owner
                                        </span>
                                        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400">
                                            Inisiator Anggaran
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white">
                                        {{ selectedBudget.pic?.name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ selectedBudget.pic?.email }}
                                    </p>
                                </div>
                            </div>

                            <!-- Dynamic Checkers Stages -->
                            <div
                                v-for="(checker, idx) in selectedBudget.checkers"
                                :key="checker.id"
                                class="relative flex items-start gap-4"
                            >
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-600 font-bold text-white shadow-md shadow-indigo-500/20">
                                    {{ idx + 1 }}
                                </div>
                                <div class="flex-1 rounded-xl border border-gray-200 bg-white p-3.5 shadow-xs dark:border-gray-700 dark:bg-gray-800">
                                    <div class="flex items-center justify-between">
                                        <span class="rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                            Level {{ idx + 1 }}: {{ checker.role_title || ('Checker ' + (idx + 1)) }}
                                        </span>
                                        <span class="text-[11px] font-medium text-amber-600 dark:text-amber-400">
                                            Approval Bertingkat
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white">
                                        {{ checker.user?.name || 'User Tidak Ditemukan' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ checker.user?.email }}
                                    </p>
                                </div>
                            </div>

                            <!-- Final Stage: Approved -->
                            <div class="relative flex items-start gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-600 font-bold text-white shadow-md shadow-emerald-500/20">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div class="flex-1 rounded-xl border border-emerald-200 bg-emerald-50/50 p-3.5 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                                    <span class="rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-800 dark:bg-emerald-900/70 dark:text-emerald-300">
                                        Tahap Akhir: Disetujui
                                    </span>
                                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                        Setelah seluruh level checker menyetujui, transaksi belanja anggaran langsung disetujui (Approved) untuk proses procurement / PO / finance.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end border-t border-gray-200 pt-4 dark:border-gray-700">
                    <SecondaryButton type="button" @click="showDetailModal = false">
                        Tutup
                    </SecondaryButton>
                </div>
            </div>
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
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Konfirmasi Hapus Budget</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Apakah Anda yakin ingin menghapus master budget ini?
                        </p>
                    </div>
                </div>

                <div v-if="budgetToDelete" class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800 text-xs">
                    <p class="font-bold text-gray-900 dark:text-white">{{ budgetToDelete.code }} - {{ budgetToDelete.name }}</p>
                    <p class="text-gray-500 dark:text-gray-400">PIC: {{ budgetToDelete.pic?.name }}</p>
                    <p class="mt-1 text-[11px] text-gray-400">
                        Catatan: Data ini dapat dipulihkan kembali sewaktu-waktu melalui menu <strong>Recycle Bin</strong>.
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
