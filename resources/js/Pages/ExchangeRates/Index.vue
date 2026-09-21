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
    rates: Object,
    currencies: Array,
    stats: Object,
    filters: Object,
});

// Filters
const search = ref(props.filters.search || '');
const selectedDate = ref(props.filters.date || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const selectedCurrency = ref(props.filters.currency || '');

let filterTimeout = null;

const applyFilters = () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(
            route('exchange-rates.index'),
            {
                search: search.value || undefined,
                date: selectedDate.value || undefined,
                start_date: (!selectedDate.value && startDate.value) ? startDate.value : undefined,
                end_date: (!selectedDate.value && endDate.value) ? endDate.value : undefined,
                currency: selectedCurrency.value || undefined,
            },
            { preserveState: true, replace: true }
        );
    }, 250);
};

watch([search, selectedDate, startDate, endDate, selectedCurrency], () => {
    applyFilters();
});

const resetFilters = () => {
    search.value = '';
    selectedDate.value = '';
    startDate.value = '';
    endDate.value = '';
    selectedCurrency.value = '';
};

// Set Quick Date filter
const setQuickDate = (dateVal) => {
    startDate.value = '';
    endDate.value = '';
    selectedDate.value = dateVal;
};

// Helper: Format Rupiah
const formatRupiah = (val, decimals = 2) => {
    if (val === null || val === undefined || isNaN(val)) return 'Rp 0,00';
    return 'Rp ' + Number(val).toLocaleString('id-ID', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: 4,
    });
};

// Helper: Format Date
const formatDateIndo = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

// --- MODAL SINKRONISASI BANK INDONESIA ---
const showSyncModal = ref(false);
const syncForm = useForm({
    start_date: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
    currency_codes: [],
});

const openSyncModal = () => {
    syncForm.reset();
    syncForm.clearErrors();
    syncForm.start_date = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    syncForm.end_date = new Date().toISOString().split('T')[0];
    syncForm.currency_codes = [];
    showSyncModal.value = true;
};

const setSyncPreset = (type) => {
    const today = new Date();
    const todayStr = today.toISOString().split('T')[0];

    if (type === 'today') {
        syncForm.start_date = todayStr;
        syncForm.end_date = todayStr;
    } else if (type === '7days') {
        const past = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
        syncForm.start_date = past.toISOString().split('T')[0];
        syncForm.end_date = todayStr;
    } else if (type === '30days') {
        const past = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
        syncForm.start_date = past.toISOString().split('T')[0];
        syncForm.end_date = todayStr;
    } else if (type === 'mtd') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        syncForm.start_date = firstDay.toISOString().split('T')[0];
        syncForm.end_date = todayStr;
    }
};

const submitSync = () => {
    syncForm.post(route('exchange-rates.sync'), {
        preserveScroll: true,
        onSuccess: () => {
            showSyncModal.value = false;
        },
    });
};

// --- MODAL TAMBAH KURS MANUAL ---
const showCreateModal = ref(false);
const createForm = useForm({
    currency_id: '',
    date: new Date().toISOString().split('T')[0],
    unit: 1.0,
    rate_buy: '',
    rate_sell: '',
    rate_middle: '',
    source: 'Manual',
});

// Auto calculate middle rate on create
const computedCreateMiddleRate = computed(() => {
    const buy = parseFloat(createForm.rate_buy) || 0;
    const sell = parseFloat(createForm.rate_sell) || 0;
    if (buy > 0 && sell > 0) {
        return ((buy + sell) / 2).toFixed(4);
    }
    return '';
});

