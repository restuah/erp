<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    logs: Object,
    stats: Object,
    filterOptions: Object,
    filters: Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const canDeleteLogs = computed(() => {
    return (
        currentUser.value?.roles?.includes('Superadmin') ||
        currentUser.value?.permissions?.includes('activity_logs.delete')
    );
});

// Filters state
const search = ref(props.filters.search || '');
const selectedEvent = ref(props.filters.event || '');
const selectedModule = ref(props.filters.module || '');
const selectedCauser = ref(props.filters.causer_id || '');
const selectedRange = ref(props.filters.range || '');

// Detail Modal state
const selectedLog = ref(null);
const showDetailModal = ref(false);
const activeDetailTab = ref('changes'); // 'changes' | 'raw'
const copiedJson = ref(false);
const copiedId = ref(false);

// Clear Logs Modal state
const showClearModal = ref(false);
const clearDays = ref('30');
const isClearing = ref(false);

// Live Debounced Filter Apply
let searchTimeout = null;
const applyFilters = () => {
    router.get(
        route('activity-logs.index'),
        {
            search: search.value || undefined,
            event: selectedEvent.value || undefined,
            module: selectedModule.value || undefined,
            causer_id: selectedCauser.value || undefined,
            range: selectedRange.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
});

watch([selectedEvent, selectedModule, selectedCauser, selectedRange], () => {
    applyFilters();
});

const resetFilters = () => {
    search.value = '';
    selectedEvent.value = '';
    selectedModule.value = '';
    selectedCauser.value = '';
    selectedRange.value = '';
    router.get(route('activity-logs.index'), {}, { replace: true });
};

const hasActiveFilters = computed(() => {
    return (
        !!search.value ||
        !!selectedEvent.value ||
        !!selectedModule.value ||
        !!selectedCauser.value ||
        !!selectedRange.value
    );
});

// Open Log Detail
const openDetail = (log) => {
    selectedLog.value = log;
    activeDetailTab.value = 'changes';
    copiedJson.value = false;
    copiedId.value = false;
    showDetailModal.value = true;
};

// Copy JSON helper
const copyRawJson = async () => {
    if (!selectedLog.value) return;
    try {
        await navigator.clipboard.writeText(
            JSON.stringify(selectedLog.value, null, 2),
        );
        copiedJson.value = true;
        setTimeout(() => {
            copiedJson.value = false;
        }, 2000);
    } catch {
        // clipboard unavailable
    }
};

// Copy ID helper
const copyLogId = async (id) => {
    try {
        await navigator.clipboard.writeText(id);
        copiedId.value = true;
        setTimeout(() => {
            copiedId.value = false;
        }, 2000);
    } catch {
        // clipboard unavailable
    }
};

// Clear Logs Action
const executeClearLogs = () => {
    isClearing.value = true;
    router.delete(route('activity-logs.clear'), {
        data: { days: clearDays.value },
        onSuccess: () => {
            showClearModal.value = false;
            isClearing.value = false;
        },
        onError: () => {
            isClearing.value = false;
        },
    });
};

// Export CSV URL with filters
const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (selectedEvent.value) params.set('event', selectedEvent.value);
    if (selectedModule.value) params.set('module', selectedModule.value);
    if (selectedCauser.value) params.set('causer_id', selectedCauser.value);
    if (selectedRange.value) params.set('range', selectedRange.value);

    const query = params.toString();
    return route('activity-logs.export') + (query ? '?' + query : '');
});

// Format Datetime
const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(date);
};

// Relative Time
const timeAgo = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) return 'Baru saja';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes} menit lalu`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours} jam lalu`;
    const days = Math.floor(hours / 24);
    if (days < 30) return `${days} hari lalu`;
    return '';
};

// Badges Helper
const getEventBadgeClass = (event) => {
    switch (event) {
        case 'created':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800';
        case 'updated':
            return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800';
        case 'deleted':
            return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800';
        case 'force_deleted':
            return 'bg-red-100 text-red-800 border-red-300 dark:bg-red-950 dark:text-red-300 dark:border-red-800 font-semibold';
        case 'restored':
        case 'bulk_restore':
            return 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/60 dark:text-teal-300 dark:border-teal-800';
        case 'login':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border-indigo-800';
        case 'logout':
            return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800';
        case 'failed_login':
            return 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/60 dark:text-red-300 dark:border-red-800 font-semibold';
        case 'password_reset':
            return 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800';
        default:
            return 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700';
    }
};

const getMethodBadgeClass = (method) => {
    switch (method?.toUpperCase()) {
        case 'GET':
            return 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300';
        case 'POST':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300';
        case 'PUT':
        case 'PATCH':
            return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300';
        case 'DELETE':
            return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300';
        default:
            return 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300';
    }
};

