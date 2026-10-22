<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Button from '@/Components/Atoms/Button.vue';
import Icon from '@/Components/Atoms/Icon.vue';
import ConfirmDialog from '@/Components/Molecules/ConfirmDialog.vue';
import StatsCard from '@/Components/Molecules/StatsCard.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import AuthenticatedTemplate from '@/Components/Templates/AuthenticatedTemplate.vue';
import CalendarEditModal from './Partials/CalendarEditModal.vue';
import CalendarSyncModal from './Partials/CalendarSyncModal.vue';

const props = defineProps({
    days: Array,
    currentYear: Number,
    currentMonth: Number,
    firstDayOfWeek: Number, // 0 = Minggu ... 6 = Sabtu
    firstDayOfWeekIso: Number, // 1 = Senin ... 7 = Minggu
    daysInMonth: Number,
    monthStats: Object,
    yearStats: Object,
    monthsList: Array,
    yearsList: Array,
    filters: Object,
});

// View & Filter States
const selectedYear = ref(props.currentYear);
const selectedMonth = ref(props.currentMonth);
const viewMode = ref(props.filters.view || 'grid'); // 'grid' | 'table'
const statusFilter = ref(props.filters.status || '');
const searchQuery = ref(props.filters.search || '');

// Modals
const showEditModal = ref(false);
const showSyncModal = ref(false);
const showResetModal = ref(false);
const activeDay = ref(null);

// Form for editing day
const editForm = useForm({
    is_working_day: true,
    name: '',
    type: 'workday',
});

// Form for syncing
const syncForm = useForm({
    year: props.currentYear,
});

// Form for resetting
const resetForm = useForm({
    year: props.currentYear,
});

const applyNavigation = () => {
    router.get(
        route('calendar.index'),
        {
            year: selectedYear.value,
            month: selectedMonth.value,
            view: viewMode.value,
            status: statusFilter.value,
            search: searchQuery.value,
        },
        { preserveState: true, replace: true },
    );
};

// Next & Prev Month
const prevMonth = () => {
    if (selectedMonth.value === 1) {
        selectedMonth.value = 12;
        selectedYear.value -= 1;
    } else {
        selectedMonth.value -= 1;
    }
    applyNavigation();
};

const nextMonth = () => {
    if (selectedMonth.value === 12) {
        selectedMonth.value = 1;
        selectedYear.value += 1;
    } else {
        selectedMonth.value += 1;
    }
    applyNavigation();
};

// Next & Prev Year
const prevYear = () => {
    selectedYear.value -= 1;
    applyNavigation();
};

const nextYear = () => {
    selectedYear.value += 1;
    applyNavigation();
};

const goToToday = () => {
    const today = new Date();
    selectedYear.value = today.getFullYear();
    selectedMonth.value = today.getMonth() + 1;
    applyNavigation();
};

const switchViewMode = (mode) => {
    viewMode.value = mode;
    if (mode === 'grid') {
        statusFilter.value = '';
    }
    applyNavigation();
};

let searchTimeout = null;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyNavigation();
    }, 350);
});

watch(statusFilter, () => {
    applyNavigation();
});

// Open Edit Modal for a day
const openEditDayModal = (day) => {
    activeDay.value = day;
    editForm.is_working_day = Boolean(day.is_working_day);
    editForm.name = day.name || '';
    editForm.type =
        day.type || (day.is_working_day ? 'workday' : 'custom_holiday');
    showEditModal.value = true;
};

const submitEditDay = () => {
    if (!activeDay.value) return;

    editForm.patch(route('calendar.update', activeDay.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            activeDay.value = null;
        },
    });
};

// Open Sync Modal
const openSyncModal = () => {
    syncForm.year = selectedYear.value;
    showSyncModal.value = true;
};

const submitSync = () => {
    syncForm.post(route('calendar.sync'), {
        preserveScroll: true,
        onSuccess: () => {
            showSyncModal.value = false;
        },
    });
};

// Open Reset Modal
const openResetModal = () => {
    resetForm.year = selectedYear.value;
    showResetModal.value = true;
};