watch(computedCreateMiddleRate, (val) => {
    if (val && !createForm.rate_middle_manual) {
        createForm.rate_middle = val;
    }
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.date = new Date().toISOString().split('T')[0];
    createForm.unit = 1.0;
    createForm.source = 'Manual';
    if (props.currencies.length > 0) {
        createForm.currency_id = props.currencies[0].id;
    }
    showCreateModal.value = true;
};

const submitCreate = () => {
    if (!createForm.rate_middle && computedCreateMiddleRate.value) {
        createForm.rate_middle = computedCreateMiddleRate.value;
    }
    createForm.post(route('exchange-rates.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// --- MODAL EDIT KURS ---
const showEditModal = ref(false);
const rateToEdit = ref(null);
const editForm = useForm({
    unit: 1.0,
    rate_buy: '',
    rate_sell: '',
    rate_middle: '',
    source: '',
});

const computedEditMiddleRate = computed(() => {
    const buy = parseFloat(editForm.rate_buy) || 0;
    const sell = parseFloat(editForm.rate_sell) || 0;
    if (buy > 0 && sell > 0) {
        return ((buy + sell) / 2).toFixed(4);
    }
    return '';
});

const openEditModal = (rate) => {
    rateToEdit.value = rate;
    editForm.reset();
    editForm.clearErrors();
    editForm.unit = Number(rate.unit);
    editForm.rate_buy = rate.rate_buy;
    editForm.rate_sell = rate.rate_sell;
    editForm.rate_middle = rate.rate_middle;
    editForm.source = rate.source;
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!rateToEdit.value) return;
    if (!editForm.rate_middle && computedEditMiddleRate.value) {
        editForm.rate_middle = computedEditMiddleRate.value;
    }
    editForm.put(route('exchange-rates.update', rateToEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            rateToEdit.value = null;
        },
    });
};

// --- MODAL HAPUS KURS ---
const showDeleteModal = ref(false);
const rateToDelete = ref(null);
const isDeleting = ref(false);

const openDeleteModal = (rate) => {
    rateToDelete.value = rate;
    showDeleteModal.value = true;
};

const submitDelete = () => {
    if (!rateToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('exchange-rates.destroy', rateToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            rateToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Master Exchange Rate (Kurs Nilai Tukar)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 ring-1 ring-emerald-500/20">
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                                Master Exchange Rate (Kurs Nilai Tukar IDR)
                            </h1>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                BI Live API
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            Pantau nilai tukar IDR terhadap mata uang terdaftar, kurs beli, kurs jual, dan kurs tengah (middle rate) Bank Indonesia.
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        @click="openSyncModal"
                        class="inline-flex items-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 px-3.5 py-2 text-xs font-semibold text-emerald-700 shadow-xs transition-all hover:bg-emerald-100 hover:border-emerald-400 active:scale-95 dark:border-emerald-700/60 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Sinkronisasi BI
                    </button>

                    <PrimaryButton
                        type="button"
                        @click="openCreateModal"
                        class="flex items-center gap-2"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Kurs
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Summary Stats Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700/60 dark:bg-gray-800">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Riwayat Kurs</p>
                            <p class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ stats.total_records }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700/60 dark:bg-gray-800">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Mata Uang Terdaftar</p>
                            <p class="text-2xl font-extrabold text-gray-900 dark:text-white">{{ stats.total_currencies }} Valuta</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700/60 dark:bg-gray-800">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Tanggal Kurs Terbaru</p>
                            <p class="text-base font-bold text-gray-900 dark:text-white">
                                {{ stats.latest_date ? formatDateIndo(stats.latest_date) : 'Belum Ada' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700/60 dark:bg-gray-800">
                    <div class="flex items-center gap-3.5">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Cron Job Harian</p>
                            <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400">Pukul 08:00 WIB</p>
                            <p class="text-[10px] text-gray-400">Otomatis sync Bank Indonesia</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Filter & Quick Date Selector Toolbar -->
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-700/60 dark:bg-gray-800">
                <div class="flex flex-col gap-4">
                    <!-- Top row: Specific Date selector & Quick buttons -->
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-3 dark:border-gray-700/50">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                📅 Lihat Kurs Pada Tanggal:
                            </span>
                            <input
                                type="date"
                                v-model="selectedDate"
                                class="rounded-lg border border-gray-300 bg-gray-50 px-3 py-1.5 text-xs text-gray-900 focus:border-emerald-500 focus:bg-white focus:ring-1 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                            <button
                                v-if="selectedDate"
                                @click="selectedDate = ''"
                                class="text-xs text-red-500 hover:underline"
                            >
                                Hapus Filter Tanggal
                            </button>
                        </div>

                        <!-- Quick Chips -->
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="text-[11px] text-gray-400">Pilihan Cepat:</span>
                            <button
                                type="button"
                                @click="setQuickDate(stats.latest_date)"
                                v-if="stats.latest_date"
                                :class="[
                                    'rounded-lg px-2.5 py-1 text-xs font-medium transition-colors',
                                    selectedDate === stats.latest_date
                                        ? 'bg-emerald-600 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                                ]"
                            >
                                Data Terakhir ({{ formatDateIndo(stats.latest_date) }})
                            </button>

                            <button
                                type="button"
                                @click="resetFilters"
                                class="rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                            >
                                Tampilkan Semua
                            </button>
                        </div>
                    </div>

                    <!-- Bottom row: Search and Secondary Filters -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Search -->
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
                                <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="search"
                                placeholder="Cari kode (USD, JPY), sumber..."
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2 ps-9 pe-8 text-xs text-gray-900 focus:border-emerald-500 focus:bg-white focus:ring-1 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400"
                            />
                            <button
                                v-if="search"
                                @click="search = ''"
                                class="absolute inset-y-0 end-0 flex items-center pe-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <!-- Currency Selector -->
                        <div>
                            <select
                                v-model="selectedCurrency"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2 px-3 text-xs text-gray-900 focus:border-emerald-500 focus:bg-white focus:ring-1 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="">Semua Mata Uang</option>
                                <option v-for="c in currencies" :key="c.id" :value="c.code">
                                    {{ c.code }} - {{ c.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Date Range From (when not filtering specific single date) -->
                        <div>
                            <input
                                type="date"
                                v-model="startDate"
                                :disabled="!!selectedDate"
                                placeholder="Dari Tanggal"
                                :class="[
                                    'block w-full rounded-lg border py-2 px-3 text-xs focus:ring-1',
                                    selectedDate
                                        ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed dark:bg-gray-800 dark:border-gray-700'
                                        : 'bg-gray-50 border-gray-300 text-gray-900 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                                ]"
                            />
                        </div>

                        <!-- Date Range To -->
                        <div>
                            <input
                                type="date"
                                v-model="endDate"
                                :disabled="!!selectedDate"
                                placeholder="Sampai Tanggal"
                                :class="[
                                    'block w-full rounded-lg border py-2 px-3 text-xs focus:ring-1',
                                    selectedDate
                                        ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed dark:bg-gray-800 dark:border-gray-700'
                                        : 'bg-gray-50 border-gray-300 text-gray-900 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white'
                                ]"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exchange Rates Table -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-700/60 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-gray-200 bg-gray-50/75 text-gray-500 dark:border-gray-700/60 dark:bg-gray-700/50 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="py-3.5 px-4 font-semibold">Mata Uang</th>
                                <th scope="col" class="py-3.5 px-4 font-semibold">Tanggal</th>
                                <th scope="col" class="py-3.5 px-4 font-semibold text-center">Satuan / Unit</th>
                                <th scope="col" class="py-3.5 px-4 font-semibold text-right">Kurs Beli (IDR)</th>
                                <th scope="col" class="py-3.5 px-4 font-semibold text-right">Kurs Jual (IDR)</th>
                                <th scope="col" class="py-3.5 px-4 font-semibold text-right bg-emerald-50/60 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400">
                                    Kurs Tengah (Middle Rate)
                                </th>
                                <th scope="col" class="py-3.5 px-4 font-semibold">Sumber</th>
                                <th scope="col" class="py-3.5 px-4 font-semibold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/50 text-gray-700 dark:text-gray-300">
                            <tr
                                v-for="rate in rates.data"
                                :key="rate.id"
                                class="transition-colors hover:bg-gray-50/75 dark:hover:bg-gray-750"
                            >
                                <!-- Mata Uang -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <span class="inline-flex h-8 w-11 items-center justify-center rounded-lg bg-gray-100 px-2 font-mono text-xs font-bold text-gray-800 dark:bg-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600">
                                            {{ rate.currency_code }}
                                        </span>
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                {{ rate.currency?.name || rate.currency_code }}
                                            </p>
                                            <p class="text-[10px] text-gray-400">
                                                1 {{ rate.currency_code }} = {{ formatRupiah(rate.rate_middle / (rate.unit || 1)) }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Tanggal -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ formatDateIndo(rate.date) }}
                                    </div>
                                    <div class="text-[10px] text-gray-400">
                                        {{ rate.date }}
                                    </div>
                                </td>

                                <!-- Satuan / Unit -->
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ Number(rate.unit) }} {{ rate.currency_code }}
                                    </span>
                                </td>

                                <!-- Kurs Beli -->
                                <td class="py-3.5 px-4 text-right font-mono font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                    {{ formatRupiah(rate.rate_buy) }}
                                </td>

                                <!-- Kurs Jual -->
                                <td class="py-3.5 px-4 text-right font-mono font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                    {{ formatRupiah(rate.rate_sell) }}
                                </td>

                                <!-- Kurs Tengah / Middle Rate -->
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/20 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-100/80 px-2 py-0.5 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300">
                                        {{ formatRupiah(rate.rate_middle) }}
                                    </span>
                                </td>

                                <!-- Sumber -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium',
                                            rate.source?.includes('BI')
                                                ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-800'
                                                : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                        ]"
                                    >
                                        <svg v-if="rate.source?.includes('BI')" class="h-3 w-3 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        {{ rate.source }}
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditModal(rate)"
                                            title="Edit Kurs"
                                            class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-indigo-400"
                                        >
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click="openDeleteModal(rate)"
                                            title="Hapus Kurs"
                                            class="rounded-lg p-1.5 text-gray-500 hover:bg-red-50 hover:text-red-600 dark:text-gray-400 dark:hover:bg-red-950/40 dark:hover:text-red-400"
                                        >
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="rates.data.length === 0">
                                <td colspan="8" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-700/60 dark:text-gray-500">
                                            <svg class="h-7 w-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">Tidak ada data kurs yang ditemukan</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-sm">
                                            Tidak ada catatan kurs untuk kriteria tanggal atau pencarian ini. Silakan klik tombol "Sinkronisasi BI" untuk menarik data langsung dari Bank Indonesia.
                                        </p>
                                        <button
                                            type="button"
                                            @click="openSyncModal"
                                            class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700 transition"
                                        >
                                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Sinkronisasi Sekarang
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="rates.total > rates.per_page" class="border-t border-gray-200 bg-white p-4 dark:border-gray-700/60 dark:bg-gray-800">
                    <Pagination :links="rates.links" />
                </div>
            </div>
        </div>

        <!-- ============================================== -->
        <!-- MODAL 1: SINKRONISASI BANK INDONESIA (BI)     -->
        <!-- ============================================== -->
        <Modal :show="showSyncModal" @close="showSyncModal = false" max-width="lg">
            <div class="p-6">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 ring-1 ring-emerald-500/20">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Sinkronisasi Kurs Bank Indonesia
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Mengambil kurs transaksi resmi (beli, jual, tengah) via API getSubKursLokal3 Bank Indonesia.
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submitSync" class="mt-4 space-y-4">
                    <!-- Preset Rentang Cepat -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                            Pilihan Rentang Waktu:
                        </label>
                        <div class="grid grid-cols-4 gap-2">
                            <button
                                type="button"
                                @click="setSyncPreset('today')"
                                class="rounded-lg border border-gray-200 bg-gray-50 py-1.5 text-xs font-medium text-gray-700 hover:bg-emerald-50 hover:border-emerald-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            >
                                Hari Ini
                            </button>
                            <button
                                type="button"
                                @click="setSyncPreset('7days')"
                                class="rounded-lg border border-gray-200 bg-gray-50 py-1.5 text-xs font-medium text-gray-700 hover:bg-emerald-50 hover:border-emerald-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            >
                                7 Hari Lalu
                            </button>
                            <button
                                type="button"
                                @click="setSyncPreset('mtd')"
                                class="rounded-lg border border-gray-200 bg-gray-50 py-1.5 text-xs font-medium text-gray-700 hover:bg-emerald-50 hover:border-emerald-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            >
                                Bulan Ini (MTD)
                            </button>
                            <button
                                type="button"
                                @click="setSyncPreset('30days')"
                                class="rounded-lg border border-gray-200 bg-gray-50 py-1.5 text-xs font-medium text-gray-700 hover:bg-emerald-50 hover:border-emerald-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            >
                                30 Hari
                            </button>
                        </div>
                    </div>

                    <!-- Input Tanggal Awal & Akhir -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel for="sync_start" value="Tanggal Mulai" />
                            <TextInput
                                id="sync_start"
                                type="date"
                                v-model="syncForm.start_date"
                                class="mt-1 block w-full text-xs"
                                required
                            />
                            <InputError :message="syncForm.errors.start_date" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="sync_end" value="Tanggal Selesai" />
                            <TextInput
                                id="sync_end"
                                type="date"
                                v-model="syncForm.end_date"
                                class="mt-1 block w-full text-xs"
                                required
                            />
                            <InputError :message="syncForm.errors.end_date" class="mt-1" />
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="rounded-xl bg-blue-50/70 p-3 text-xs text-blue-800 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60">
                        <div class="flex items-start gap-2">
                            <svg class="h-4 w-4 shrink-0 text-blue-600 dark:text-blue-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <p class="font-semibold">Mata Uang yang Disinkronkan:</p>
                                <p class="text-[11px] text-blue-700 dark:text-blue-400 mt-0.5">
                                    Seluruh {{ currencies.length }} mata uang aktif di database ({{ currencies.map(c => c.code).join(', ') }}) akan disinkronkan langsung dari Bank Indonesia.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="mt-6 flex justify-end gap-3 pt-2">
                        <SecondaryButton type="button" @click="showSyncModal = false" :disabled="syncForm.processing">
                            Batal
                        </SecondaryButton>
                        <button
                            type="submit"
                            :disabled="syncForm.processing"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700 active:scale-95 disabled:opacity-50 transition"
                        >
                            <svg v-if="syncForm.processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ syncForm.processing ? 'Menyinkronkan...' : 'Mulai Sinkronisasi' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- ============================================== -->
        <!-- MODAL 2: TAMBAH KURS MANUAL                   -->
        <!-- ============================================== -->
        <Modal :show="showCreateModal" @close="showCreateModal = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Tambah Kurs Manual</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Masukkan nilai tukar mata uang terhadap Rupiah.</p>
                    </div>
                </div>

                <form @submit.prevent="submitCreate" class="mt-4 space-y-3.5">
                    <!-- Pilih Mata Uang -->
                    <div>
                        <InputLabel for="create_currency" value="Mata Uang *" />
                        <select
                            id="create_currency"
                            v-model="createForm.currency_id"
                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white py-2 px-3 text-xs text-gray-900 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            required
                        >
                            <option value="" disabled>Pilih mata uang</option>
                            <option v-for="c in currencies" :key="c.id" :value="c.id">
                                {{ c.code }} - {{ c.name }}
                            </option>
                        </select>
                        <InputError :message="createForm.errors.currency_id" class="mt-1" />
                    </div>

                    <!-- Tanggal Kurs & Satuan Unit -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel for="create_date" value="Tanggal Kurs *" />
                            <TextInput
                                id="create_date"
                                type="date"
                                v-model="createForm.date"
                                class="mt-1 block w-full text-xs"
                                required
                            />
                            <InputError :message="createForm.errors.date" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="create_unit" value="Satuan Unit *" />
                            <TextInput
                                id="create_unit"
                                type="number"
                                step="any"
                                v-model="createForm.unit"
                                placeholder="1 atau 100"
                                class="mt-1 block w-full text-xs"
                                required
                            />
                            <p class="text-[10px] text-gray-400 mt-0.5">Misal: 100 untuk JPY, 1 untuk USD</p>
                            <InputError :message="createForm.errors.unit" class="mt-1" />
                        </div>
                    </div>

                    <!-- Kurs Beli & Kurs Jual -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel for="create_buy" value="Kurs Beli (IDR) *" />
                            <TextInput
                                id="create_buy"
                                type="number"
                                step="any"
                                v-model="createForm.rate_buy"
                                placeholder="0.00"
                                class="mt-1 block w-full text-xs font-mono"
                                required
                            />
                            <InputError :message="createForm.errors.rate_buy" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="create_sell" value="Kurs Jual (IDR) *" />
                            <TextInput
                                id="create_sell"
                                type="number"
                                step="any"
                                v-model="createForm.rate_sell"
                                placeholder="0.00"
                                class="mt-1 block w-full text-xs font-mono"
                                required
                            />
                            <InputError :message="createForm.errors.rate_sell" class="mt-1" />
                        </div>
                    </div>

                    <!-- Kurs Tengah (Middle Rate Preview / Custom) -->
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 dark:border-emerald-800/60 dark:bg-emerald-950/30">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300">
                                Kurs Tengah (Middle Rate):
                            </span>
                            <span class="font-mono text-sm font-extrabold text-emerald-700 dark:text-emerald-400">
                                {{ formatRupiah(createForm.rate_middle || computedCreateMiddleRate || 0) }}
                            </span>
                        </div>
                        <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5">
                            Otomatis dihitung: (Kurs Beli + Kurs Jual) / 2
                        </p>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-2">
                        <SecondaryButton type="button" @click="showCreateModal = false" :disabled="createForm.processing">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="createForm.processing">
                            Simpan Kurs
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- ============================================== -->
        <!-- MODAL 3: EDIT KURS                            -->
        <!-- ============================================== -->
        <Modal :show="showEditModal" @close="showEditModal = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Edit Kurs {{ rateToEdit?.currency_code }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Tanggal: {{ rateToEdit ? formatDateIndo(rateToEdit.date) : '' }}
                        </p>
                    </div>
                </div>

                <form @submit.prevent="submitEdit" class="mt-4 space-y-3.5">
                    <div>
                        <InputLabel for="edit_unit" value="Satuan Unit *" />
                        <TextInput
                            id="edit_unit"
                            type="number"
                            step="any"
                            v-model="editForm.unit"
                            class="mt-1 block w-full text-xs"
                            required
                        />
                        <InputError :message="editForm.errors.unit" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <InputLabel for="edit_buy" value="Kurs Beli (IDR) *" />
                            <TextInput
                                id="edit_buy"
                                type="number"
                                step="any"
                                v-model="editForm.rate_buy"
                                class="mt-1 block w-full text-xs font-mono"
                                required
                            />
                            <InputError :message="editForm.errors.rate_buy" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="edit_sell" value="Kurs Jual (IDR) *" />
                            <TextInput
                                id="edit_sell"
                                type="number"
                                step="any"
                                v-model="editForm.rate_sell"
                                class="mt-1 block w-full text-xs font-mono"
                                required
                            />
                            <InputError :message="editForm.errors.rate_sell" class="mt-1" />
                        </div>
                    </div>

                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 dark:border-emerald-800/60 dark:bg-emerald-950/30">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300">
                                Kurs Tengah (Middle Rate):
                            </span>
                            <span class="font-mono text-sm font-extrabold text-emerald-700 dark:text-emerald-400">
                                {{ formatRupiah(computedEditMiddleRate || editForm.rate_middle || 0) }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 pt-2">
                        <SecondaryButton type="button" @click="showEditModal = false" :disabled="editForm.processing">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="editForm.processing">
                            Perbarui Kurs
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- ============================================== -->
        <!-- MODAL 4: HAPUS KURS KE TEMPAT SAMPAH          -->
        <!-- ============================================== -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-950/60 dark:text-red-400">
                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Pindahkan Kurs ke Tempat Sampah?
                        </h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Kurs <strong>{{ rateToDelete?.currency_code }}</strong> tanggal <strong>{{ formatDateIndo(rateToDelete?.date) }}</strong> akan dipindahkan ke Recycle Bin dan dapat dipulihkan sewaktu-waktu.
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="showDeleteModal = false" :disabled="isDeleting">
                        Batal
                    </SecondaryButton>
                    <DangerButton type="button" @click="submitDelete" :disabled="isDeleting">
                        {{ isDeleting ? 'Menghapus...' : 'Ya, Pindahkan ke Sampah' }}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
