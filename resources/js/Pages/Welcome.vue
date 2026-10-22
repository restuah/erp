<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/Atoms/ApplicationLogo.vue';
import Badge from '@/Components/Atoms/Badge.vue';
import Button from '@/Components/Atoms/Button.vue';
import Icon from '@/Components/Atoms/Icon.vue';
import ThemeToggle from '@/Components/Atoms/ThemeToggle.vue';

defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    canRegister: {
        type: Boolean,
        default: false,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
</script>

<template>
    <Head title="Enterprise Portal Gateway - PT. Hiruta Kogyo Indonesia" />

    <div
        class="relative min-h-screen flex flex-col bg-slate-50 font-sans text-slate-800 antialiased transition-colors duration-200 dark:bg-slate-950 dark:text-slate-100"
    >
        <!-- BACKGROUND AMBIENT GRADIENTS -->
        <div
            class="pointer-events-none absolute -top-40 start-1/2 -z-10 h-[500px] w-full max-w-7xl -translate-x-1/2 opacity-30 blur-3xl dark:opacity-20"
            style="background: radial-gradient(circle at 50% 50%, #3B82F6, #1E40AF, transparent 70%);"
        />

        <!-- TOP EXECUTIVE NAVBAR -->
        <header
            class="sticky top-0 z-30 border-b border-slate-200/90 bg-white/90 px-4 py-3 backdrop-blur-md transition-colors dark:border-slate-800 dark:bg-slate-900/90 sm:px-6 lg:px-8"
        >
            <div class="mx-auto flex max-w-[1400px] items-center justify-between">
                <!-- Brand Title -->
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-600/20"
                    >
                        <ApplicationLogo class="h-6 w-6 fill-current" />
                    </div>
                    <div>
                        <span
                            class="text-sm font-bold tracking-tight text-slate-900 dark:text-white sm:text-base"
                        >
                            PT. Hiruta Kogyo Indonesia
                        </span>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-[10px] font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400"
                            >
                                Enterprise ERP Suite &bull; v2.4
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Navigation Controls -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Live Status Pill -->
                    <div
                        class="hidden items-center gap-2 rounded-full border border-slate-200/80 bg-slate-50/80 px-2.5 py-1 text-[11px] font-medium text-slate-600 dark:border-slate-800 dark:bg-slate-850/80 dark:text-slate-300 md:flex"
                    >
                        <span
                            class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"
                        />
                        <span>Sistem Online &bull; HQ Server</span>
                    </div>

                    <!-- Theme Toggle Atom -->
                    <ThemeToggle />

                    <!-- Primary Auth CTA -->
                    <template v-if="canLogin">
                        <Link v-if="isAuthenticated" :href="route('dashboard')">
                            <Button variant="primary" size="sm">
                                <template #icon-left>
                                    <Icon name="dashboard" class="h-4 w-4" />
                                </template>
                                Buka Dashboard
                            </Button>
                        </Link>
                        <Link v-else :href="route('login')">
                            <Button variant="primary" size="sm">
                                <template #icon-left>
                                    <Icon name="lock" class="h-4 w-4" />
                                </template>
                                Masuk ke Sistem
                            </Button>
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- MAIN HERO CONTENT AREA -->
        <main class="mx-auto flex w-full max-w-[1400px] flex-1 flex-col justify-center px-4 py-8 sm:px-6 sm:py-14 lg:px-8">
            <!-- HERO SECTION -->
            <div class="mx-auto max-w-3xl text-center">
                <div class="mb-4 inline-flex items-center gap-2">
                    <Badge variant="primary" dot pulse size="sm">
                        ERP Operasional Manufaktur Terpadu
                    </Badge>
                </div>

                <h1
                    class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-4xl lg:text-5xl"
                >
                    Sentralisasi Operasional & Tata Kelola Perusahaan
                </h1>

                <p
                    class="mt-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300 sm:text-base lg:text-lg"
                >
                    Sistem Enterprise Resource Planning (ERP) generasi baru PT. Hiruta Kogyo Indonesia.
                    Mengintegrasikan pengadaan vendor, kontrol anggaran multi-fiskal, akuntansi valas terverifikasi Bank Indonesia, dan otorisasi berlapis berbasis peran.
                </p>

                <!-- Hero Action Buttons -->
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
                    <Link
                        v-if="!isAuthenticated && canLogin"
                        :href="route('login')"
                        class="w-full sm:w-auto"
                    >
                        <Button variant="primary" size="lg" class="w-full shadow-md shadow-blue-600/20">
                            <template #icon-left>
                                <Icon name="lock" class="h-4.5 w-4.5" />
                            </template>
                            Masuk ke Akun Terdaftar
                            <template #icon-right>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </template>
                        </Button>
                    </Link>

                    <Link
                        v-else-if="isAuthenticated"
                        :href="route('dashboard')"
                        class="w-full sm:w-auto"
                    >
                        <Button variant="primary" size="lg" class="w-full shadow-md shadow-blue-600/20">
                            <template #icon-left>
                                <Icon name="dashboard" class="h-4.5 w-4.5" />
                            </template>
                            Lanjutkan ke Dashboard
                            <template #icon-right>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </template>
                        </Button>
                    </Link>

                    <a
                        href="#modules-overview"
                        class="w-full sm:w-auto"
                    >
                        <Button variant="secondary" size="lg" class="w-full">
                            Pelajari Cakupan Modul
                        </Button>
                    </a>
                </div>
            </div>

            <!-- CORE ENTERPRISE PILLARS GRID -->
            <div
                id="modules-overview"
                class="mt-14 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 sm:mt-16 sm:gap-6"
            >
                <!-- Pillar 1: Pengadaan -->
                <div
                    class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs transition-all hover:border-blue-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-900/60"
                >
                    <div
                        class="mb-3.5 flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400"
                    >
                        <Icon name="building" class="h-5 w-5" />
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Pengadaan & Vendor
                    </h3>
                    <p class="mt-1.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                        Manajemen rekanan direct dan indirect, registrasi Term of Payment (TOP), serta evaluasi kinerja pasokan material manufaktur.
                    </p>
                </div>

                <!-- Pillar 2: Keuangan & Anggaran -->
                <div
                    class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs transition-all hover:border-blue-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-900/60"
                >
                    <div
                        class="mb-3.5 flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                    >
                        <Icon name="calculator" class="h-5 w-5" />
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Keuangan & Valas BI
                    </h3>
                    <p class="mt-1.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                        Kontrol mata anggaran, klasifikasi budget, Bagan Akun (COA), serta sinkronisasi kurs harian resmi Bank Indonesia.
                    </p>
                </div>

                <!-- Pillar 3: Operasional & Organisasi -->
                <div
                    class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs transition-all hover:border-blue-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-900/60"
                >
                    <div
                        class="mb-3.5 flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400"
                    >
                        <Icon name="calendar" class="h-5 w-5" />
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Operasional & Kalender
                    </h3>
                    <p class="mt-1.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                        Struktur hierarki departemen, kalender operasional pabrik, hari kerja nasional, serta integrasi shift kerja.
                    </p>
                </div>

                <!-- Pillar 4: Keamanan & RBAC -->
                <div
                    class="rounded-2xl border border-slate-200/90 bg-white p-5 shadow-xs transition-all hover:border-blue-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-900/60"
                >
                    <div
                        class="mb-3.5 flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400"
                    >
                        <Icon name="lock" class="h-5 w-5" />
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Keamanan & Jejak Audit
                    </h3>
                    <p class="mt-1.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                        Autentikasi dua faktor (2FA OTP), izin Spatie granular, jejak audit mutasi (Activity Log), dan pemulihan Recycle Bin.
                    </p>
                </div>
            </div>
        </main>

        <!-- ENTERPRISE FOOTER -->
        <footer
            class="border-t border-slate-200/80 bg-white/60 py-6 text-center text-xs text-slate-500 backdrop-blur-xs transition-colors dark:border-slate-800/80 dark:bg-slate-900/60 dark:text-slate-400"
        >
            <div class="mx-auto flex max-w-[1400px] flex-col items-center justify-between gap-3 px-4 sm:flex-row sm:px-6 lg:px-8">
                <div>
                    &copy; {{ new Date().getFullYear() }} <strong>PT. Hiruta Kogyo Indonesia</strong> &bull; Enterprise Resource Planning Suite &bull; v2.4
                </div>
                <div class="flex items-center gap-3 font-mono text-[11px] text-slate-400 dark:text-slate-500">
                    <span>Laravel v{{ laravelVersion }}</span>
                    <span>&bull;</span>
                    <span>PHP v{{ phpVersion }}</span>
                    <span>&bull;</span>
                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Production Ready</span>
                </div>
            </div>
        </footer>
    </div>
</template>