const getEventLabel = (event) => {
    return props.filterOptions.events[event] || event;
};

// Diff Computed for Updated Events
const diffEntries = computed(() => {
    if (!selectedLog.value?.properties) return [];
    const propsData = selectedLog.value.properties;

    if (propsData.old && propsData.attributes) {
        const keys = Object.keys(propsData.attributes);
        return keys.map((key) => ({
            field: key,
            old: propsData.old[key],
            new: propsData.attributes[key],
        }));
    }

    if (propsData.attributes) {
        return Object.entries(propsData.attributes).map(([key, val]) => ({
            field: key,
            old: null,
            new: val,
        }));
    }

    return [];
});

const formatPropertyValue = (val) => {
    if (val === null || val === undefined) return '(kosong)';
    if (typeof val === 'boolean') return val ? 'true' : 'false';
    if (typeof val === 'object') return JSON.stringify(val, null, 2);
    if (val === '') return '(string kosong)';
    return String(val);
};
</script>

<template>
    <Head title="Log Aktivitas Sistem" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1
                        class="text-xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-2xl"
                    >
                        Log Aktivitas Sistem (Audit Trail)
                    </h1>
                    <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                        Catatan audit menyeluruh setiap peristiwa, perubahan
                        data, otentikasi pengguna, dan proses sistem ERP.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Export CSV Button -->
                    <a
                        :href="exportUrl"
                        class="shadow-xs inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        title="Unduh data log ke berkas CSV sesuai filter"
                    >
                        <svg
                            class="h-4 w-4 text-gray-500 dark:text-gray-400"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            />
                        </svg>
                        Ekspor CSV
                    </a>

                    <!-- Clear Logs Button (Admin only) -->
                    <button
                        v-if="canDeleteLogs"
                        type="button"
                        @click="showClearModal = true"
                        class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300 dark:hover:bg-rose-900/60"
                        title="Bersihkan riwayat log yang sudah lama"
                    >
                        <svg
                            class="h-4 w-4 text-rose-600 dark:text-rose-400"
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
                        Bersihkan Log
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- STATS CARDS -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Card 1: Total Log -->
                <div
                    class="shadow-xs rounded-xl border border-gray-200 bg-white p-6 transition hover:shadow-md dark:border-gray-700/60 dark:bg-gray-800"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                        >
                            Total Aktivitas
                        </span>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
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
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div
                        class="mt-3 text-3xl font-black tracking-tight text-gray-900 dark:text-white"
                    >
                        {{ stats.total.toLocaleString('id-ID') }}
                    </div>
                    <p
                        class="mt-2 text-xs leading-relaxed text-gray-500 dark:text-gray-400"
                    >
                        Seluruh rekaman audit trail sistem ERP
                    </p>
                </div>

                <!-- Card 2: Hari Ini -->
                <div
                    class="shadow-xs rounded-xl border border-gray-200 bg-white p-6 transition hover:shadow-md dark:border-gray-700/60 dark:bg-gray-800"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                        >
                            Hari Ini
                        </span>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
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
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div
                        class="mt-3 text-3xl font-black tracking-tight text-emerald-600 dark:text-emerald-400"
                    >
                        {{ stats.today.toLocaleString('id-ID') }}
                    </div>
                    <p
                        class="mt-2 text-xs leading-relaxed text-gray-500 dark:text-gray-400"
                    >
                        Aktivitas tercatat sejak 00:00 WIB
                    </p>
                </div>

                <!-- Card 3: Tindakan Kritis -->
                <div
                    class="shadow-xs rounded-xl border border-gray-200 bg-white p-6 transition hover:shadow-md dark:border-gray-700/60 dark:bg-gray-800"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                        >
                            Tindakan Kritis
                        </span>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400"
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
                    </div>
                    <div
                        class="mt-3 text-3xl font-black tracking-tight text-rose-600 dark:text-rose-400"
                    >
                        {{ stats.critical.toLocaleString('id-ID') }}
                    </div>
                    <p
                        class="mt-2 text-xs leading-relaxed text-gray-500 dark:text-gray-400"
                    >
                        Penghapusan data & pembersihan sampah
                    </p>
                </div>

                <!-- Card 4: Teraktif Hari Ini -->
                <div
                    class="shadow-xs rounded-xl border border-gray-200 bg-white p-6 transition hover:shadow-md dark:border-gray-700/60 dark:bg-gray-800"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                        >
                            Teraktif Hari Ini
                        </span>
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400"
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
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div
                        v-if="stats.top_user"
                        class="mt-3 truncate text-lg font-bold text-gray-900 dark:text-white"
                        :title="stats.top_user.name"
                    >
                        {{ stats.top_user.name }}
                    </div>
                    <div
                        v-else
                        class="mt-3 text-base font-semibold text-gray-400 dark:text-gray-500"
                    >
                        Belum ada aktivitas
                    </div>
                    <p
                        v-if="stats.top_user"
                        class="mt-1.5 text-xs font-semibold text-purple-600 dark:text-purple-400"
                    >
                        {{ stats.top_user.count }} aksi dilakukan hari ini
                    </p>
                    <p
                        v-else
                        class="mt-2 text-xs leading-relaxed text-gray-500 dark:text-gray-400"
                    >
                        Menunggu aktivitas pengguna
                    </p>
                </div>
            </div>

            <!-- FILTERS TOOLBAR -->
            <div
                class="shadow-xs rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700/60 dark:bg-gray-800"
            >
                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5"
                >
                    <!-- Search Input -->
                    <div class="relative lg:col-span-2">
                        <div
                            class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5"
                        >
                            <svg
                                class="h-4.5 w-4.5 text-gray-400"
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
                            placeholder="Cari deskripsi, nama pengguna, email, atau IP..."
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50/70 py-2.5 pe-4 ps-11 text-sm text-gray-900 transition focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:placeholder-gray-400 dark:focus:border-indigo-400"
                        />
                    </div>

                    <!-- Filter Modul -->
                    <div>
                        <select
                            v-model="selectedModule"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50/70 px-3.5 py-2.5 text-sm text-gray-900 transition focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-indigo-400"
                        >
                            <option value="">Semua Modul</option>
                            <option
                                v-for="mod in filterOptions.modules"
                                :key="mod"
                                :value="mod"
                            >
                                Modul: {{ mod.toUpperCase() }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Peristiwa / Event -->
                    <div>
                        <select
                            v-model="selectedEvent"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50/70 px-3.5 py-2.5 text-sm text-gray-900 transition focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-indigo-400"
                        >
                            <option value="">Semua Peristiwa</option>
                            <option
                                v-for="(label, evt) in filterOptions.events"
                                :key="evt"
                                :value="evt"
                            >
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Rentang Waktu -->
                    <div>
                        <select
                            v-model="selectedRange"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50/70 px-3.5 py-2.5 text-sm text-gray-900 transition focus:border-indigo-500 focus:bg-white focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-indigo-400"
                        >
                            <option value="">Semua Waktu</option>
                            <option value="today">Hari Ini</option>
                            <option value="yesterday">Kemarin</option>
                            <option value="7_days">7 Hari Terakhir</option>
                            <option value="30_days">30 Hari Terakhir</option>
                            <option value="this_month">Bulan Ini</option>
                        </select>
                    </div>
                </div>

                <!-- Active filters reset indicator -->
                <div
                    v-if="hasActiveFilters"
                    class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3.5 text-sm dark:border-gray-700/60"
                >
                    <div
                        class="flex items-center gap-2.5 text-gray-500 dark:text-gray-400"
                    >
                        <span
                            class="inline-block h-2 w-2 animate-pulse rounded-full bg-indigo-500"
                        ></span>
                        Filter diterapkan. Menampilkan hasil terfilter.
                    </div>
                    <button
                        type="button"
                        @click="resetFilters"
                        class="font-semibold text-indigo-600 transition hover:text-indigo-700 hover:underline dark:text-indigo-400"
                    >
                        Reset Semua Filter
                    </button>
                </div>
            </div>

            <!-- ACTIVITY LOG TABLE -->
            <div
                class="shadow-xs overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700/60 dark:bg-gray-800"
            >
                <div class="overflow-x-auto">
                    <table
                        class="w-full text-start text-sm text-gray-600 dark:text-gray-300"
                    >
                        <thead
                            class="border-b border-gray-200 bg-gray-50/90 text-xs font-bold uppercase tracking-wider text-gray-500 dark:border-gray-700/60 dark:bg-gray-900/50 dark:text-gray-400"
                        >
                            <tr>
                                <th class="px-6 py-4 text-start">Waktu</th>
                                <th class="px-6 py-4 text-start">
                                    Pengguna (Pelaksana)
                                </th>
                                <th class="px-6 py-4 text-start">Peristiwa</th>
                                <th class="px-6 py-4 text-start">Modul</th>
                                <th class="px-6 py-4 text-start">
                                    Deskripsi Aktivitas
                                </th>
                                <th class="px-6 py-4 text-start">
                                    Perangkat & IP
                                </th>
                                <th class="px-6 py-4 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700/60"
                        >
                            <tr
                                v-for="log in logs.data"
                                :key="log.id"
                                class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-700/30"
                            >
                                <!-- Timestamp -->
                                <td
                                    class="whitespace-nowrap px-6 py-4 align-top"
                                >
                                    <div
                                        class="font-semibold text-gray-900 dark:text-white"
                                    >
                                        {{ formatDateTime(log.created_at) }}
                                    </div>
                                    <div
                                        class="mt-1 text-xs text-gray-400 dark:text-gray-500"
                                    >
                                        {{ timeAgo(log.created_at) }}
                                    </div>
                                </td>

                                <!-- User / Causer -->
                                <td class="px-6 py-4 align-top">
                                    <div class="flex items-center gap-3.5">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-indigo-50 font-bold text-indigo-700 dark:border-gray-700 dark:bg-indigo-950/70 dark:text-indigo-300"
                                        >
                                            {{
                                                (log.causer_name || 'S')
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </div>
                                        <div class="min-w-0">
                                            <div
                                                class="flex items-center gap-2 font-bold text-gray-900 dark:text-white"
                                            >
                                                <span class="truncate">{{
                                                    log.causer_name || 'Sistem'
                                                }}</span>
                                                <span
                                                    v-if="
                                                        log.causer_id &&
                                                        log.causer_id ===
                                                            currentUser?.id
                                                    "
                                                    class="rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"
                                                >
                                                    Anda
                                                </span>
                                            </div>
                                            <div
                                                class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                {{
                                                    log.causer_email ||
                                                    log.causer_role ||
                                                    'Proses Sistem'
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Event Badge -->
                                <td
                                    class="whitespace-nowrap px-6 py-4 align-top"
                                >
                                    <span
                                        :class="[
                                            'inline-flex items-center rounded-lg border px-3 py-1 text-xs font-semibold capitalize tracking-wide',
                                            getEventBadgeClass(log.event),
                                        ]"
                                    >
                                        {{ getEventLabel(log.event) }}
                                    </span>
                                </td>

                                <!-- Module -->
                                <td
                                    class="whitespace-nowrap px-6 py-4 align-top"
                                >
                                    <span
                                        class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        {{ log.log_name }}
                                    </span>
                                </td>

                                <!-- Description -->
                                <td
                                    class="min-w-[240px] max-w-md px-6 py-4 align-top"
                                >
                                    <div
                                        class="font-medium leading-relaxed text-gray-900 dark:text-gray-100"
                                    >
                                        {{ log.description }}
                                    </div>
                                    <div
                                        v-if="log.subject_label"
                                        class="mt-1.5 inline-flex items-center gap-1 rounded-md bg-indigo-50/80 px-2.5 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300"
                                    >
                                        <span class="font-bold">Target:</span>
                                        <span class="truncate">{{
                                            log.subject_label
                                        }}</span>
                                    </div>
                                </td>

                                <!-- Device & IP -->
                                <td
                                    class="whitespace-nowrap px-6 py-4 align-top"
                                >
                                    <div
                                        class="font-mono text-xs font-semibold tracking-wide text-gray-800 dark:text-gray-200"
                                    >
                                        {{ log.ip_address || '-' }}
                                    </div>
                                    <div
                                        class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                        :title="log.user_agent"
                                    >
                                        {{ log.device || '-' }}
                                    </div>
                                </td>

                                <!-- Action Button -->
                                <td
                                    class="whitespace-nowrap px-6 py-4 text-end align-top"
                                >
                                    <button
                                        type="button"
                                        @click="openDetail(log)"
                                        class="shadow-xs inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 transition hover:border-indigo-300 hover:bg-indigo-50/50 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-indigo-600 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-400"
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
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                        Detail
                                    </button>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="logs.data.length === 0">
                                <td
                                    colspan="7"
                                    class="py-16 text-center text-gray-500 dark:text-gray-400"
                                >
                                    <div
                                        class="flex flex-col items-center justify-center"
                                    >
                                        <div
                                            class="flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800"
                                        >
                                            <svg
                                                class="h-8 w-8 text-gray-400 dark:text-gray-500"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.5"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                />
                                            </svg>
                                        </div>
                                        <div
                                            class="mt-4 text-base font-bold text-gray-900 dark:text-white"
                                        >
                                            Tidak ada riwayat log ditemukan
                                        </div>
                                        <p
                                            class="mt-1.5 max-w-sm text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            Tidak ada catatan aktivitas yang
                                            sesuai dengan kriteria filter yang
                                            Anda pilih saat ini.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="logs.data.length > 0"
                    class="border-t border-gray-100 p-5 dark:border-gray-700/60"
                >
                    <Pagination :links="logs.links" />
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL: DETAIL AKTIVITAS (REFINED & NEAT)  -->
        <!-- ========================================== -->
        <Modal
            :show="showDetailModal"
            @close="showDetailModal = false"
            max-width="4xl"
        >
            <div v-if="selectedLog" class="flex flex-col">
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between border-b border-gray-200/80 bg-white px-6 py-4 dark:border-gray-700/80 dark:bg-gray-800 sm:px-7 sm:py-4"
                >
                    <!-- Left: Icon & Title & Timestamp -->
                    <div class="flex min-w-0 items-center gap-4">
                        <div
                            class="shadow-2xs flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border transition-colors"
                            :class="[
                                selectedLog.status === 'danger'
                                    ? 'border-rose-200 bg-rose-50 text-rose-600 dark:border-rose-900/60 dark:bg-rose-950/50 dark:text-rose-400'
                                    : selectedLog.status === 'warning'
                                      ? 'border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-900/60 dark:bg-amber-950/50 dark:text-amber-400'
                                      : 'border-indigo-100 bg-indigo-50 text-indigo-600 dark:border-indigo-900/60 dark:bg-indigo-950/50 dark:text-indigo-400',
                            ]"
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
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <h3
                                class="text-base font-bold tracking-tight text-gray-900 dark:text-white sm:text-lg"
                            >
                                Detail Riwayat Aktivitas
                            </h3>
                            <div
                                class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400"
                            >
                                <span>{{
                                    formatDateTime(selectedLog.created_at)
                                }}</span>
                                <span class="text-gray-300 dark:text-gray-600"
                                    >•</span
                                >
                                <span
                                    class="font-medium text-gray-600 dark:text-gray-300"
                                >
                                    {{ timeAgo(selectedLog.created_at) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Badges & Close Button -->
                    <div class="flex shrink-0 items-center gap-2.5 sm:gap-3">
                        <span
                            :class="[
                                'inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold uppercase tracking-wider',
                                getEventBadgeClass(selectedLog.event),
                            ]"
                        >
                            {{ getEventLabel(selectedLog.event) }}
                        </span>

                        <span
                            class="hidden items-center rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:border-gray-700 dark:bg-gray-700/60 dark:text-gray-300 sm:inline-flex"
                        >
                            {{ selectedLog.log_name }}
                        </span>

                        <div
                            class="hidden h-5 w-px bg-gray-200 dark:bg-gray-700 sm:block"
                        ></div>

                        <button
                            type="button"
                            @click="showDetailModal = false"
                            class="focus:outline-hidden rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:ring-2 focus:ring-indigo-500 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                            title="Tutup (Esc)"
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
                </div>

                <!-- Modal Body -->
                <div
                    class="max-h-[75vh] space-y-5 overflow-y-auto bg-slate-50/60 p-6 dark:bg-gray-900/50"
                >
                    <!-- SECTION 1: Deskripsi Proses -->
                    <div
                        class="shadow-2xs rounded-xl border border-gray-200/80 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div
                            class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                        >
                            Deskripsi Proses
                        </div>
                        <div
                            class="mt-1.5 text-base font-semibold leading-relaxed text-gray-900 dark:text-white"
                        >
                            {{ selectedLog.description }}
                        </div>
                        <div
                            v-if="selectedLog.subject_label"
                            class="mt-3 flex flex-wrap items-center gap-2"
                        >
                            <span
                                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300"
                            >
                                <span class="font-bold">Target:</span>
                                <span>{{ selectedLog.subject_label }}</span>
                            </span>
                            <span
                                v-if="selectedLog.subject_type"
                                class="font-mono text-[11px] text-gray-400 dark:text-gray-500"
                            >
                                ({{ selectedLog.subject_type }})
                            </span>
                        </div>
                    </div>

                    <!-- SECTION 2: Grid 2 Kolom (Pelaksana & Rincian Teknis) -->
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <!-- Kolom Kiri: Pelaksana (Causer) -->
                        <div
                            class="shadow-2xs rounded-xl border border-gray-200/80 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                        >
                            <div
                                class="flex items-center gap-2 border-b border-gray-100 pb-3 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:border-gray-700/60 dark:text-gray-500"
                            >
                                <svg
                                    class="h-4 w-4 text-indigo-500"
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
                                Pengguna Pelaksana (Causer)
                            </div>

                            <div class="mt-3.5 flex items-center gap-3.5">
                                <div
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-base font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                                >
                                    {{
                                        (selectedLog.causer_name || 'S')
                                            .charAt(0)
                                            .toUpperCase()
                                    }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div
                                        class="truncate text-base font-bold text-gray-900 dark:text-white"
                                    >
                                        {{
                                            selectedLog.causer_name || 'Sistem'
                                        }}
                                    </div>
                                    <div
                                        class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        {{ selectedLog.causer_email || '-' }}
                                    </div>
                                    <div class="mt-2 flex items-center gap-2">
                                        <span
                                            class="inline-block rounded-md bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                        >
                                            {{
                                                selectedLog.causer_role ||
                                                'Pengguna'
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="selectedLog.causer_id"
                                class="mt-4 border-t border-gray-50 pt-2.5 text-[11px] text-gray-400 dark:border-gray-700/50 dark:text-gray-500"
                            >
                                <span class="font-mono"
                                    >ID: {{ selectedLog.causer_id }}</span
                                >
                            </div>
                        </div>

                        <!-- Kolom Kanan: Detail Jaringan & Klien -->
                        <div
                            class="shadow-2xs rounded-xl border border-gray-200/80 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                        >
                            <div
                                class="flex items-center gap-2 border-b border-gray-100 pb-3 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:border-gray-700/60 dark:text-gray-500"
                            >
                                <svg
                                    class="h-4 w-4 text-emerald-500"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"
                                    />
                                </svg>
                                Jaringan & Perangkat Klien
                            </div>

                            <div class="mt-3.5 space-y-2.5 text-xs">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                        >Alamat IP:</span
                                    >
                                    <span
                                        class="rounded-md bg-gray-100 px-2 py-0.5 font-mono font-bold text-gray-800 dark:bg-gray-700 dark:text-gray-200"
                                    >
                                        {{ selectedLog.ip_address || '-' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-gray-500 dark:text-gray-400"
                                        >Perangkat:</span
                                    >
                                    <span
                                        class="font-medium text-gray-800 dark:text-gray-200"
                                    >
                                        {{ selectedLog.device || '-' }}
                                    </span>
                                </div>
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <span
                                        class="shrink-0 text-gray-500 dark:text-gray-400"
                                        >Metode & URL:</span
                                    >
                                    <div
                                        class="flex max-w-[240px] items-center gap-1.5 overflow-hidden truncate"
                                    >
                                        <span
                                            v-if="selectedLog.method"
                                            :class="[
                                                'rounded px-1.5 py-0.5 text-[10px] font-bold uppercase',
                                                getMethodBadgeClass(
                                                    selectedLog.method,
                                                ),
                                            ]"
                                        >
                                            {{ selectedLog.method }}
                                        </span>
                                        <span
                                            class="truncate font-mono text-[11px] text-gray-700 dark:text-gray-300"
                                            :title="selectedLog.url"
                                        >
                                            {{ selectedLog.url || '-' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: Inspeksi Perubahan & Data (Tabs) -->
                    <div
                        class="shadow-2xs overflow-hidden rounded-xl border border-gray-200/80 bg-white dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-100 px-5 pt-3 dark:border-gray-700"
                        >
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    @click="activeDetailTab = 'changes'"
                                    :class="[
                                        'border-b-2 px-4 py-2.5 text-xs font-bold transition-all',
                                        activeDetailTab === 'changes'
                                            ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                                    ]"
                                >
                                    Perubahan Nilai Data (Diff)
                                    <span
                                        v-if="diffEntries.length > 0"
                                        class="ms-1.5 rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                                    >
                                        {{ diffEntries.length }}
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    @click="activeDetailTab = 'raw'"
                                    :class="[
                                        'border-b-2 px-4 py-2.5 text-xs font-bold transition-all',
                                        activeDetailTab === 'raw'
                                            ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                                    ]"
                                >
                                    Metadata Mentah (JSON)
                                </button>
                            </div>
                        </div>

                        <!-- Content: Diff Tab -->
                        <div v-if="activeDetailTab === 'changes'" class="p-5">
                            <!-- Jika Event Updated (Perubahan Before vs After) -->
                            <div
                                v-if="
                                    selectedLog.event === 'updated' &&
                                    diffEntries.length > 0
                                "
                                class="space-y-3"
                            >
                                <div
                                    class="grid grid-cols-12 gap-3 rounded-lg bg-gray-50 px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:bg-gray-900/60 dark:text-gray-400"
                                >
                                    <div class="col-span-3">
                                        Kolom / Atribut
                                    </div>
                                    <div
                                        class="col-span-4 text-rose-600 dark:text-rose-400"
                                    >
                                        Nilai Sebelum (Old)
                                    </div>
                                    <div
                                        class="col-span-5 text-emerald-600 dark:text-emerald-400"
                                    >
                                        Nilai Sesudah (New)
                                    </div>
                                </div>

                                <div
                                    v-for="diff in diffEntries"
                                    :key="diff.field"
                                    class="dark:hover:bg-gray-750 grid grid-cols-12 gap-3 rounded-lg border border-gray-100 p-3 transition hover:bg-gray-50/50 dark:border-gray-700/60"
                                >
                                    <div
                                        class="col-span-3 flex items-center text-xs font-semibold text-gray-800 dark:text-gray-200"
                                    >
                                        <span
                                            class="rounded bg-gray-100 px-2 py-1 font-mono text-[11px] text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                        >
                                            {{ diff.field }}
                                        </span>
                                    </div>
                                    <div
                                        class="col-span-4 rounded-md border border-rose-200/70 bg-rose-50/60 p-2.5 font-mono text-xs text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300"
                                    >
                                        <span
                                            class="block break-all line-through"
                                        >
                                            {{ formatPropertyValue(diff.old) }}
                                        </span>
                                    </div>
                                    <div
                                        class="col-span-5 rounded-md border border-emerald-200/70 bg-emerald-50/60 p-2.5 font-mono text-xs font-bold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300"
                                    >
                                        <span class="block break-all">
                                            {{ formatPropertyValue(diff.new) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Jika Event Created / Lainnya dengan Attributes -->
                            <div
                                v-else-if="diffEntries.length > 0"
                                class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                            >
                                <table class="w-full text-start text-xs">
                                    <thead
                                        class="border-b border-gray-200 bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-400"
                                    >
                                        <tr>
                                            <th
                                                class="w-1/3 px-4 py-3 text-start"
                                            >
                                                Kolom / Atribut
                                            </th>
                                            <th
                                                class="w-2/3 px-4 py-3 text-start"
                                            >
                                                Nilai Tercatat
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="divide-y divide-gray-100 font-mono text-xs dark:divide-gray-700"
                                    >
                                        <tr
                                            v-for="diff in diffEntries"
                                            :key="diff.field"
                                            class="transition hover:bg-gray-50 dark:hover:bg-gray-700/30"
                                        >
                                            <td
                                                class="px-4 py-3 font-sans font-bold text-gray-800 dark:text-gray-200"
                                            >
                                                <span
                                                    class="rounded bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                                >
                                                    {{ diff.field }}
                                                </span>
                                            </td>
                                            <td
                                                class="break-all px-4 py-3 font-semibold text-gray-900 dark:text-white"
                                            >
                                                {{
                                                    formatPropertyValue(
                                                        diff.new,
                                                    )
                                                }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Empty State Attributes -->
                            <div
                                v-else
                                class="rounded-lg border border-dashed border-gray-200 p-8 text-center text-xs text-gray-400 dark:border-gray-700 dark:text-gray-500"
                            >
                                Tidak ada rincian atribut data spesifik yang
                                diubah pada proses ini.
                            </div>
                        </div>

                        <!-- Content: Raw JSON Tab -->
                        <div
                            v-if="activeDetailTab === 'raw'"
                            class="space-y-3 p-5"
                        >
                            <div class="flex justify-end">
                                <button
                                    type="button"
                                    @click="copyRawJson"
                                    class="shadow-2xs inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                >
                                    <svg
                                        v-if="!copiedJson"
                                        class="h-3.5 w-3.5 text-gray-500"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"
                                        />
                                    </svg>
                                    <svg
                                        v-else
                                        class="h-3.5 w-3.5 text-emerald-500"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                    {{
                                        copiedJson
                                            ? 'Tersalin ke Clipboard!'
                                            : 'Salin Data JSON'
                                    }}
                                </button>
                            </div>
                            <pre
                                class="max-h-72 overflow-x-auto rounded-xl bg-gray-900 p-4 font-mono text-xs leading-relaxed text-emerald-400 shadow-inner"
                            ><code>{{ JSON.stringify(selectedLog, null, 2) }}</code></pre>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="flex items-center justify-between border-t border-gray-200/80 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div
                        class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500"
                    >
                        <span
                            class="max-w-[260px] truncate font-mono text-[11px]"
                            :title="selectedLog.id"
                        >
                            ID: {{ selectedLog.id }}
                        </span>
                        <button
                            type="button"
                            @click="copyLogId(selectedLog.id)"
                            class="transition hover:text-indigo-600"
                            title="Salin ID Log"
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
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                />
                            </svg>
                        </button>
                        <span
                            v-if="copiedId"
                            class="text-[10px] font-bold text-emerald-600"
                            >Tersalin!</span
                        >
                    </div>

                    <SecondaryButton
                        @click="showDetailModal = false"
                        class="px-4 py-2 text-xs font-semibold"
                    >
                        Tutup
                    </SecondaryButton>
                </div>
            </div>
        </Modal>

        <!-- ========================================== -->
        <!-- MODAL: BERSIHKAN LOG (REFINED & NEAT)     -->
        <!-- ========================================== -->
        <Modal
            :show="showClearModal"
            @close="showClearModal = false"
            max-width="md"
        >
            <div class="flex flex-col">
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between border-b border-gray-200/80 bg-white px-6 py-4 dark:border-gray-700/80 dark:bg-gray-800"
                >
                    <div class="flex min-w-0 items-center gap-3.5">
                        <div
                            class="shadow-2xs flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 dark:border-rose-900/60 dark:bg-rose-950/50 dark:text-rose-400"
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
                        <div class="min-w-0">
                            <h3
                                class="text-base font-bold text-gray-900 dark:text-white"
                            >
                                Bersihkan Riwayat Log
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Pembersihan arsip audit trail sistem
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="showClearModal = false"
                        class="focus:outline-hidden rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:ring-2 focus:ring-rose-500 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                        title="Tutup (Esc)"
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

                <!-- Modal Body -->
                <div class="p-6">
                    <p
                        class="text-xs leading-relaxed text-gray-600 dark:text-gray-300"
                    >
                        Pilih batas waktu penyimpanan riwayat log yang ingin
                        dihapus. Tindakan ini permanen dan tidak dapat
                        dibatalkan.
                    </p>

                    <div class="mt-4 space-y-2.5 text-xs">
                        <label
                            class="flex cursor-pointer items-center justify-between rounded-xl border p-3 transition-all"
                            :class="
                                clearDays === '30'
                                    ? 'border-indigo-500 bg-indigo-50/40 dark:bg-indigo-950/30'
                                    : 'dark:hover:bg-gray-750 border-gray-200 hover:bg-gray-50 dark:border-gray-700'
                            "
                        >
                            <div class="flex items-center gap-3">
                                <input
                                    type="radio"
                                    v-model="clearDays"
                                    value="30"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span
                                    class="font-medium text-gray-800 dark:text-gray-200"
                                >
                                    Lebih lama dari 30 hari (Disarankan)
                                </span>
                            </div>
                        </label>

                        <label
                            class="flex cursor-pointer items-center justify-between rounded-xl border p-3 transition-all"
                            :class="
                                clearDays === '60'
                                    ? 'border-indigo-500 bg-indigo-50/40 dark:bg-indigo-950/30'
                                    : 'dark:hover:bg-gray-750 border-gray-200 hover:bg-gray-50 dark:border-gray-700'
                            "
                        >
                            <div class="flex items-center gap-3">
                                <input
                                    type="radio"
                                    v-model="clearDays"
                                    value="60"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span
                                    class="font-medium text-gray-800 dark:text-gray-200"
                                >
                                    Lebih lama dari 60 hari
                                </span>
                            </div>
                        </label>

                        <label
                            class="flex cursor-pointer items-center justify-between rounded-xl border p-3 transition-all"
                            :class="
                                clearDays === '90'
                                    ? 'border-indigo-500 bg-indigo-50/40 dark:bg-indigo-950/30'
                                    : 'dark:hover:bg-gray-750 border-gray-200 hover:bg-gray-50 dark:border-gray-700'
                            "
                        >
                            <div class="flex items-center gap-3">
                                <input
                                    type="radio"
                                    v-model="clearDays"
                                    value="90"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span
                                    class="font-medium text-gray-800 dark:text-gray-200"
                                >
                                    Lebih lama dari 90 hari
                                </span>
                            </div>
                        </label>

                        <label
                            class="flex cursor-pointer items-center justify-between rounded-xl border p-3 transition-all"
                            :class="
                                clearDays === '0'
                                    ? 'border-rose-500 bg-rose-50 dark:bg-rose-950/40'
                                    : 'border-rose-200 bg-rose-50/40 hover:bg-rose-50 dark:border-rose-900/50 dark:bg-rose-950/20'
                            "
                        >
                            <div class="flex items-center gap-3">
                                <input
                                    type="radio"
                                    v-model="clearDays"
                                    value="0"
                                    class="h-4 w-4 text-rose-600 focus:ring-rose-500"
                                />
                                <span
                                    class="font-bold text-rose-700 dark:text-rose-300"
                                >
                                    Bersihkan Seluruh Riwayat Log (0 Hari)
                                </span>
                            </div>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end gap-2.5">
                        <SecondaryButton
                            @click="showClearModal = false"
                            :disabled="isClearing"
                            class="px-4 py-2 text-xs"
                        >
                            Batal
                        </SecondaryButton>
                        <DangerButton
                            @click="executeClearLogs"
                            :disabled="isClearing"
                            class="px-4 py-2 text-xs"
                        >
                            {{
                                isClearing
                                    ? 'Membersihkan...'
                                    : 'Konfirmasi Bersihkan'
                            }}
                        </DangerButton>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
