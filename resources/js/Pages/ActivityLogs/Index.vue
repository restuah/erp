<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import Button from '@/Components/Atoms/Button.vue';
import Modal from '@/Components/Atoms/Modal.vue';
import Pagination from '@/Components/Molecules/Pagination.vue';
import StatsCard from '@/Components/Molecules/StatsCard.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import AuthenticatedTemplate from '@/Components/Templates/AuthenticatedTemplate.vue';
import ActivityLogDetailModal from './Partials/ActivityLogDetailModal.vue';

const props = defineProps({
    logs: Object,
    stats: Object,
    filterOptions: Object,
    filters: Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
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
    showDetailModal.value = true;
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
            return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800';
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

const getEventLabel = (event) => {
    return props.filterOptions?.events?.[event] || event;
};
</script>

<template>
    <Head title="Log Aktivitas Sistem" />

    <AuthenticatedTemplate>
        <template #header>
            <PageHeader
                title="Log Aktivitas Sistem (Audit Trail)"
                subtitle="Catatan audit menyeluruh setiap peristiwa, perubahan data, otentikasi pengguna, dan proses sistem ERP."
                :breadcrumbs="[
                    { label: 'Dashboard', href: route('dashboard') },
                    { label: 'Log Aktivitas' },
                ]"
            >
                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Export CSV Button -->
                        <a
                            :href="exportUrl"
                            class="shadow-xs inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
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
                            <span>Ekspor CSV</span>
                        </a>

                        <!-- Clear Logs Button (Admin only) -->
                        <Button
                            v-if="canDeleteLogs"
                            variant="danger"
                            size="sm"
                            @click="showClearModal = true"
                            title="Bersihkan riwayat log yang sudah lama"
                        >
                            <template #icon-left>
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
                            </template>
                            <span>Bersihkan Log</span>
                        </Button>
                    </div>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-6">
            <!-- STATS CARDS -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatsCard
                    title="Total Aktivitas"
                    :value="stats.total.toLocaleString('id-ID')"
                    variant="primary"
                    description="Seluruh rekaman audit trail sistem ERP"
                />
                <StatsCard
                    title="Hari Ini"
                    :value="stats.today.toLocaleString('id-ID')"
                    variant="success"
                    description="Aktivitas tercatat sejak 00:00 WIB"
                />
                <StatsCard
                    title="Tindakan Kritis"
                    :value="stats.critical.toLocaleString('id-ID')"
                    variant="danger"
                    description="Penghapusan data & pembersihan sampah"
                />
                <StatsCard
                    title="Teraktif Hari Ini"
                    :value="stats.top_user?.name || 'Belum ada'"
                    variant="purple"
                    :description="
                        stats.top_user
                            ? `${stats.top_user.count} aksi dilakukan hari ini`
                            : 'Menunggu aktivitas pengguna'
                    "
                />
            </div>

            <!-- SEARCH & ADVANCED FILTERS -->
            <div
                class="shadow-xs rounded-xl border border-gray-200 bg-white p-4 transition-all dark:border-gray-700/60 dark:bg-gray-800"
            >
                <div
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5"
                >
                    <!-- Search Input -->
                    <div class="relative sm:col-span-2 lg:col-span-2">
                        <div
                            class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5"
                        >
                            <svg
                                class="h-4 w-4 text-gray-400 dark:text-gray-500"
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
                            placeholder="Cari deskripsi, subject, IP..."
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50/70 py-2 pe-4 ps-10 text-xs text-gray-900 transition focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:placeholder-gray-400 dark:focus:border-blue-400"
                        />
                    </div>

                    <!-- Filter Peristiwa (Event) -->
                    <div>
                        <select
                            v-model="selectedEvent"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50/70 px-3 py-2 text-xs text-gray-900 transition focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-blue-400"
                        >
                            <option value="">Semua Peristiwa</option>
                            <option
                                v-for="(label, key) in filterOptions.events"
                                :key="key"
                                :value="key"
                            >
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Modul (Log Name) -->
                    <div>
                        <select
                            v-model="selectedModule"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50/70 px-3 py-2 text-xs text-gray-900 transition focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-blue-400"
                        >
                            <option value="">Semua Modul</option>
                            <option
                                v-for="mod in filterOptions.modules"
                                :key="mod"
                                :value="mod"
                            >
                                {{ mod }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter Rentang Waktu (Range) -->
                    <div>
                        <select
                            v-model="selectedRange"
                            class="block w-full rounded-lg border border-gray-300 bg-gray-50/70 px-3 py-2 text-xs text-gray-900 transition focus:border-blue-500 focus:bg-white focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700/50 dark:text-white dark:focus:border-blue-400"
                        >
                            <option value="">Semua Periode</option>
                            <option value="today">Hari Ini</option>
                            <option value="yesterday">Kemarin</option>
                            <option value="7days">7 Hari Terakhir</option>
                            <option value="30days">30 Hari Terakhir</option>
                            <option value="month">Bulan Ini</option>
                        </select>
                    </div>
                </div>

                <!-- Active Filters & Reset -->
                <div
                    v-if="hasActiveFilters"
                    class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-700/60"
                >
                    <div
                        class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
                    >
                        <span
                            class="inline-block h-2 w-2 animate-pulse rounded-full bg-blue-500"
                        ></span>
                        <span>Filter aktif diterapkan</span>
                    </div>

                    <button
                        type="button"
                        @click="resetFilters"
                        class="text-xs font-semibold text-blue-600 transition hover:text-blue-700 hover:underline dark:text-blue-400"
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
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-blue-50 font-bold text-blue-700 dark:border-gray-700 dark:bg-blue-950/70 dark:text-blue-300"
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
                                            'inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold capitalize tracking-wide',
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
                                        class="mt-1.5 inline-flex items-center gap-1 rounded-md bg-blue-50/80 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-950/50 dark:text-blue-300"
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
                                        class="shadow-xs inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:border-blue-300 hover:bg-blue-50/50 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-blue-600 dark:hover:bg-blue-950/40 dark:hover:text-blue-400"
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
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                        <span>Detail</span>
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
                                            class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800"
                                        >
                                            <svg
                                                class="h-7 w-7 text-gray-400 dark:text-gray-500"
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
                                            class="mt-3 text-base font-bold text-gray-900 dark:text-white"
                                        >
                                            Tidak ada riwayat log ditemukan
                                        </div>
                                        <p
                                            class="mt-1 max-w-sm text-xs text-gray-500 dark:text-gray-400"
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
                    class="border-t border-gray-100 p-4 dark:border-gray-700/60"
                >
                    <Pagination :links="logs.links" />
                </div>
            </div>
        </div>

        <!-- DETAIL MODAL -->
        <ActivityLogDetailModal
            :show="showDetailModal"
            :log="selectedLog"
            :events-map="filterOptions?.events || {}"
            @close="showDetailModal = false"
        />

        <!-- CLEAR LOGS MODAL -->
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
                                    ? 'border-blue-500 bg-blue-50/40 dark:bg-blue-950/30'
                                    : 'dark:hover:bg-gray-750 border-gray-200 hover:bg-gray-50 dark:border-gray-700'
                            "
                        >
                            <div class="flex items-center gap-3">
                                <input
                                    type="radio"
                                    v-model="clearDays"
                                    value="30"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500"
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
                                    ? 'border-blue-500 bg-blue-50/40 dark:bg-blue-950/30'
                                    : 'dark:hover:bg-gray-750 border-gray-200 hover:bg-gray-50 dark:border-gray-700'
                            "
                        >
                            <div class="flex items-center gap-3">
                                <input
                                    type="radio"
                                    v-model="clearDays"
                                    value="60"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500"
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
                                    ? 'border-blue-500 bg-blue-50/40 dark:bg-blue-950/30'
                                    : 'dark:hover:bg-gray-750 border-gray-200 hover:bg-gray-50 dark:border-gray-700'
                            "
                        >
                            <div class="flex items-center gap-3">
                                <input
                                    type="radio"
                                    v-model="clearDays"
                                    value="90"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500"
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
                        <Button
                            variant="secondary"
                            @click="showClearModal = false"
                            :disabled="isClearing"
                            class="px-4 py-2 text-xs"
                        >
                            Batal
                        </Button>
                        <Button
                            variant="danger"
                            @click="executeClearLogs"
                            :disabled="isClearing"
                            class="px-4 py-2 text-xs"
                        >
                            {{
                                isClearing
                                    ? 'Membersihkan...'
                                    : 'Konfirmasi Bersihkan'
                            }}
                        </Button>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedTemplate>
</template>