const submitReset = () => {
    resetForm.post(route('calendar.reset'), {
        preserveScroll: true,
        onSuccess: () => {
            showResetModal.value = false;
        },
    });
};

// Month name helper
const currentMonthName = computed(() => {
    const found = props.monthsList.find((m) => m.value === selectedMonth.value);
    return found ? found.label : '';
});

// Days of previous month to show as leading blank/faded cells (Sunday-based)
const leadingTrailingDays = computed(() => {
    // 0 = Minggu, 1 = Senin, 2 = Selasa, 3 = Rabu, 4 = Kamis, 5 = Jumat, 6 = Sabtu
    const count =
        props.firstDayOfWeek !== undefined
            ? props.firstDayOfWeek
            : props.firstDayOfWeekIso % 7;

    if (count <= 0) return [];

    // Calculate days in previous month
    const prevMonthNumber =
        selectedMonth.value === 1 ? 12 : selectedMonth.value - 1;
    const prevYearNumber =
        selectedMonth.value === 1 ? selectedYear.value - 1 : selectedYear.value;
    const daysInPrevMonth = new Date(
        prevYearNumber,
        prevMonthNumber,
        0,
    ).getDate();

    const result = [];
    for (let i = count - 1; i >= 0; i--) {
        result.push(daysInPrevMonth - i);
    }
    return result;
});

// Days of next month to complete 7 columns
const trailingNextDays = computed(() => {
    const totalRendered =
        leadingTrailingDays.value.length + (props.days?.length || 0);
    const remainder = totalRendered % 7;
    if (remainder === 0) return [];
    const count = 7 - remainder;
    return Array.from({ length: count }, (_, i) => i + 1);
});

// Helper for date badge classes
const getTypeBadgeInfo = (day) => {
    if (day.type === 'national_holiday') {
        return {
            text: 'Libur Nasional',
            dot: 'bg-rose-500',
            pill: 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800/70',
        };
    }
    if (day.type === 'collective_leave') {
        return {
            text: 'Cuti Bersama',
            dot: 'bg-amber-500',
            pill: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800/70',
        };
    }
    if (day.type === 'custom_holiday') {
        return {
            text: 'Libur Khusus',
            dot: 'bg-purple-500',
            pill: 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800/70',
        };
    }
    if (day.type === 'custom_workday') {
        return {
            text: 'Kerja Pengganti',
            dot: 'bg-cyan-500',
            pill: 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-950/60 dark:text-cyan-300 dark:border-cyan-800/70',
        };
    }
    if (day.day_of_week >= 6) {
        return {
            text: 'Akhir Pekan',
            dot: 'bg-slate-400',
            pill: 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-gray-700/50 dark:text-gray-300 dark:border-gray-600',
        };
    }
    return {
        text: 'Hari Kerja',
        dot: 'bg-emerald-500',
        pill: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800/60',
    };
};

// Check if day is today
const isToday = (dateStr) => {
    const todayStr = new Date().toISOString().slice(0, 10);
    return dateStr === todayStr;
};

