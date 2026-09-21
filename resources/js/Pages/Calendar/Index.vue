<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

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
        { preserveState: true, replace: true }
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
    editForm.type = day.type || (day.is_working_day ? 'workday' : 'custom_holiday');
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
    const count = props.firstDayOfWeek !== undefined
        ? props.firstDayOfWeek
        : (props.firstDayOfWeekIso % 7);

    if (count <= 0) return [];

    // Calculate days in previous month
    const prevMonthNumber = selectedMonth.value === 1 ? 12 : selectedMonth.value - 1;
    const prevYearNumber = selectedMonth.value === 1 ? selectedYear.value - 1 : selectedYear.value;
    const daysInPrevMonth = new Date(prevYearNumber, prevMonthNumber, 0).getDate();

    const result = [];
    for (let i = count - 1; i >= 0; i--) {
        result.push(daysInPrevMonth - i);
    }
    return result;
});

// Days of next month to complete 7 columns
const trailingNextDays = computed(() => {
    const totalRendered = leadingTrailingDays.value.length + (props.days?.length || 0);
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

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600 text-white shadow-md shadow-indigo-500/20">
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-xl font-black tracking-tight text-gray-900 dark:text-white">
                                    Master Kalender Kerja & Libur
                                </h1>
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                                    Tahun {{ selectedYear }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Atur hari kerja weekday, weekend libur, sinkronkan libur nasional via API, atau ubah status hari secara fleksibel.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Reset Default Button -->
                    <button
                        type="button"
                        @click="openResetModal"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-xs transition hover:bg-gray-50 hover:text-gray-900 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        title="Kembalikan hari ke default kerja & libur"
                    >
                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset Default
                    </button>

                    <!-- API Sync Button -->
                    <PrimaryButton
                        @click="openSyncModal"
                        class="flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-700 to-blue-600 px-4 py-2 text-xs font-semibold text-white shadow-md shadow-indigo-500/25 transition hover:from-indigo-700 hover:to-blue-700"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        Sinkronisasi API
                    </PrimaryButton>
                </div>
            </div>
        </template>

        <div class="space-y-6 pb-12">
            <!-- STATS METRIC CARDS -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Hari Kerja Bulan Ini -->
                <div class="relative overflow-hidden rounded-2xl border border-gray-200/80 bg-white p-5 shadow-xs transition hover:shadow-md dark:border-gray-700/80 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Hari Kerja (Bulan Ini)
                        </span>
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black tracking-tight text-gray-900 dark:text-white">
                            {{ monthStats.workdays_count }}
                        </span>
                        <span class="text-xs font-medium text-gray-400 dark:text-gray-500">
                            / {{ monthStats.total_days }} Hari
                        </span>
                    </div>
                    <!-- Mini Progress Bar -->
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                            <span>Waktu Produktif</span>
                            <span>{{ Math.round((monthStats.workdays_count / (monthStats.total_days || 1)) * 100) }}%</span>
                        </div>
                        <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                            <div
                                class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                :style="{ width: `${Math.round((monthStats.workdays_count / (monthStats.total_days || 1)) * 100)}%` }"
                            />
                        </div>
                    </div>
                </div>

                <!-- Total Hari Libur Bulan Ini -->
                <div class="relative overflow-hidden rounded-2xl border border-gray-200/80 bg-white p-5 shadow-xs transition hover:shadow-md dark:border-gray-700/80 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Hari Libur (Bulan Ini)
                        </span>
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black tracking-tight text-gray-900 dark:text-white">
                            {{ monthStats.holidays_count }}
                        </span>
                        <span class="text-xs font-medium text-gray-400 dark:text-gray-500">
                            Hari Off
                        </span>
                    </div>
                    <!-- Mini Progress Bar -->
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-[11px] font-semibold text-rose-600 dark:text-rose-400">
                            <span>Rasio Hari Libur</span>
                            <span>{{ Math.round((monthStats.holidays_count / (monthStats.total_days || 1)) * 100) }}%</span>
                        </div>
                        <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                            <div
                                class="h-full rounded-full bg-rose-500 transition-all duration-500"
                                :style="{ width: `${Math.round((monthStats.holidays_count / (monthStats.total_days || 1)) * 100)}%` }"
                            />
                        </div>
                    </div>
                </div>

                <!-- Libur Nasional & Cuti Bersama -->
                <div class="relative overflow-hidden rounded-2xl border border-gray-200/80 bg-white p-5 shadow-xs transition hover:shadow-md dark:border-gray-700/80 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Libur Nasional & Cuti
                        </span>
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black tracking-tight text-gray-900 dark:text-white">
                            {{ monthStats.national_holidays_count }}
                        </span>
                        <span class="text-xs font-medium text-gray-400 dark:text-gray-500">
                            (Bulan) • {{ yearStats.national_holidays_count }} (Tahun)
                        </span>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-[11px] font-medium text-indigo-600 dark:text-indigo-400">
                        <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                        <span>Sinkron dari Tanggal Merah API</span>
                    </div>
                </div>

                <!-- Penyesuaian Manual (Override) -->
                <div class="relative overflow-hidden rounded-2xl border border-gray-200/80 bg-white p-5 shadow-xs transition hover:shadow-md dark:border-gray-700/80 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Diubah Manual
                        </span>
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-3 flex items-baseline gap-2">
                        <span class="text-3xl font-black tracking-tight text-gray-900 dark:text-white">
                            {{ monthStats.overridden_count }}
                        </span>
                        <span class="text-xs font-medium text-gray-400 dark:text-gray-500">
                            (Bulan) • {{ yearStats.overridden_count }} (Tahun)
                        </span>
                    </div>
                    <div class="mt-3 flex items-center gap-1.5 text-[11px] font-medium text-amber-600 dark:text-amber-400">
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        <span>Tercatat Pengguna & Audit Trail</span>
                    </div>
                </div>
            </div>

            <!-- TOOLBAR NAVIGASI BULAN & FILTER -->
            <div class="rounded-2xl border border-gray-200/80 bg-white p-4 shadow-xs dark:border-gray-700/80 dark:bg-gray-800">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <!-- Segmented Month & Year Switcher -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-1 rounded-xl border border-gray-200 bg-gray-50/80 p-1 dark:border-gray-700 dark:bg-gray-900/50">
                            <button
                                type="button"
                                @click="prevMonth"
                                class="rounded-lg p-2 text-gray-600 transition hover:bg-white hover:text-gray-900 hover:shadow-2xs dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                                title="Bulan Sebelumnya"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            <div class="flex items-center px-2">
                                <select
                                    v-model="selectedMonth"
                                    @change="applyNavigation"
                                    class="border-0 bg-transparent py-1 pl-1 pr-10 text-sm font-bold text-gray-900 focus:ring-0 dark:text-white cursor-pointer"
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
                                    class="border-0 bg-transparent py-1 pl-1 pr-10 text-sm font-bold text-indigo-600 focus:ring-0 dark:text-indigo-400 cursor-pointer"
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
                                class="rounded-lg p-2 text-gray-600 transition hover:bg-white hover:text-gray-900 hover:shadow-2xs dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                                title="Bulan Berikutnya"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>

                        <!-- Today Quick Button -->
                        <button
                            type="button"
                            @click="goToToday"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-2xs transition hover:bg-gray-50 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <svg class="h-3.5 w-3.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
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
                                @click="statusFilter = ''; applyNavigation()"
                                :class="[
                                    'rounded-lg px-2.5 py-1 text-xs font-semibold transition',
                                    statusFilter === ''
                                        ? 'bg-white text-gray-900 shadow-2xs dark:bg-gray-800 dark:text-white'
                                        : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                                ]"
                            >
                                Semua
                            </button>
                            <button
                                type="button"
                                @click="statusFilter = 'workday'; applyNavigation()"
                                :class="[
                                    'flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold transition',
                                    statusFilter === 'workday'
                                        ? 'bg-emerald-500 text-white shadow-2xs'
                                        : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                                ]"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusFilter === 'workday' ? 'bg-white' : 'bg-emerald-500'"></span>
                                Kerja
                            </button>
                            <button
                                type="button"
                                @click="statusFilter = 'holiday'; applyNavigation()"
                                :class="[
                                    'flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold transition',
                                    statusFilter === 'holiday'
                                        ? 'bg-rose-500 text-white shadow-2xs'
                                        : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                                ]"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusFilter === 'holiday' ? 'bg-white' : 'bg-rose-500'"></span>
                                Libur
                            </button>
                        </div>

                        <!-- Search Input -->
                        <div class="relative min-w-[200px]">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </span>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari tanggal/keterangan..."
                                class="w-full rounded-xl border border-gray-200 bg-white py-1.5 pl-8 pr-3 text-xs text-gray-700 shadow-2xs placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            />
                        </div>

                        <!-- View Switch: Grid vs Table -->
                        <div class="flex items-center rounded-xl border border-gray-200 bg-gray-50/80 p-1 dark:border-gray-700 dark:bg-gray-900/50">
                            <button
                                type="button"
                                @click="switchViewMode('grid')"
                                :class="[
                                    'flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs font-bold transition',
                                    viewMode === 'grid'
                                        ? 'bg-white text-indigo-600 shadow-2xs dark:bg-gray-800 dark:text-indigo-400'
                                        : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                                ]"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                                Kalender
                            </button>

                            <button
                                type="button"
                                @click="switchViewMode('table')"
                                :class="[
                                    'flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs font-bold transition',
                                    viewMode === 'table'
                                        ? 'bg-white text-indigo-600 shadow-2xs dark:bg-gray-800 dark:text-indigo-400'
                                        : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white',
                                ]"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                                Tabel
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAMPILAN 1: GRID KALENDER BULANAN (ELEVATED DESIGN) -->
            <div v-if="viewMode === 'grid'" class="space-y-4">
                <div class="overflow-hidden rounded-2xl border border-gray-200/90 bg-white shadow-sm dark:border-gray-700/80 dark:bg-gray-800">
                    <div class="overflow-x-auto">
                        <div class="min-w-[768px]">
                            <!-- Header Hari: Urutan Minggu s/d Sabtu -->
                            <div
                                class="grid grid-cols-7 border-b border-gray-200 bg-gray-50/90 text-center text-xs font-bold uppercase tracking-wider dark:border-gray-700 dark:bg-gray-900/60"
                                style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr));"
                            >
                                <div class="py-3.5 text-rose-600 dark:text-rose-400 bg-rose-50/50 dark:bg-rose-950/20">Minggu</div>
                                <div class="py-3.5 text-gray-700 dark:text-gray-300">Senin</div>
                                <div class="py-3.5 text-gray-700 dark:text-gray-300">Selasa</div>
                                <div class="py-3.5 text-gray-700 dark:text-gray-300">Rabu</div>
                                <div class="py-3.5 text-gray-700 dark:text-gray-300">Kamis</div>
                                <div class="py-3.5 text-gray-700 dark:text-gray-300">Jum'at</div>
                                <div class="py-3.5 text-rose-600 dark:text-rose-400 bg-rose-50/50 dark:bg-rose-950/20 border-l border-gray-200/60 dark:border-gray-700/60">Sabtu</div>
                            </div>

                            <!-- Grid Badan Kalender -->
                            <div
                                class="grid grid-cols-7 gap-px bg-gray-200/80 dark:bg-gray-700/60"
                                style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr));"
                            >
                        <!-- Hari Bulan Sebelumnya (Leading Faded Days) -->
                        <div
                            v-for="prevDayNumber in leadingTrailingDays"
                            :key="'prev-' + prevDayNumber"
                            class="min-h-[125px] bg-gray-50/40 p-3 select-none dark:bg-gray-900/30"
                        >
                            <span class="inline-block text-xs font-medium text-gray-300 dark:text-gray-600">
                                {{ prevDayNumber }}
                            </span>
                        </div>

                        <!-- Kartu Tanggal Bulan Berjalan -->
                        <div
                            v-for="day in days"
                            :key="day.id"
                            @click="openEditDayModal(day)"
                            :class="[
                                'group relative flex flex-col justify-between min-h-[125px] cursor-pointer p-3 transition-all duration-150',
                                day.day_of_week >= 6
                                    ? 'bg-slate-50/70 hover:bg-slate-100/80 dark:bg-gray-800/60 dark:hover:bg-gray-800'
                                    : 'bg-white hover:bg-indigo-50/30 dark:bg-gray-800 dark:hover:bg-gray-750',
                                day.is_overridden ? 'border-t-2 border-t-amber-400 dark:border-t-amber-500' : '',
                                isToday(day.date)
                                    ? 'ring-2 ring-inset ring-indigo-500 bg-indigo-50/20 dark:bg-indigo-950/30 shadow-xs'
                                    : '',
                            ]"
                        >
                            <!-- Header Kartu: Nomor Tanggal & Indikator -->
                            <div>
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <!-- Date Badge -->
                                        <span
                                            :class="[
                                                'flex h-7 w-7 items-center justify-center rounded-xl text-xs font-black transition-transform duration-150 group-hover:scale-105',
                                                isToday(day.date)
                                                    ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/30 ring-2 ring-indigo-200 dark:ring-indigo-900'
                                                    : day.is_working_day
                                                      ? 'text-gray-900 dark:text-gray-100 bg-gray-100/60 dark:bg-gray-700/50'
                                                      : 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/50',
                                            ]"
                                        >
                                            {{ day.day }}
                                        </span>

                                        <span
                                            v-if="isToday(day.date)"
                                            class="rounded-md bg-indigo-100 px-1.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wide text-indigo-700 dark:bg-indigo-950/80 dark:text-indigo-300"
                                        >
                                            Hari Ini
                                        </span>
                                    </div>

                                    <!-- Status / Override Indicators -->
                                    <div class="flex items-center gap-1">
                                        <span
                                            v-if="day.is_overridden"
                                            class="inline-flex items-center gap-0.5 rounded-md bg-amber-100 px-1.5 py-0.5 text-[9px] font-bold text-amber-800 ring-1 ring-amber-300/60 dark:bg-amber-950/60 dark:text-amber-300 dark:ring-amber-700"
                                            title="Diubah secara manual oleh pengguna"
                                        >
                                            <svg class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
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
                                            'flex items-center gap-1.5 rounded-lg border px-2 py-1.5 text-xs font-medium shadow-2xs transition group-hover:shadow-xs',
                                            getTypeBadgeInfo(day).pill,
                                        ]"
                                        :title="day.name"
                                    >
                                        <span
                                            :class="[
                                                'h-2 w-2 shrink-0 rounded-full ring-2 ring-white/80 dark:ring-gray-900',
                                                getTypeBadgeInfo(day).dot,
                                            ]"
                                        />
                                        <span class="truncate font-bold tracking-tight">
                                            {{ day.name }}
                                        </span>
                                    </div>

                                    <!-- Hari Kerja Biasa -->
                                    <div
                                        v-else-if="day.is_working_day"
                                        class="flex items-center gap-1.5 py-1 text-[11px] text-gray-400 dark:text-gray-500"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500/80"></span>
                                        <span class="truncate">Hari Kerja Normal</span>
                                    </div>

                                    <!-- Weekend Biasa Tanpa Libur Khusus -->
                                    <div
                                        v-else
                                        class="flex items-center gap-1.5 py-1 text-[11px] text-gray-400 dark:text-gray-500"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-slate-300 dark:bg-gray-600"></span>
                                        <span class="truncate">{{ day.day_name }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Kartu: Info Updater -->
                            <div class="mt-3 flex items-center justify-between border-t border-gray-100/80 pt-1.5 text-[10px] text-gray-400 dark:border-gray-700/50 dark:text-gray-500">
                                <span class="font-medium truncate max-w-[120px]" :title="day.updater ? day.updater.name : 'Sistem Bawaan'">
                                    {{ day.updater ? day.updater.name : 'Sistem' }}
                                </span>

                                <span v-if="!day.updater" class="text-[9px] text-gray-300 dark:text-gray-600">
                                    Default
                                </span>
                            </div>
                        </div>

                        <!-- Hari Bulan Berikutnya (Trailing Faded Days) -->
                        <div
                            v-for="nextDayNumber in trailingNextDays"
                            :key="'next-' + nextDayNumber"
                            class="min-h-[125px] bg-gray-50/40 p-3 select-none dark:bg-gray-900/30"
                        >
                            <span class="inline-block text-xs font-medium text-gray-300 dark:text-gray-600">
                                {{ nextDayNumber }}
                            </span>
                        </div>
                    </div>
                        </div>
                    </div>
                </div>

                <!-- PETUNJUK WARNA (COLOR LEGEND BAR) -->
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200/80 bg-white p-4 text-xs text-gray-600 shadow-xs dark:border-gray-700/80 dark:bg-gray-800 dark:text-gray-300">
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Petunjuk Warna:
                        </span>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-emerald-200 dark:ring-emerald-950"></span>
                            <span>Hari Kerja</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-rose-500 ring-2 ring-rose-200 dark:ring-rose-950"></span>
                            <span>Libur Nasional</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-500 ring-2 ring-amber-200 dark:ring-amber-950"></span>
                            <span>Cuti Bersama</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-purple-500 ring-2 ring-purple-200 dark:ring-purple-950"></span>
                            <span>Libur Khusus</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-cyan-500 ring-2 ring-cyan-200 dark:ring-cyan-950"></span>
                            <span>Kerja Pengganti</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-slate-300 dark:bg-gray-600"></span>
                            <span>Akhir Pekan</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="inline-flex items-center rounded-md bg-amber-100 px-1 py-0.5 text-[9px] font-bold text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                Manual
                            </span>
                            <span>Override Manual</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAMPILAN 2: TABEL RINCIAN HARI -->
            <div v-else class="overflow-hidden rounded-2xl border border-gray-200/90 bg-white shadow-xs dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50/90 text-left text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="py-4 pl-4 pr-3 sm:pl-6">Tanggal & Hari</th>
                                <th scope="col" class="px-3 py-4">Status</th>
                                <th scope="col" class="px-3 py-4">Tipe Kalender</th>
                                <th scope="col" class="px-3 py-4">Keterangan</th>
                                <th scope="col" class="px-3 py-4">Sumber Data</th>
                                <th scope="col" class="px-3 py-4">Diubah Oleh</th>
                                <th scope="col" class="px-3 py-4">Waktu Update</th>
                                <th scope="col" class="relative py-4 pl-3 pr-4 sm:pr-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white text-xs dark:divide-gray-700 dark:bg-gray-800">
                            <tr
                                v-for="day in days"
                                :key="day.id"
                                :class="[
                                    'transition hover:bg-gray-50/80 dark:hover:bg-gray-700/40',
                                    isToday(day.date) ? 'bg-indigo-50/30 dark:bg-indigo-950/20' : '',
                                ]"
                            >
                                <!-- Tanggal & Hari -->
                                <td class="whitespace-nowrap py-3.5 pl-4 pr-3 sm:pl-6">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            :class="[
                                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-xs font-black shadow-2xs',
                                                day.is_working_day
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                                    : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300',
                                            ]"
                                        >
                                            {{ day.day }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                                {{ formatDateIndo(day.date) }}
                                                <span
                                                    v-if="isToday(day.date)"
                                                    class="rounded-md bg-indigo-100 px-1.5 py-0.2 text-[9px] font-bold text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300"
                                                >
                                                    Hari Ini
                                                </span>
                                            </div>
                                            <div class="text-[11px] font-medium text-gray-400 dark:text-gray-500">
                                                {{ day.day_name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="whitespace-nowrap px-3 py-3.5">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold shadow-2xs',
                                            day.is_working_day
                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800'
                                                : 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'h-2 w-2 rounded-full',
                                                day.is_working_day ? 'bg-emerald-500' : 'bg-rose-500',
                                            ]"
                                        />
                                        {{ day.is_working_day ? 'Hari Kerja' : 'Hari Libur' }}
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
                                        <span :class="['h-1.5 w-1.5 rounded-full', getTypeBadgeInfo(day).dot]" />
                                        {{ getTypeBadgeInfo(day).text }}
                                    </span>
                                </td>

                                <!-- Keterangan -->
                                <td class="px-3 py-3.5 text-gray-700 dark:text-gray-300 max-w-xs">
                                    <div class="truncate font-medium" :title="day.name">
                                        {{ day.name || '-' }}
                                    </div>
                                </td>

                                <!-- Sumber Data -->
                                <td class="whitespace-nowrap px-3 py-3.5 text-gray-500 dark:text-gray-400">
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
                                <td class="whitespace-nowrap px-3 py-3.5 text-gray-600 dark:text-gray-300">
                                    <span v-if="day.updater" class="font-medium text-gray-900 dark:text-white">
                                        {{ day.updater.name }}
                                    </span>
                                    <span v-else class="text-gray-400 italic text-[11px]">
                                        Sistem Bawaan
                                    </span>
                                </td>

                                <!-- Waktu Update -->
                                <td class="whitespace-nowrap px-3 py-3.5 text-[11px] text-gray-500 dark:text-gray-400">
                                    {{ formatDateTimeIndo(day.updated_at) }}
                                </td>

                                <!-- Aksi -->
                                <td class="whitespace-nowrap py-3.5 pl-3 pr-4 sm:pr-6 text-right">
                                    <button
                                        type="button"
                                        @click="openEditDayModal(day)"
                                        class="inline-flex items-center gap-1 rounded-xl border border-gray-200 bg-white px-3 py-1 text-xs font-semibold text-gray-700 shadow-2xs hover:bg-gray-50 hover:text-indigo-600 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                    >
                                        <svg class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
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

        <!-- ================= MODAL: EDIT STATUS HARI KALENDER ================= -->
        <Modal :show="showEditModal" @close="showEditModal = false" max-width="lg">
            <div class="p-6">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                Atur Status Hari Kalender
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400" v-if="activeDay">
                                {{ activeDay.day_name }}, {{ formatDateIndo(activeDay.date) }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showEditModal = false"
                        class="rounded-xl p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="submitEditDay" class="mt-5 space-y-5">
                    <!-- Pilihan Status Kerja vs Libur -->
                    <div>
                        <InputLabel value="Tentukan Status Hari Ini" class="mb-2 font-bold" />
                        <div class="grid grid-cols-2 gap-3">
                            <label
                                :class="[
                                    'flex cursor-pointer items-center gap-3 rounded-2xl border p-4 transition-all',
                                    editForm.is_working_day
                                        ? 'border-emerald-500 bg-emerald-50/50 ring-2 ring-emerald-500/20 dark:border-emerald-500/80 dark:bg-emerald-950/30'
                                        : 'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800',
                                ]"
                            >
                                <input
                                    type="radio"
                                    :value="true"
                                    v-model="editForm.is_working_day"
                                    class="h-4 w-4 text-emerald-600 focus:ring-emerald-500"
                                />
                                <div>
                                    <span class="block text-xs font-bold text-gray-900 dark:text-white">
                                        Hari Kerja
                                    </span>
                                    <span class="block text-[11px] text-gray-500 dark:text-gray-400">
                                        Operasional aktif normal
                                    </span>
                                </div>
                            </label>

                            <label
                                :class="[
                                    'flex cursor-pointer items-center gap-3 rounded-2xl border p-4 transition-all',
                                    !editForm.is_working_day
                                        ? 'border-rose-500 bg-rose-50/50 ring-2 ring-rose-500/20 dark:border-rose-500/80 dark:bg-rose-950/30'
                                        : 'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800',
                                ]"
                            >
                                <input
                                    type="radio"
                                    :value="false"
                                    v-model="editForm.is_working_day"
                                    class="h-4 w-4 text-rose-600 focus:ring-rose-500"
                                />
                                <div>
                                    <span class="block text-xs font-bold text-rose-600 dark:text-rose-400">
                                        Hari Libur
                                    </span>
                                    <span class="block text-[11px] text-gray-500 dark:text-gray-400">
                                        Libur / cuti / akhir pekan
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Input Keterangan / Nama Libur -->
                    <div>
                        <InputLabel for="day_name" value="Keterangan / Nama Libur / Catatan Tambahan" />
                        <TextInput
                            id="day_name"
                            type="text"
                            v-model="editForm.name"
                            class="mt-1 block w-full rounded-xl text-xs"
                            placeholder="Contoh: Libur Cuti Bersama Perusahaan, Hari Pengganti Lembur, dll."
                        />
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                            Opsional untuk hari kerja biasa, sangat disarankan bila ditetapkan sebagai hari libur khusus.
                        </p>
                    </div>

                    <!-- Audit Info Banner -->
                    <div v-if="activeDay && activeDay.updater" class="rounded-xl border border-amber-200 bg-amber-50/80 p-3.5 text-xs dark:border-amber-800/50 dark:bg-amber-950/30">
                        <div class="flex items-start gap-2.5">
                            <svg class="h-4 w-4 shrink-0 text-amber-600 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div class="space-y-0.5 text-amber-900 dark:text-amber-200 text-[11px]">
                                <p>
                                    Terakhir diubah oleh: <strong class="font-bold">{{ activeDay.updater.name }}</strong>
                                </p>
                                <p class="text-[10px] text-amber-600 dark:text-amber-400">
                                    Pada: {{ formatDateTimeIndo(activeDay.updated_at) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
                        <SecondaryButton type="button" @click="showEditModal = false" class="rounded-xl">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="editForm.processing" class="rounded-xl">
                            Simpan Perubahan
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- ================= MODAL: SINKRONISASI API TANGGAL MERAH ================= -->
        <Modal :show="showSyncModal" @close="showSyncModal = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 dark:bg-blue-950/80 dark:text-blue-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Sinkronisasi API Tanggal Merah
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Sumber API: <code class="rounded bg-gray-100 px-1 py-0.5 font-mono text-[10px] text-gray-700 dark:bg-gray-800 dark:text-gray-300">https://tanggalmerah.upset.dev</code>
                        </p>
                    </div>
                </div>

                <div class="mt-4 space-y-3 text-xs text-gray-600 dark:text-gray-300">
                    <p>
                        Sistem akan mengambil data resmi hari libur nasional serta cuti bersama dari API dan secara otomatis memperbarui status hari kerja menjadi hari libur.
                    </p>
                    <div class="rounded-xl bg-blue-50/80 p-3 text-[11px] text-blue-800 dark:bg-blue-950/40 dark:text-blue-300">
                        Nama akun Anda akan dicatat pada riwayat audit sebagai pengguna yang mengeksekusi sinkronisasi ini.
                    </div>
                </div>

                <form @submit.prevent="submitSync" class="mt-5 space-y-4">
                    <div>
                        <InputLabel for="sync_year" value="Pilih Tahun Kalender untuk Disinkronkan" />
                        <select
                            id="sync_year"
                            v-model="syncForm.year"
                            class="mt-1 block w-full rounded-xl border border-gray-200 bg-white py-2 pl-3 pr-8 text-xs font-semibold text-gray-900 shadow-2xs focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-700 dark:text-white"
                        >
                            <option v-for="y in yearsList" :key="y" :value="y">
                                Tahun {{ y }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
                        <SecondaryButton type="button" @click="showSyncModal = false" class="rounded-xl">
                            Batal
                        </SecondaryButton>
                        <PrimaryButton type="submit" :disabled="syncForm.processing" class="flex items-center gap-1.5 rounded-xl">
                            <svg v-if="syncForm.processing" class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            Mulai Sinkronisasi
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- ================= MODAL: RESET DEFAULT KALENDER ================= -->
        <Modal :show="showResetModal" @close="showResetModal = false" max-width="md">
            <div class="p-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-600 dark:bg-amber-950/80 dark:text-amber-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Reset Kalender ke Default
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Kembalikan konfigurasi tahun {{ selectedYear }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 space-y-2 text-xs text-gray-600 dark:text-gray-300">
                    <p>
                        Tindakan ini akan mengatur ulang semua hari pada tahun terpilih menjadi:
                    </p>
                    <ul class="list-disc pl-5 space-y-1 text-[11px] text-gray-500 dark:text-gray-400">
                        <li><strong>Senin s/d Jumat</strong>: Hari Kerja</li>
                        <li><strong>Sabtu & Minggu</strong>: Hari Libur (Akhir Pekan)</li>
                        <li>Menghapus seluruh status override manual & hasil sinkronisasi API.</li>
                    </ul>
                </div>

                <form @submit.prevent="submitReset" class="mt-5 space-y-4">
                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
                        <SecondaryButton type="button" @click="showResetModal = false" class="rounded-xl">
                            Batal
                        </SecondaryButton>
                        <DangerButton type="submit" :disabled="resetForm.processing" class="rounded-xl">
                            Ya, Reset Sekarang
                        </DangerButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
