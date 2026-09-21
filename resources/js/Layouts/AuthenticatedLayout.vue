<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import SidebarLink from '@/Components/SidebarLink.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import NavbarExchangeRate from '@/Components/NavbarExchangeRate.vue';
import { Link, router } from '@inertiajs/vue3';

// Sidebar states
const sidebarMobileOpen = ref(false);
const sidebarCollapsed = ref(false);

// Load persisted sidebar collapsed state on desktop
onMounted(() => {
    try {
        const savedCollapsed = localStorage.getItem('erp_sidebar_collapsed');
        if (savedCollapsed !== null) {
            sidebarCollapsed.value = savedCollapsed === 'true';
        }
    } catch {
        // localStorage not available
    }
});

const toggleSidebarCollapsed = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    try {
        localStorage.setItem(
            'erp_sidebar_collapsed',
            sidebarCollapsed.value.toString(),
        );
    } catch {
        // localStorage not available
    }
};

// Close mobile sidebar on route navigation
const removeNavigateListener = router.on('navigate', () => {
    sidebarMobileOpen.value = false;
});

onUnmounted(() => {
    removeNavigateListener();
});
</script>

<template>
    <div
        class="flex h-screen overflow-hidden bg-slate-50 font-sans text-slate-800 antialiased transition-colors duration-200 dark:bg-gray-900 dark:text-gray-100"
    >
        <!-- Mobile Sidebar Backdrop Overlay -->
        <Transition
            enter-active-class="transition-opacity ease-linear duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-linear duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="sidebarMobileOpen"
                @click="sidebarMobileOpen = false"
                class="backdrop-blur-xs fixed inset-0 z-40 bg-gray-900/60 lg:hidden"
            />
        </Transition>

        <!-- SIDEBAR -->
        <aside
            :class="[
                // Mobile slide-over
                'fixed inset-y-0 start-0 z-50 flex h-full flex-col border-r border-gray-200 bg-white transition-all duration-300 ease-in-out dark:border-gray-700/70 dark:bg-gray-800 lg:static lg:z-auto lg:h-full lg:shrink-0',
                sidebarMobileOpen
                    ? 'translate-x-0'
                    : '-translate-x-full lg:translate-x-0',
                // Desktop width
                sidebarCollapsed ? 'lg:w-20' : 'lg:w-64',
                'w-72 shadow-xl lg:shadow-none',
            ]"
        >
            <!-- Sidebar Header / Logo -->
            <div
                class="flex h-16 shrink-0 items-center justify-between border-b border-gray-100 px-4 dark:border-gray-700/70"
            >
                <Link
                    :href="route('dashboard')"
                    class="flex items-center gap-3 overflow-hidden focus:outline-none"
                >
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-md shadow-indigo-600/20 dark:bg-indigo-500"
                    >
                        <ApplicationLogo class="h-6 w-6 fill-current" />
                    </div>
                    <div
                        v-if="!sidebarCollapsed"
                        class="flex flex-col transition-opacity duration-200"
                    >
                        <span
                            class="text-base font-bold tracking-tight text-gray-900 dark:text-white"
                        >
                            ERP System
                        </span>
                        <span
                            class="text-[10px] font-medium uppercase tracking-wider text-indigo-600 dark:text-indigo-400"
                        >
                            Enterprise Suite
                        </span>
                    </div>
                </Link>

                <!-- Mobile Close Button -->
                <button
                    type="button"
                    @click="sidebarMobileOpen = false"
                    class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200 lg:hidden"
                    title="Close Sidebar"
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

            <!-- Sidebar Navigation Menu -->
            <nav
                class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain px-3 py-4"
            >
                <!-- SECTION 1: MENU UTAMA -->
                <div>
                    <div
                        v-if="!sidebarCollapsed"
                        class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                    >
                        Menu Utama
                    </div>
                    <div class="space-y-1">
                        <SidebarLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                            :collapsed="sidebarCollapsed"
                            title="Dashboard"
                        >
                            <template #icon>
                                <svg
                                    class="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                                    />
                                </svg>
                            </template>
                            Dashboard
                        </SidebarLink>
                    </div>
                </div>

                <!-- SECTION: MASTER DATA -->
                <div>
                    <div
                        v-if="!sidebarCollapsed"
                        class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                    >
                        Master Data
                    </div>
                    <div class="space-y-1">
                        <SidebarLink
                            :href="route('calendar.index')"
                            :active="route().current('calendar.*')"
                            :collapsed="sidebarCollapsed"
                            title="Master Kalender"
                        >
                            <template #icon>
                                <svg
                                    class="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
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
                            </template>
                            Master Kalender
                        </SidebarLink>

                        <SidebarLink
                            :href="route('currencies.index')"
                            :active="route().current('currencies.*')"
                            :collapsed="sidebarCollapsed"
                            title="Master Mata Uang"
                        >
                            <template #icon>
                                <svg
                                    class="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </template>
                            Master Mata Uang
                        </SidebarLink>

                        <SidebarLink
                            :href="route('exchange-rates.index')"
                            :active="route().current('exchange-rates.*')"
                            :collapsed="sidebarCollapsed"
                            title="Master Kurs"
                        >
                            <template #icon>
                                <svg
                                    class="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"
                                    />
                                </svg>
                            </template>
                            Master Kurs
                        </SidebarLink>
                    </div>
                </div>

                <!-- SECTION 2: OPERASIONAL ERP -->
                <div>
                    <div
                        v-if="!sidebarCollapsed"
                        class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                    >
                        Operasional
                    </div>
                    <div class="space-y-1">
                        <SidebarLink
                            href="#"
                            :collapsed="sidebarCollapsed"
                            badge="Segera"
                            badge-color="indigo"
                            title="Penjualan & Pesanan"
                        >
                            <template #icon>
                                <svg
                                    class="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                            </template>
                            Penjualan
                        </SidebarLink>

                        <SidebarLink
                            href="#"
                            :collapsed="sidebarCollapsed"
                            badge="Segera"
                            badge-color="indigo"
                            title="Inventaris & Stok"
                        >
                            <template #icon>
                                <svg
                                    class="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                    />
                                </svg>
                            </template>
                            Inventaris / Stok
                        </SidebarLink>

                        <SidebarLink
                            href="#"
                            :collapsed="sidebarCollapsed"
                            badge="Segera"
                            badge-color="indigo"
                            title="Pengadaan & Pembelian"
                        >
                            <template #icon>
                                <svg
                                    class="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </template>
                            Pengadaan
                        </SidebarLink>

                        <SidebarLink
                            href="#"
                            :collapsed="sidebarCollapsed"
                            badge="Segera"
                            badge-color="indigo"
                            title="Keuangan & Akuntansi"
                        >
                            <template #icon>
                                <svg
                                    class="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </template>
                            Keuangan
                        </SidebarLink>
                    </div>
                </div>

                <!-- SECTION 3: LAPORAN & ANALISIS -->
                <div>
                    <div
                        v-if="!sidebarCollapsed"
                        class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                    >
                        Analisis
                    </div>
                    <div class="space-y-1">
                        <SidebarLink
                            href="#"
                            :collapsed="sidebarCollapsed"
                            badge="Segera"
                            badge-color="indigo"
                            title="Laporan & Ringkasan"
                        >
                            <template #icon>
                                <svg
                                    class="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                    />
                                </svg>
                            </template>
                            Laporan Bisnis
                        </SidebarLink>
                    </div>
                </div>

                <!-- SECTION: MANAJEMEN AKSES -->
                <div>
                    <div
                        v-if="!sidebarCollapsed"
                        class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                    >
                        Manajemen Akses
                    </div>
                    <div class="space-y-1">
                        <SidebarLink
                            :href="route('users.index')"
                            :active="route().current('users.*')"
                            :collapsed="sidebarCollapsed"
                            title="Pengguna"
                        >
                            <template #icon>
                                <svg
                                    class="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                    />
                                </svg>
                            </template>
                            Pengguna
                        </SidebarLink>

                        <SidebarLink
                            :href="route('roles.index')"
                            :active="route().current('roles.*')"
                            :collapsed="sidebarCollapsed"
                            title="Peran & Hak Akses"
                        >
                            <template #icon>
                                <svg
                                    class="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                    />
                                </svg>
                            </template>
                            Peran & Hak Akses
                        </SidebarLink>

                        <SidebarLink
                            :href="route('permissions.index')"
                            :active="route().current('permissions.*')"
                            :collapsed="sidebarCollapsed"
                            title="Izin Sistem"
                        >
                            <template #icon>
                                <svg
                                    class="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
                                    />
                                </svg>
                            </template>
                            Izin Sistem
                        </SidebarLink>

                        <SidebarLink
                            :href="route('recycle-bin.index')"
                            :active="route().current('recycle-bin.*')"
                            :collapsed="sidebarCollapsed"
                            title="Tempat Sampah"
                        >
                            <template #icon>
                                <svg
                                    class="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                            </template>
                            Tempat Sampah
                        </SidebarLink>
                    </div>
                </div>

                <!-- SECTION 4: PENGATURAN -->
                <div>
                    <div
                        v-if="!sidebarCollapsed"
                        class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                    >
                        Sistem
                    </div>
                    <div class="space-y-1">
                        <SidebarLink
                            :href="route('profile.edit')"
                            :active="route().current('profile.edit')"
                            :collapsed="sidebarCollapsed"
                            title="Profil & Keamanan"
                        >
                            <template #icon>
                                <svg
                                    class="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                    />
                                </svg>
                            </template>
                            Profil Akun
                        </SidebarLink>

                        <SidebarLink
                            :href="route('activity-logs.index')"
                            :active="route().current('activity-logs.*')"
                            :collapsed="sidebarCollapsed"
                            title="Log Aktivitas Sistem"
                        >
                            <template #icon>
                                <svg
                                    class="h-5 w-5"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </template>
                            Log Aktivitas
                        </SidebarLink>
                    </div>
                </div>
            </nav>

            <!-- Sidebar Footer (Compact User Info & Status) -->
            <div
                class="shrink-0 border-t border-gray-100 p-3 dark:border-gray-700/70"
            >
                <div
                    class="flex items-center gap-3 rounded-lg p-2 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    :class="sidebarCollapsed ? 'justify-center' : ''"
                >
                    <div
                        class="relative flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-100 font-semibold text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200"
                    >
                        <img
                            v-if="$page.props.auth.user.avatar_url"
                            :src="$page.props.auth.user.avatar_url"
                            :alt="$page.props.auth.user.name"
                            class="h-full w-full object-cover"
                        />
                        <span v-else>
                            {{
                                $page.props.auth.user.name
                                    .charAt(0)
                                    .toUpperCase()
                            }}
                        </span>
                        <span
                            class="absolute bottom-0 end-0 h-2.5 w-2.5 rounded-full border-2 border-white bg-emerald-500 dark:border-gray-800"
                        ></span>
                    </div>

                    <div v-if="!sidebarCollapsed" class="min-w-0 flex-1">
                        <p
                            class="truncate text-xs font-semibold text-gray-800 dark:text-gray-200"
                        >
                            {{ $page.props.auth.user.name }}
                        </p>
                        <p
                            class="truncate text-[11px] capitalize text-gray-500 dark:text-gray-400"
                        >
                            {{ $page.props.auth.user.roles?.[0] || 'Pengguna' }}
                        </p>
                    </div>

                    <Link
                        v-if="!sidebarCollapsed"
                        :href="route('logout')"
                        method="post"
                        as="button"
                        title="Keluar"
                        class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-red-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-red-400"
                    >
                        <svg
                            class="h-4 w-4"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                            />
                        </svg>
                    </Link>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <div class="flex h-full min-w-0 flex-1 flex-col overflow-hidden">
            <!-- TOP NAVBAR / HEADER -->
            <header
                class="z-20 flex h-16 shrink-0 items-center justify-between border-b border-gray-200 bg-white/95 px-4 backdrop-blur-md transition-colors duration-200 dark:border-gray-700/80 dark:bg-gray-800/95 sm:px-6 lg:px-8"
            >
                <!-- Left: Toggles & Breadcrumb/Context -->
                <div class="flex items-center gap-3">
                    <!-- Mobile Hamburger Button -->
                    <button
                        type="button"
                        @click="sidebarMobileOpen = true"
                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200 lg:hidden"
                        title="Buka Sidebar"
                    >
                        <svg
                            class="h-5 w-5"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>

                    <!-- Desktop Sidebar Collapse Toggle -->
                    <button
                        type="button"
                        @click="toggleSidebarCollapsed"
                        class="hidden rounded-lg p-2 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200 lg:flex"
                        :title="
                            sidebarCollapsed
                                ? 'Perluas Sidebar'
                                : 'Perkecil Sidebar'
                        "
                    >
                        <svg
                            class="h-5 w-5 transition-transform duration-200"
                            :class="sidebarCollapsed ? 'rotate-180' : ''"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
                            />
                        </svg>
                    </button>

                    <!-- Context Indicator -->
                    <div
                        class="hidden items-center gap-2 text-sm text-gray-500 dark:text-gray-400 sm:flex"
                    >
                        <span
                            class="font-medium text-gray-700 dark:text-gray-300"
                            >ERP Portal</span
                        >
                        <span>/</span>
                        <span class="font-normal capitalize">{{
                            route().current() || 'Workspace'
                        }}</span>
                    </div>
                </div>

                <!-- Right: Quick actions, ThemeToggle, Profile Dropdown -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Currency Exchange Rate Widget -->
                    <NavbarExchangeRate />

                    <div class="hidden h-6 w-px bg-gray-200 dark:bg-gray-700 sm:block"></div>

                    <!-- Theme Toggle (Beside Profile) -->
                    <div class="flex items-center">
                        <ThemeToggle />
                    </div>

                    <div class="h-6 w-px bg-gray-200 dark:bg-gray-700"></div>

                    <!-- User Profile Dropdown -->
                    <div class="relative">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="flex items-center gap-2.5 rounded-lg p-1.5 text-sm font-medium text-gray-700 transition-all duration-150 hover:bg-gray-100 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-700/60"
                                >
                                    <div
                                        class="shadow-xs flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-indigo-600 text-xs font-bold text-white dark:bg-indigo-500"
                                    >
                                        <img
                                            v-if="
                                                $page.props.auth.user.avatar_url
                                            "
                                            :src="
                                                $page.props.auth.user.avatar_url
                                            "
                                            :alt="$page.props.auth.user.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <span v-else>
                                            {{
                                                $page.props.auth.user.name
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </span>
                                    </div>
                                    <div class="hidden text-start md:block">
                                        <span
                                            class="block max-w-[130px] truncate text-xs font-semibold text-gray-800 dark:text-gray-200"
                                        >
                                            {{ $page.props.auth.user.name }}
                                        </span>
                                        <span
                                            class="block text-[10px] text-gray-500 dark:text-gray-400"
                                        >
                                            Akun Saya
                                        </span>
                                    </div>
                                    <svg
                                        class="h-4 w-4 text-gray-400 transition-transform duration-150"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <!-- User Email Info -->
                                <div
                                    class="border-b border-gray-100 px-4 py-2.5 dark:border-gray-600"
                                >
                                    <p
                                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                                    >
                                        Masuk sebagai:
                                    </p>
                                    <p
                                        class="truncate text-xs font-semibold text-gray-900 dark:text-gray-100"
                                    >
                                        {{ $page.props.auth.user.email }}
                                    </p>
                                </div>

                                <DropdownLink :href="route('profile.edit')">
                                    <div class="flex items-center gap-2">
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
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                            />
                                        </svg>
                                        <span>Profil Akun</span>
                                    </div>
                                </DropdownLink>

                                <div
                                    class="border-t border-gray-100 dark:border-gray-600"
                                ></div>

                                <DropdownLink
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="w-full text-start text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                >
                                    <div class="flex items-center gap-2">
                                        <svg
                                            class="h-4 w-4 text-red-500"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                            />
                                        </svg>
                                        <span>Keluar (Log Out)</span>
                                    </div>
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE CONTENT REGION -->
            <div
                scroll-region
                class="flex min-h-0 flex-1 flex-col overflow-y-auto overscroll-contain"
            >
                <!-- PAGE HEADING SLOT -->
                <div
                    v-if="$slots.header"
                    class="shadow-xs backdrop-blur-xs shrink-0 border-b border-gray-200/80 bg-white/70 px-4 py-5 dark:border-gray-700/60 dark:bg-gray-800/60 sm:px-6 lg:px-8"
                >
                    <slot name="header" />
                </div>

                <!-- MAIN PAGE CONTENT -->
                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    <!-- Flash Notification Alert: Success -->
                    <div
                        v-if="$page.props.flash?.success"
                        class="shadow-xs mb-6 flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800/50 dark:bg-emerald-950/40 dark:text-emerald-300"
                    >
                        <div class="flex items-center gap-2.5">
                            <svg
                                class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span class="font-medium">{{
                                $page.props.flash.success
                            }}</span>
                        </div>
                    </div>

                    <!-- Flash Notification Alert: Error -->
                    <div
                        v-if="$page.props.flash?.error"
                        class="shadow-xs mb-6 flex items-center justify-between rounded-xl border border-red-200 bg-red-50/90 px-4 py-3 text-sm text-red-800 dark:border-red-800/50 dark:bg-red-950/40 dark:text-red-300"
                    >
                        <div class="flex items-center gap-2.5">
                            <svg
                                class="h-5 w-5 shrink-0 text-red-600 dark:text-red-400"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span class="font-medium">{{
                                $page.props.flash.error
                            }}</span>
                        </div>
                    </div>

                    <slot />
                </main>

                <!-- ERP FOOTER -->
                <footer
                    class="mt-auto shrink-0 border-t border-gray-200/60 py-3 text-center text-xs text-gray-400 transition-colors dark:border-gray-800 dark:text-gray-500"
                >
                    &copy; {{ new Date().getFullYear() }} ERP System. Enterprise
                    Management Platform.
                </footer>
            </div>
        </div>
    </div>
</template>