// Format date to local Indonesian string
const formatDateIndo = (dateStr) => {
    if (!dateStr) return '-';
    try {
        const parts = dateStr.split('-');
        const date = new Date(parts[0], parts[1] - 1, parts[2]);
        return date.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
};

const formatDateTimeIndo = (dtStr) => {
    if (!dtStr) return '-';
    try {
        const d = new Date(dtStr);
        return d.toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dtStr;
    }
};
</script>

<template>
    <Head title="Master Kalender Kerja & Libur" />

    <AuthenticatedTemplate>
        <template #header>
            <PageHeader
                title="Master Kalender Kerja & Libur"
                subtitle="Atur hari kerja weekday, weekend libur, sinkronkan libur nasional via API, atau ubah status hari secara fleksibel."
                :breadcrumbs="[
                    { label: 'Dashboard', href: route('dashboard') },
                    { label: 'Master Kalender' },
                ]"
            >
                <template #badge>
                    <span
                        class="inline-flex items-center rounded-full border border-blue-200/60 bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700 dark:border-blue-800/60 dark:bg-blue-950/60 dark:text-blue-300"
                    >
                        Tahun {{ selectedYear }}
                    </span>
                </template>
                <template #actions>
                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            @click="openResetModal"
                            title="Kembalikan hari ke default kerja & libur"
                        >
                            <template #icon-left>
                                <Icon
                                    name="refresh"
                                    class="h-4 w-4 text-slate-400"
                                />
                            </template>
                            <span>Reset Default</span>
                        </Button>
                        <Button
                            type="button"
                            variant="primary"
                            size="sm"
                            @click="openSyncModal"
                        >
                            <template #icon-left>
                                <Icon name="refresh" class="h-4 w-4" />
                            </template>
                            <span>Sinkronisasi API</span>
                        </Button>
                    </div>
                </template>
            </PageHeader>
        </template>

        <div class="space-y-6 pb-12">
            <!-- STATS METRIC CARDS -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatsCard
                    title="Hari Kerja (Bulan Ini)"
                    :value="monthStats.workdays_count"
                    unit="Hari"
                    variant="success"
                    :progress="
                        Math.round(
                            (monthStats.workdays_count /
                                (monthStats.total_days || 1)) *
                                100,
                        )
                    "
                    description="Waktu operasional aktif"
                />

                <StatsCard
                    title="Hari Libur (Bulan Ini)"
                    :value="monthStats.holidays_count"
                    unit="Hari Off"
                    variant="danger"
                    :progress="
                        Math.round(
                            (monthStats.holidays_count /
                                (monthStats.total_days || 1)) *
                                100,
                        )
                    "
                    description="Libur akhir pekan & cuti"
                />

                <StatsCard
                    title="Libur Nasional & Cuti"
                    :value="
                        (monthStats.national_holidays_count || 0) +
                        (monthStats.collective_leave_count || 0)
                    "
                    unit="Hari"
                    variant="warning"
                    description="Resmi kalender nasional"
                />

                <StatsCard
                    title="Penyesuaian Manual"
                    :value="monthStats.overridden_count"
                    unit="Hari"
                    variant="primary"
                    description="Tercatat dalam audit trail"
                />
            </div>

            <!-- TOOLBAR NAVIGASI BULAN & FILTER -->
            <div
                class="shadow-xs rounded-2xl border border-gray-200/80 bg-white p-4 dark:border-gray-700/80 dark:bg-gray-800"
            >
                <div
                    class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between"
                >
                    <!-- Segmented Month & Year Switcher -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div
                            class="flex items-center gap-1 rounded-xl border border-gray-200 bg-gray-50/80 p-1 dark:border-gray-700 dark:bg-gray-900/50"
                        >
                            <button
                                type="button"
                                @click="prevMonth"
                                class="hover:shadow-2xs rounded-lg p-2 text-gray-600 transition hover:bg-white hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                                title="Bulan Sebelumnya"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 19l-7-7 7-7"
                                    />
                                </svg>
                            </button>

                            <div class="flex items-center px-2">
                                <select
                                    v-model="selectedMonth"
                                    @change="applyNavigation"
                                    class="cursor-pointer border-0 bg-transparent py-1 pl-1 pr-10 text-sm font-bold text-gray-900 focus:ring-0 dark:text-white"
                                >
                                    <option
                                        v-for="m in monthsList"
                                        :key="m.value"
                                        :value="m.value"
                                        class="dark:bg-gray-800"
                                    >
                                        {{ m.label }}
                                    </option>
                                </select>

                                <select
                                    v-model="selectedYear"
                                    @change="applyNavigation"
                                    class="cursor-pointer border-0 bg-transparent py-1 pl-1 pr-10 text-sm font-bold text-blue-600 focus:ring-0 dark:text-blue-400"
                                >
                                    <option
                                        v-for="y in yearsList"
                                        :key="y"
                                        :value="y"
                                        class="dark:bg-gray-800"
                                    >
                                        {{ y }}
                                    </option>
                                </select>
                            </div>

                            <button
                                type="button"
                                @click="nextMonth"
                                class="hover:shadow-2xs rounded-lg p-2 text-gray-600 transition hover:bg-white hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                                title="Bulan Berikutnya"
                            >
                                <svg
                                    class="h-4 w-4"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 5l7 7-7 7"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- Today Quick Button -->
                        <button
                            type="button"
                            @click="goToToday"
                            class="shadow-2xs inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <svg
                                class="h-3.5 w-3.5 text-blue-500"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                            Bulan Ini
                        </button>
                    </div>

                    <!-- Filter Tabs & Controls -->
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Filter Status Pills (Hanya Tampil di Mode Tabel) -->
                        <div
                            v-if="viewMode === 'table'"
                            class="flex items-center gap-1 rounded-xl border border-gray-200 bg-gray-50/80 p-1 dark:border-gray-700 dark:bg-gray-900/50"
                        >
                            <button
                                type="button"
                                @click="
                                    statusFilter = '';
                                    applyNavigation();
                                "
                                :class="[
                                    'rounded-lg px-2.5 py-1 text-xs font-semibold transition',
                                    statusFilter === ''
                                        ? 'shadow-2xs bg-white text-gray-900 dark:bg-gray-800 dark:text-white'
                                        : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                                ]"
                            >
                                Semua
                            </button>
                            <button
                                type="button"
                                @click="
                                    statusFilter = 'workday';
                                    applyNavigation();
                                "
                                :class="[
                                    'flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold transition',
                                    statusFilter === 'workday'
                                        ? 'shadow-2xs bg-emerald-500 text-white'
                                        : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                                ]"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="
                                        statusFilter === 'workday'
                                            ? 'bg-white'
                                            : 'bg-emerald-500'
                                    "
                                ></span>
                                Kerja
                            </button>
                            <button
                                type="button"
                                @click="
                                    statusFilter = 'holiday';
                                    applyNavigation();
                                "
                                :class="[
                                    'flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold transition',
                                    statusFilter === 'holiday'
                                        ? 'shadow-2xs bg-rose-500 text-white'
                                        : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                                ]"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="
                                        statusFilter === 'holiday'
                                            ? 'bg-white'
                                            : 'bg-rose-500'
                                    "
                                ></span>
                                Libur
                            </button>
                        </div>

                        <!-- Search Input -->
                        <div class="relative min-w-[200px]">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
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
                            </span>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari tanggal/keterangan..."
                                class="shadow-2xs w-full rounded-xl border border-gray-200 bg-white py-1.5 pl-8 pr-3 text-xs text-gray-700 placeholder:text-gray-400 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            />
                        </div>

                        <!-- View Switch: Grid vs Table -->
                        <div
                            class="flex items-center rounded-xl border border-gray-200 bg-gray-50/80 p-1 dark:border-gray-700 dark:bg-gray-900/50"
                        >
                            <button
                                type="button"
                                @click="switchViewMode('grid')"
                                :class="[
                                    'flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs font-bold transition',
                                    viewMode === 'grid'
                                        ? 'shadow-2xs bg-white text-blue-600 dark:bg-gray-800 dark:text-blue-400'
                                        : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                                ]"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                                    />
                                </svg>
                                Kalender
                            </button>

                            <button
                                type="button"
                                @click="switchViewMode('table')"
                                :class="[
                                    'flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs font-bold transition',
                                    viewMode === 'table'
                                        ? 'shadow-2xs bg-white text-blue-600 dark:bg-gray-800 dark:text-blue-400'
                                        : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                                ]"
                            >
                                <svg
                                    class="h-3.5 w-3.5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M4 6h16M4 10h16M4 14h16M4 18h16"
                                    />
                                </svg>
                                Tabel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAMPILAN 1: GRID KALENDER BULANAN (ELEVATED DESIGN) -->
            <div v-if="viewMode === 'grid'" class="space-y-4">
                <div
                    class="overflow-hidden rounded-2xl border border-gray-200/90 bg-white shadow-sm dark:border-gray-700/80 dark:bg-gray-800"
                >
                    <div class="overflow-x-auto">
                        <div class="min-w-[768px]">
                            <!-- Header Hari: Urutan Minggu s/d Sabtu -->
                            <div
                                class="grid grid-cols-7 border-b border-gray-200 bg-gray-50/90 text-center text-xs font-bold uppercase tracking-wider dark:border-gray-700 dark:bg-gray-900/60"
                                style="
                                    display: grid;
                                    grid-template-columns: repeat(
                                        7,
                                        minmax(0, 1fr)
                                    );
                                "
                            >
                                <div
                                    class="bg-rose-50/50 py-3.5 text-rose-600 dark:bg-rose-950/20 dark:text-rose-400"
                                >
                                    Minggu
                                </div>
                                <div
                                    class="py-3.5 text-gray-700 dark:text-gray-300"
                                >
                                    Senin
                                </div>
                                <div
                                    class="py-3.5 text-gray-700 dark:text-gray-300"
                                >
                                    Selasa
                                </div>
                                <div
                                    class="py-3.5 text-gray-700 dark:text-gray-300"
                                >
                                    Rabu
                                </div>
                                <div
                                    class="py-3.5 text-gray-700 dark:text-gray-300"
                                >
                                    Kamis
                                </div>
                                <div
                                    class="py-3.5 text-gray-700 dark:text-gray-300"
                                >
                                    Jum'at
                                </div>
                                <div
                                    class="border-l border-gray-200/60 bg-rose-50/50 py-3.5 text-rose-600 dark:border-gray-700/60 dark:bg-rose-950/20 dark:text-rose-400"
                                >
                                    Sabtu
                                </div>
                            </div>

                            <!-- Grid Badan Kalender -->
                            <div
                                class="grid grid-cols-7 gap-px bg-gray-200/80 dark:bg-gray-700/60"
                                style="
                                    display: grid;
                                    grid-template-columns: repeat(
                                        7,
                                        minmax(0, 1fr)
                                    );
                                "
                            >
                                <!-- Hari Bulan Sebelumnya (Leading Faded Days) -->
                                <div
                                    v-for="prevDayNumber in leadingTrailingDays"
                                    :key="'prev-' + prevDayNumber"
                                    class="min-h-[125px] select-none bg-gray-50/40 p-3 dark:bg-gray-900/30"
                                >
                                    <span
                                        class="inline-block text-xs font-medium text-gray-300 dark:text-gray-600"
                                    >
                                        {{ prevDayNumber }}
                                    </span>
                                </div>

                                <!-- Kartu Tanggal Bulan Berjalan -->
                                <div
                                    v-for="day in days"
                                    :key="day.id"
                                    @click="openEditDayModal(day)"
                                    :class="[
                                        'group relative flex min-h-[125px] cursor-pointer flex-col justify-between p-3 transition-all duration-150',
                                        day.day_of_week >= 6
                                            ? 'bg-slate-50/70 hover:bg-slate-100/80 dark:bg-gray-800/60 dark:hover:bg-gray-800'
                                            : 'dark:hover:bg-gray-750 bg-white hover:bg-blue-50/30 dark:bg-gray-800',
                                        day.is_overridden
                                            ? 'border-t-2 border-t-amber-400 dark:border-t-amber-500'
                                            : '',
                                        isToday(day.date)
                                            ? 'shadow-xs bg-blue-50/20 ring-2 ring-inset ring-blue-500 dark:bg-blue-950/30'
                                            : '',
                                    ]"
                                >
                                    <!-- Header Kartu: Nomor Tanggal & Indikator -->
                                    <div>
                                        <div
                                            class="flex items-start justify-between"
                                        >
                                            <div
                                                class="flex items-center gap-1.5"
                                            >
                                                <!-- Date Badge -->
                                                <span
                                                    :class="[
                                                        'flex h-7 w-7 items-center justify-center rounded-xl text-xs font-black transition-transform duration-150 group-hover:scale-105',
                                                        isToday(day.date)
                                                            ? 'bg-blue-600 text-white shadow-md shadow-blue-500/30 ring-2 ring-blue-200 dark:ring-blue-900'
                                                            : day.is_working_day
                                                              ? 'bg-gray-100/60 text-gray-900 dark:bg-gray-700/50 dark:text-gray-100'
                                                              : 'bg-rose-50 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400',
                                                    ]"
                                                >
                                                    {{ day.day }}
                                                </span>

                                                <span
                                                    v-if="isToday(day.date)"
                                                    class="rounded-md bg-blue-100 px-1.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wide text-blue-700 dark:bg-blue-950/80 dark:text-blue-300"
                                                >
                                                    Hari Ini
                                                </span>
                                            </div>

                                            <!-- Status / Override Indicators -->
                                            <div
                                                class="flex items-center gap-1"
                                            >
                                                <span
                                                    v-if="day.is_overridden"
                                                    class="inline-flex items-center gap-0.5 rounded-md bg-amber-100 px-1.5 py-0.5 text-[9px] font-bold text-amber-800 ring-1 ring-amber-300/60 dark:bg-amber-950/60 dark:text-amber-300 dark:ring-amber-700"
                                                    title="Diubah secara manual oleh pengguna"
                                                >
                                                    <svg
                                                        class="h-2.5 w-2.5"
                                                        fill="none"
                                                        viewBox="0 0 24 24"
                                                        stroke="currentColor"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2.5"
                                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                                        />
                                                    </svg>
                                                    Manual
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Body: Event / Holiday Pill -->
                                        <div class="mt-2.5 space-y-1">
                                            <!-- Jika ada nama hari libur / cuti khusus -->
                                            <div
                                                v-if="day.name"
                                                :class="[
                                                    'shadow-2xs group-hover:shadow-xs flex items-center gap-1.5 rounded-lg border px-2 py-1.5 text-xs font-medium transition',
                                                    getTypeBadgeInfo(day).pill,
                                                ]"
                                                :title="day.name"
                                            >
                                                <span
                                                    :class="[
                                                        'h-2 w-2 shrink-0 rounded-full ring-2 ring-white/80 dark:ring-gray-900',
                                                        getTypeBadgeInfo(day)
                                                            .dot,
                                                    ]"
                                                />
                                                <span
                                                    class="truncate font-bold tracking-tight"
                                                >
                                                    {{ day.name }}
                                                </span>
                                            </div>

                                            <!-- Hari Kerja Biasa -->
                                            <div
                                                v-else-if="day.is_working_day"
                                                class="flex items-center gap-1.5 py-1 text-[11px] text-gray-400 dark:text-gray-500"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full bg-emerald-500/80"
                                                ></span>
                                                <span class="truncate"
                                                    >Hari Kerja Normal</span
                                                >
                                            </div>

                                            <!-- Weekend Biasa Tanpa Libur Khusus -->
                                            <div
                                                v-else
                                                class="flex items-center gap-1.5 py-1 text-[11px] text-gray-400 dark:text-gray-500"
                                            >
                                                <span
                                                    class="h-1.5 w-1.5 rounded-full bg-slate-300 dark:bg-gray-600"
                                                ></span>
                                                <span class="truncate">{{
                                                    day.day_name
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Footer Kartu: Info Updater -->
                                    <div
                                        class="mt-3 flex items-center justify-between border-t border-gray-100/80 pt-1.5 text-[10px] text-gray-400 dark:border-gray-700/50 dark:text-gray-500"
                                    >
                                        <span
                                            class="max-w-[120px] truncate font-medium"
                                            :title="
                                                day.updater
                                                    ? day.updater.name
                                                    : 'Sistem Bawaan'
                                            "
                                        >
                                            {{
                                                day.updater
                                                    ? day.updater.name
                                                    : 'Sistem'
                                            }}
                                        </span>

                                        <span
                                            v-if="!day.updater"
                                            class="text-[9px] text-gray-300 dark:text-gray-600"
                                        >
                                            Default
                                        </span>
                                    </div>
                                </div>

                                <!-- Hari Bulan Berikutnya (Trailing Faded Days) -->
                                <div
                                    v-for="nextDayNumber in trailingNextDays"
                                    :key="'next-' + nextDayNumber"
                                    class="min-h-[125px] select-none bg-gray-50/40 p-3 dark:bg-gray-900/30"
                                >
                                    <span
                                        class="inline-block text-xs font-medium text-gray-300 dark:text-gray-600"
                                    >
                                        {{ nextDayNumber }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PETUNJUK WARNA (COLOR LEGEND BAR) -->
                <div
                    class="shadow-xs flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200/80 bg-white p-4 text-xs text-gray-600 dark:border-gray-700/80 dark:bg-gray-800 dark:text-gray-300"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                        >
                            Petunjuk Warna:
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-xs">
                        <div class="flex items-center gap-1.5">
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-emerald-200 dark:ring-emerald-950"
                            ></span>
                            <span>Hari Kerja</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-rose-500 ring-2 ring-rose-200 dark:ring-rose-950"
                            ></span>
                            <span>Libur Nasional</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-amber-500 ring-2 ring-amber-200 dark:ring-amber-950"
                            ></span>
                            <span>Cuti Bersama</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-purple-500 ring-2 ring-purple-200 dark:ring-purple-950"
                            ></span>
                            <span>Libur Khusus</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-cyan-500 ring-2 ring-cyan-200 dark:ring-cyan-950"
                            ></span>
                            <span>Kerja Pengganti</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-slate-300 dark:bg-gray-600"
                            ></span>
                            <span>Akhir Pekan</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="inline-flex items-center rounded-md bg-amber-100 px-1 py-0.5 text-[9px] font-bold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300"
                            >
                                Manual
                            </span>
                            <span>Override Manual</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAMPILAN 2: TABEL RINCIAN HARI -->
            <div
                v-else
                class="shadow-xs overflow-hidden rounded-2xl border border-gray-200/90 bg-white dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="overflow-x-auto">
                    <table
                        class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                    >
                        <thead
                            class="bg-gray-50/90 text-left text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:bg-gray-900/50 dark:text-gray-400"
                        >
                            <tr>
                                <th scope="col" class="py-4 pl-4 pr-3 sm:pl-6">
                                    Tanggal & Hari
                                </th>
                                <th scope="col" class="px-3 py-4">Status</th>
                                <th scope="col" class="px-3 py-4">
                                    Tipe Kalender
                                </th>
                                <th scope="col" class="px-3 py-4">
                                    Keterangan
                                </th>
                                <th scope="col" class="px-3 py-4">
                                    Sumber Data
                                </th>
                                <th scope="col" class="px-3 py-4">
                                    Diubah Oleh
                                </th>
                                <th scope="col" class="px-3 py-4">
                                    Waktu Update
                                </th>
                                <th
                                    scope="col"
                                    class="relative py-4 pl-3 pr-4 text-right sm:pr-6"
                                >
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-200 bg-white text-xs dark:divide-gray-700 dark:bg-gray-800"
                        >
                            <tr
                                v-for="day in days"
                                :key="day.id"
                                :class="[
                                    'transition hover:bg-gray-50/80 dark:hover:bg-gray-700/40',
                                    isToday(day.date)
                                        ? 'bg-blue-50/30 dark:bg-blue-950/20'
                                        : '',
                                ]"
                            >
                                <!-- Tanggal & Hari -->
                                <td
                                    class="whitespace-nowrap py-3.5 pl-4 pr-3 sm:pl-6"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            :class="[
                                                'shadow-2xs flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-xs font-black',
                                                day.is_working_day
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                                    : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300',
                                            ]"
                                        >
                                            {{ day.day }}
                                        </div>
                                        <div>
                                            <div
                                                class="flex items-center gap-1.5 font-bold text-gray-900 dark:text-white"
                                            >
                                                {{ formatDateIndo(day.date) }}
                                                <span
                                                    v-if="isToday(day.date)"
                                                    class="py-0.2 rounded-md bg-blue-100 px-1.5 text-[9px] font-bold text-blue-700 dark:bg-blue-900/60 dark:text-blue-300"
                                                >
                                                    Hari Ini
                                                </span>
                                            </div>
                                            <div
                                                class="text-[11px] font-medium text-gray-400 dark:text-gray-500"
                                            >
                                                {{ day.day_name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="whitespace-nowrap px-3 py-3.5">
                                    <span
                                        :class="[
                                            'shadow-2xs inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold',
                                            day.is_working_day
                                                ? 'border border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300'
                                                : 'border border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'h-2 w-2 rounded-full',
                                                day.is_working_day
                                                    ? 'bg-emerald-500'
                                                    : 'bg-rose-500',
                                            ]"
                                        />
                                        {{
                                            day.is_working_day
                                                ? 'Hari Kerja'
                                                : 'Hari Libur'
                                        }}
                                    </span>
                                </td>

                                <!-- Tipe Kalender -->
                                <td class="whitespace-nowrap px-3 py-3.5">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 rounded-lg border px-2 py-0.5 text-xs font-medium',
                                            getTypeBadgeInfo(day).pill,
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'h-1.5 w-1.5 rounded-full',
                                                getTypeBadgeInfo(day).dot,
                                            ]"
                                        />
                                        {{ getTypeBadgeInfo(day).text }}
                                    </span>
                                </td>

                                <!-- Keterangan -->
                                <td
                                    class="max-w-xs px-3 py-3.5 text-gray-700 dark:text-gray-300"
                                >
                                    <div
                                        class="truncate font-medium"
                                        :title="day.name"
                                    >
                                        {{ day.name || '-' }}
                                    </div>
                                </td>

                                <!-- Sumber Data -->
                                <td
                                    class="whitespace-nowrap px-3 py-3.5 text-gray-500 dark:text-gray-400"
                                >
                                    <span
                                        v-if="day.source === 'api_sync'"
                                        class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-0.5 text-[11px] font-bold text-blue-700 dark:bg-blue-950/40 dark:text-blue-300"
                                    >
                                        API Kalender
                                    </span>
                                    <span
                                        v-else-if="day.source === 'manual'"
                                        class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-[11px] font-bold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300"
                                    >
                                        Manual
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        Default
                                    </span>
                                </td>

                                <!-- Diubah Oleh (Nama User) -->
                                <td
                                    class="whitespace-nowrap px-3 py-3.5 text-gray-600 dark:text-gray-300"
                                >
                                    <span
                                        v-if="day.updater"
                                        class="font-medium text-gray-900 dark:text-white"
                                    >
                                        {{ day.updater.name }}
                                    </span>
                                    <span
                                        v-else
                                        class="text-[11px] italic text-gray-400"
                                    >
                                        Sistem Bawaan
                                    </span>
                                </td>

                                <!-- Waktu Update -->
                                <td
                                    class="whitespace-nowrap px-3 py-3.5 text-[11px] text-gray-500 dark:text-gray-400"
                                >
                                    {{ formatDateTimeIndo(day.updated_at) }}
                                </td>

                                <!-- Aksi -->
                                <td
                                    class="whitespace-nowrap py-3.5 pl-3 pr-4 text-right sm:pr-6"
                                >
                                    <button
                                        type="button"
                                        @click="openEditDayModal(day)"
                                        class="shadow-2xs inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-white px-3 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                    >
                                        <svg
                                            class="h-3.5 w-3.5 text-gray-400"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                            />
                                        </svg>
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <CalendarEditModal
            :show="showEditModal"
            :active-day="activeDay"
            :form="editForm"
            @close="showEditModal = false"
            @submit="submitEditDay"
        />

        <CalendarSyncModal
            :show="showSyncModal"
            :current-year="selectedYear"
            :years-list="yearsList"
            @close="showSyncModal = false"
        />

        <ConfirmDialog
            :show="showResetModal"
            title="Reset Kalender ke Default"
            message="Tindakan ini akan mengatur ulang semua hari pada tahun terpilih menjadi Senin s/d Jumat Hari Kerja dan Sabtu & Minggu Libur Akhir Pekan, serta menghapus seluruh status override manual dan hasil sinkronisasi API."
            confirm-label="Ya, Reset Sekarang"
            variant="danger"
            :processing="resetForm.processing"
            @close="showResetModal = false"
            @confirm="submitReset"
        />
    </AuthenticatedTemplate>
</template>
