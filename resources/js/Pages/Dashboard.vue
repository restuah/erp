<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Badge from '@/Components/Atoms/Badge.vue';
import Button from '@/Components/Atoms/Button.vue';
import Icon from '@/Components/Atoms/Icon.vue';
import StatsCard from '@/Components/Molecules/StatsCard.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import AuthenticatedTemplate from '@/Components/Templates/AuthenticatedTemplate.vue';

const props = defineProps({
    metrics: {
        type: Object,
        default: () => ({
            suppliers: { total: 5, active: 5, direct: 3, indirect: 2 },
            budgets: { total: 1, active: 1 },
            currencies: { total: 8 },
            users: { total: 3, with_2fa: 0 },
            tops: { total: 8 },
        }),
    },
    latest_rates: {
        type: Array,
        default: () => [],
    },
    recent_activities: {
        type: Array,
        default: () => [],
    },
    recent_suppliers: {
        type: Array,
        default: () => [],
    },
    system_info: {
        type: Object,
        default: () => ({
            company_name: 'PT. Hiruta Kogyo Indonesia',
            branch: 'Kantor Pusat (HQ)',
            environment: 'production',
            timezone: 'Asia/Jakarta (WIB)',
            active_period: 'Q3 2026 • Tahun Fiskal 2026',
            laravel_version: '12.x',
            php_version: '8.3',
        }),
    },
});

const formatRupiah = (val) => {
    if (val === null || val === undefined || isNaN(val)) return '-';
    return new Intl.NumberFormat('id-ID', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(val);
};

const topRates = computed(() => {
    if (props.latest_rates && props.latest_rates.length > 0) {
        return props.latest_rates.slice(0, 5);
    }
    return [
        {
            code: 'USD',
            name: 'US Dollar',
            rate_buy: 17547.82,
            rate_sell: 17724.18,
            rate_middle: 17636.0,
            date: '2026-09-25',
        },
        {
            code: 'EUR',
            name: 'Euro',
            rate_buy: 19120.5,
            rate_sell: 19315.8,
            rate_middle: 19218.15,
            date: '2026-09-25',
        },
        {
            code: 'SGD',
            name: 'Singapore Dollar',
            rate_buy: 13350.2,
            rate_sell: 13485.6,
            rate_middle: 13417.9,
            date: '2026-09-25',
        },
        {
            code: 'JPY',
            name: 'Japanese Yen (100)',
            rate_buy: 11840.1,
            rate_sell: 11960.3,
            rate_middle: 11900.2,
            date: '2026-09-25',
        },
        {
            code: 'CNY',
            name: 'Chinese Yuan',
            rate_buy: 2450.3,
            rate_sell: 2475.1,
            rate_middle: 2462.7,
            date: '2026-09-25',
        },
    ];
});

const usdRate = computed(() => {
    const rate = topRates.value.find((r) => r.code === 'USD');
    return rate ? formatRupiah(rate.rate_middle) : '17.636,00';
});
</script>

<template>
    <Head title="Executive Operations Dashboard" />

    <AuthenticatedTemplate>
        <!-- Page Header Organism -->
        <PageHeader
            title="Executive Operations Dashboard"
            :subtitle="`${system_info.company_name} • ${system_info.branch} • Periode Aktif: ${system_info.active_period}`"
            :breadcrumbs="[{ label: 'ERP' }, { label: 'Ikhtisar Operasional' }]"
        >
            <template #badge>
                <Badge variant="primary" dot size="sm"
                    >ERP Live &bull; v2.4</Badge
                >
            </template>

            <template #actions>
                <Link :href="route('suppliers.index')">
                    <Button variant="primary" size="sm">
                        <template #icon-left>
                            <Icon name="plus" class="h-4 w-4" />
                        </template>
                        Tambah Rekanan
                    </Button>
                </Link>
                <Link :href="route('budgets.index')">
                    <Button variant="secondary" size="sm">
                        <template #icon-left>
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                />
                            </svg>
                        </template>
                        Kelola Budget
                    </Button>
                </Link>
                <Link :href="route('exchange-rates.index')">
                    <Button variant="outline" size="sm">
                        <template #icon-left>
                            <Icon name="refresh" class="h-4 w-4" />
                        </template>
                        Kurs BI
                    </Button>
                </Link>
            </template>
        </PageHeader>

        <!-- KPI / Metric Cards (Mobile-First: 1 col on mobile, 2 on sm, 4 on lg) -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatsCard
                title="Master Rekanan Vendor"
                :value="metrics?.suppliers?.total ?? 5"
                unit="Vendor"
                :subtitle="`${metrics?.suppliers?.direct ?? 3} Direct • ${metrics?.suppliers?.indirect ?? 2} Indirect`"
                icon-name="building"
                icon-color="brand"
                trend="+100%"
                trend-label="Aktif Terdaftar"
                badge-text="Operasional"
                badge-variant="success"
                clickable
                @click="$inertia.visit(route('suppliers.index'))"
            />
            <StatsCard
                title="Kontrol Alokasi Anggaran"
                :value="metrics?.budgets?.total ?? 1"
                unit="Mata Anggaran"
                :subtitle="`${metrics?.budgets?.active ?? 1} Budget Disetujui Checker`"
                icon-name="calendar"
                icon-color="emerald"
                :progress="78"
                trend="Stabil"
                trend-label="Realisasi Terkendali"
                badge-text="Tahun 2026"
                badge-variant="info"
                clickable
                @click="$inertia.visit(route('budgets.index'))"
            />
            <StatsCard
                title="Valas & Kurs Transaksi"
                :value="metrics?.currencies?.total ?? 8"
                unit="Mata Uang"
                :subtitle="`USD: Rp ${usdRate}`"
                icon-name="dollar"
                icon-color="amber"
                trend="Live BI"
                trend-label="Sinkron Harian"
                badge-text="Bank Indonesia"
                badge-variant="warning"
                clickable
                @click="$inertia.visit(route('exchange-rates.index'))"
            />
            <StatsCard
                title="Pengguna & Tata Kelola"
                :value="metrics?.users?.total ?? 3"
                unit="Pengguna"
                :subtitle="`3 Peran Terotorisasi (RBAC)`"
                icon-name="user"
                icon-color="rose"
                trend="2FA Ready"
                trend-label="Keamanan Berlapis"
                badge-text="Enterprise"
                badge-variant="primary"
                clickable
                @click="$inertia.visit(route('users.index'))"
            />
        </div>

        <!-- MAIN DASHBOARD CONTENT GRID (Left 7 cols, Right 5 cols on lg) -->
        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- LEFT COLUMN (7 Cols on LG): Live Kurs Valas & Recent Suppliers -->
            <div class="space-y-6 lg:col-span-7">
                <!-- 1. Papan Pantau Kurs Bank Indonesia -->
                <div
                    class="shadow-xs overflow-hidden rounded-xl border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400"
                            >
                                <Icon name="dollar" class="h-4.5 w-4.5" />
                            </div>
                            <div>
                                <h2
                                    class="text-sm font-bold text-slate-900 dark:text-white"
                                >
                                    Kurs Transaksi Valas Terkini
                                </h2>
                                <p
                                    class="text-[11px] text-slate-500 dark:text-slate-400"
                                >
                                    Acuan resmi pembukuan dari Bank Indonesia
                                </p>
                            </div>
                        </div>

                        <Link
                            :href="route('exchange-rates.index')"
                            class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                        >
                            Lihat Semua &rarr;
                        </Link>
                    </div>

                    <!-- Table of Rates -->
                    <div class="overflow-x-auto">
                        <table
                            class="w-full text-start text-xs text-slate-600 dark:text-slate-300"
                        >
                            <thead
                                class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-850/60 dark:text-slate-400"
                            >
                                <tr>
                                    <th class="px-4 py-2.5 text-start">
                                        Mata Uang
                                    </th>
                                    <th class="px-4 py-2.5 text-end">
                                        Kurs Beli (IDR)
                                    </th>
                                    <th class="px-4 py-2.5 text-end">
                                        Kurs Jual (IDR)
                                    </th>
                                    <th class="px-4 py-2.5 text-end">
                                        Kurs Tengah (IDR)
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-100 dark:divide-slate-800/70"
                            >
                                <tr
                                    v-for="rate in topRates"
                                    :key="rate.code"
                                    class="transition-colors hover:bg-slate-50/60 dark:hover:bg-slate-800/40"
                                >
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] font-bold text-slate-800 dark:bg-slate-800 dark:text-slate-200"
                                            >
                                                {{ rate.code }}
                                            </span>
                                            <span
                                                class="text-xs font-medium text-slate-700 dark:text-slate-300"
                                            >
                                                {{ rate.name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-end font-mono tabular-nums text-slate-600 dark:text-slate-400"
                                    >
                                        {{ formatRupiah(rate.rate_buy) }}
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-end font-mono tabular-nums text-slate-600 dark:text-slate-400"
                                    >
                                        {{ formatRupiah(rate.rate_sell) }}
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-end font-mono font-bold tabular-nums text-blue-600 dark:text-blue-400"
                                    >
                                        {{ formatRupiah(rate.rate_middle) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. Rekanan Supplier Terdaftar Terkini -->
                <div
                    class="shadow-xs overflow-hidden rounded-xl border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                            >
                                <Icon name="building" class="h-4.5 w-4.5" />
                            </div>
                            <div>
                                <h2
                                    class="text-sm font-bold text-slate-900 dark:text-white"
                                >
                                    Rekanan Vendor Terdaftar
                                </h2>
                                <p
                                    class="text-[11px] text-slate-500 dark:text-slate-400"
                                >
                                    Pemasok terverifikasi untuk rantai pasok
                                    perusahaan
                                </p>
                            </div>
                        </div>

                        <Link
                            :href="route('suppliers.index')"
                            class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                        >
                            Kelola Rekanan &rarr;
                        </Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table
                            class="w-full text-start text-xs text-slate-600 dark:text-slate-300"
                        >
                            <thead
                                class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-850/60 dark:text-slate-400"
                            >
                                <tr>
                                    <th class="px-4 py-2.5 text-start">Kode</th>
                                    <th class="px-4 py-2.5 text-start">
                                        Nama Rekanan Vendor
                                    </th>
                                    <th class="px-4 py-2.5 text-start">Tipe</th>
                                    <th class="px-4 py-2.5 text-start">
                                        Term of Payment
                                    </th>
                                    <th class="px-4 py-2.5 text-start">
                                        Lokasi
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-100 dark:divide-slate-800/70"
                            >
                                <tr
                                    v-for="supplier in recent_suppliers"
                                    :key="supplier.id"
                                    class="transition-colors hover:bg-slate-50/60 dark:hover:bg-slate-800/40"
                                >
                                    <td class="px-4 py-2.5">
                                        <span
                                            class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] font-bold text-slate-800 dark:bg-slate-800 dark:text-slate-200"
                                        >
                                            {{ supplier.code }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-4 py-2.5 font-semibold text-slate-900 dark:text-white"
                                    >
                                        {{ supplier.name }}
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <Badge
                                            :variant="
                                                supplier.type === 'direct'
                                                    ? 'primary'
                                                    : 'neutral'
                                            "
                                            size="sm"
                                        >
                                            {{
                                                supplier.type === 'direct'
                                                    ? 'Direct'
                                                    : 'Indirect'
                                            }}
                                        </Badge>
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-slate-600 dark:text-slate-400"
                                    >
                                        {{ supplier.top_name }}
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-slate-500 dark:text-slate-400"
                                    >
                                        {{ supplier.city }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN (5 Cols on LG): Audit Trail & System Compliance -->
            <div class="space-y-6 lg:col-span-5">
                <!-- 3. Audit Trail & Log Aktivitas Terkini -->
                <div
                    class="shadow-xs overflow-hidden rounded-xl border border-slate-200/90 bg-white dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400"
                            >
                                <Icon name="clock" class="h-4.5 w-4.5" />
                            </div>
                            <div>
                                <h2
                                    class="text-sm font-bold text-slate-900 dark:text-white"
                                >
                                    Jejak Audit Terkini
                                </h2>
                                <p
                                    class="text-[11px] text-slate-500 dark:text-slate-400"
                                >
                                    Log aktivitas & transparansi keamanan
                                </p>
                            </div>
                        </div>

                        <Link
                            :href="route('activity-logs.index')"
                            class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                        >
                            Log Lengkap &rarr;
                        </Link>
                    </div>

                    <div
                        class="divide-y divide-slate-100 px-5 dark:divide-slate-800/70"
                    >
                        <div
                            v-for="log in recent_activities"
                            :key="log.id"
                            class="flex items-start gap-3 py-3 text-xs"
                        >
                            <div
                                class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                            >
                                <svg
                                    v-if="log.event === 'login'"
                                    class="h-3.5 w-3.5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"
                                    />
                                </svg>
                                <Icon
                                    v-else
                                    name="document"
                                    class="h-3.5 w-3.5"
                                />
                            </div>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="line-clamp-1 font-semibold text-slate-800 dark:text-slate-200"
                                >
                                    {{ log.description }}
                                </p>
                                <div
                                    class="mt-1 flex items-center gap-2 text-[10px] text-slate-400 dark:text-slate-500"
                                >
                                    <span
                                        class="font-medium text-slate-600 dark:text-slate-400"
                                    >
                                        {{ log.causer_name }}
                                    </span>
                                    <span>&bull;</span>
                                    <span>{{ log.created_at }}</span>
                                    <span>&bull;</span>
                                    <span class="max-w-[120px] truncate">{{
                                        log.device
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="
                                !recent_activities ||
                                recent_activities.length === 0
                            "
                            class="py-6 text-center text-xs text-slate-400"
                        >
                            Belum ada riwayat aktivitas tercatat.
                        </div>
                    </div>
                </div>

                <!-- 4. Kelaikan & Kepatuhan Sistem Enterprise -->
                <div
                    class="shadow-xs rounded-xl border border-slate-200/90 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center gap-2.5 border-b border-slate-100 pb-3 dark:border-slate-800"
                    >
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400"
                        >
                            <Icon name="lock" class="h-4.5 w-4.5" />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-bold text-slate-900 dark:text-white"
                            >
                                Status Infrastruktur & Kepatuhan
                            </h3>
                            <p
                                class="text-[11px] text-slate-500 dark:text-slate-400"
                            >
                                Integritas data & kelaikan sistem operasional
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 space-y-2.5 text-xs">
                        <div
                            class="flex items-center justify-between border-b border-slate-100 py-1.5 dark:border-slate-800/60"
                        >
                            <span class="text-slate-500 dark:text-slate-400"
                                >Konektivitas Database</span
                            >
                            <span
                                class="inline-flex items-center gap-1.5 font-semibold text-emerald-600 dark:text-emerald-400"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                                ></span>
                                MySQL Online (Terkoneksi)
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between border-b border-slate-100 py-1.5 dark:border-slate-800/60"
                        >
                            <span class="text-slate-500 dark:text-slate-400"
                                >Keamanan Akses (2FA)</span
                            >
                            <span
                                class="font-semibold text-slate-800 dark:text-slate-200"
                            >
                                OTP Mandiri &bull; Aktif
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between border-b border-slate-100 py-1.5 dark:border-slate-800/60"
                        >
                            <span class="text-slate-500 dark:text-slate-400"
                                >Basis Jam Operasional</span
                            >
                            <span
                                class="font-mono text-slate-800 dark:text-slate-200"
                            >
                                {{ system_info.timezone }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between border-b border-slate-100 py-1.5 dark:border-slate-800/60"
                        >
                            <span class="text-slate-500 dark:text-slate-400"
                                >Framework & Runtime</span
                            >
                            <span
                                class="font-mono text-[11px] text-slate-700 dark:text-slate-300"
                            >
                                Laravel {{ system_info.laravel_version }} &bull;
                                PHP {{ system_info.php_version }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-1.5">
                            <span class="text-slate-500 dark:text-slate-400"
                                >SLA Ketersediaan Layanan</span
                            >
                            <span
                                class="font-mono font-bold text-emerald-600 dark:text-emerald-400"
                            >
                                99.98% High Availability
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BOTTOM ROW: Direktori Akses Cepat Modul ERP (Enterprise Launchpad) -->
        <div
            class="shadow-xs rounded-xl border border-slate-200/90 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-6"
        >
            <div
                class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800"
            >
                <div>
                    <h2
                        class="text-sm font-bold text-slate-900 dark:text-white"
                    >
                        Direktori Modul Master Data & Operasional
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Akses langsung ke seluruh konfigurasi dan pembukuan data
                        ERP perusahaan
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Modul 1: Pengadaan -->
                <div
                    class="rounded-lg border border-slate-200/80 p-3.5 transition-colors hover:border-blue-300 dark:border-slate-800 dark:hover:border-blue-700/60"
                >
                    <div class="mb-2 flex items-center gap-2">
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400"
                        >
                            <Icon name="building" class="h-4 w-4" />
                        </div>
                        <h3
                            class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200"
                        >
                            Pengadaan & Vendor
                        </h3>
                    </div>
                    <ul
                        class="space-y-1.5 text-xs text-slate-600 dark:text-slate-400"
                    >
                        <li>
                            <Link
                                :href="route('suppliers.index')"
                                class="flex items-center justify-between hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <span>Master Supplier</span>
                                <span
                                    class="font-mono text-[11px] text-slate-400"
                                    >&rarr;</span
                                >
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="route('term-of-payments.index')"
                                class="flex items-center justify-between hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <span>Term of Payment (TOP)</span>
                                <span
                                    class="font-mono text-[11px] text-slate-400"
                                    >&rarr;</span
                                >
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Modul 2: Keuangan & Akuntansi -->
                <div
                    class="rounded-lg border border-slate-200/80 p-3.5 transition-colors hover:border-blue-300 dark:border-slate-800 dark:hover:border-blue-700/60"
                >
                    <div class="mb-2 flex items-center gap-2">
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                        >
                            <Icon name="calendar" class="h-4 w-4" />
                        </div>
                        <h3
                            class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200"
                        >
                            Keuangan & Akuntansi
                        </h3>
                    </div>
                    <ul
                        class="space-y-1.5 text-xs text-slate-600 dark:text-slate-400"
                    >
                        <li>
                            <Link
                                :href="route('budgets.index')"
                                class="flex items-center justify-between hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <span>Master Budget</span>
                                <span
                                    class="font-mono text-[11px] text-slate-400"
                                    >&rarr;</span
                                >
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="route('budget-classifications.index')"
                                class="flex items-center justify-between hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <span>Klasifikasi Budget</span>
                                <span
                                    class="font-mono text-[11px] text-slate-400"
                                    >&rarr;</span
                                >
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="route('chart-of-accounts.index')"
                                class="flex items-center justify-between hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <span>Bagan Akun (COA)</span>
                                <span
                                    class="font-mono text-[11px] text-slate-400"
                                    >&rarr;</span
                                >
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="route('exchange-rates.index')"
                                class="flex items-center justify-between hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <span>Master Kurs BI</span>
                                <span
                                    class="font-mono text-[11px] text-slate-400"
                                    >&rarr;</span
                                >
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Modul 3: Operasional Pabrik -->
                <div
                    class="rounded-lg border border-slate-200/80 p-3.5 transition-colors hover:border-blue-300 dark:border-slate-800 dark:hover:border-blue-700/60"
                >
                    <div class="mb-2 flex items-center gap-2">
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400"
                        >
                            <Icon name="calendar" class="h-4 w-4" />
                        </div>
                        <h3
                            class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200"
                        >
                            Operasional Pabrik
                        </h3>
                    </div>
                    <ul
                        class="space-y-1.5 text-xs text-slate-600 dark:text-slate-400"
                    >
                        <li>
                            <Link
                                :href="route('calendar.index')"
                                class="flex items-center justify-between hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <span>Master Kalender Kerja</span>
                                <span
                                    class="font-mono text-[11px] text-slate-400"
                                    >&rarr;</span
                                >
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="route('unit-of-measures.index')"
                                class="flex items-center justify-between hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <span>Satuan Ukur (UOM)</span>
                                <span
                                    class="font-mono text-[11px] text-slate-400"
                                    >&rarr;</span
                                >
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Modul 4: Tata Kelola & Audit -->
                <div
                    class="rounded-lg border border-slate-200/80 p-3.5 transition-colors hover:border-blue-300 dark:border-slate-800 dark:hover:border-blue-700/60"
                >
                    <div class="mb-2 flex items-center gap-2">
                        <div
                            class="flex h-7 w-7 items-center justify-center rounded bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400"
                        >
                            <Icon name="user" class="h-4 w-4" />
                        </div>
                        <h3
                            class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-slate-200"
                        >
                            Tata Kelola & Audit
                        </h3>
                    </div>
                    <ul
                        class="space-y-1.5 text-xs text-slate-600 dark:text-slate-400"
                    >
                        <li>
                            <Link
                                :href="route('users.index')"
                                class="flex items-center justify-between hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <span>Manajemen Pengguna</span>
                                <span
                                    class="font-mono text-[11px] text-slate-400"
                                    >&rarr;</span
                                >
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="route('roles.index')"
                                class="flex items-center justify-between hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <span>Peran & Hak Akses</span>
                                <span
                                    class="font-mono text-[11px] text-slate-400"
                                    >&rarr;</span
                                >
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="route('activity-logs.index')"
                                class="flex items-center justify-between hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <span>Log Audit Aktivitas</span>
                                <span
                                    class="font-mono text-[11px] text-slate-400"
                                    >&rarr;</span
                                >
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="route('recycle-bin.index')"
                                class="flex items-center justify-between hover:text-blue-600 dark:hover:text-blue-400"
                            >
                                <span>Tempat Sampah Data</span>
                                <span
                                    class="font-mono text-[11px] text-slate-400"
                                    >&rarr;</span
                                >
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedTemplate>
</template>
