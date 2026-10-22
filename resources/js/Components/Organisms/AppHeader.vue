<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Avatar from '../Atoms/Avatar.vue';
import Icon from '../Atoms/Icon.vue';
import ThemeToggle from '../Atoms/ThemeToggle.vue';
import Dropdown from '../Molecules/Dropdown.vue';
import DropdownLink from '../Molecules/DropdownLink.vue';
import NavbarExchangeRate from '../Molecules/NavbarExchangeRate.vue';

defineProps({
    sidebarCollapsed: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['toggle-mobile-sidebar', 'toggle-collapse']);

const page = usePage();
const user = computed(
    () => page.props.auth?.user || { name: 'User', email: '' },
);
</script>

<template>
    <header
        class="sticky top-0 z-30 flex h-14 shrink-0 items-center justify-between border-b border-slate-200/90 bg-white/95 px-3 backdrop-blur-md transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900/95 sm:px-6"
    >
        <!-- LEFT: Hamburger, Collapse & Quick Context -->
        <div class="flex items-center gap-2 sm:gap-3">
            <!-- Mobile Hamburger Toggle (< lg) -->
            <button
                type="button"
                @click="$emit('toggle-mobile-sidebar')"
                class="flex min-h-[44px] min-w-[44px] sm:min-h-[38px] sm:min-w-[38px] cursor-pointer items-center justify-center rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/20 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200 lg:hidden"
                title="Buka Menu Navigasi"
                aria-label="Buka Menu Navigasi"
            >
                <Icon name="menu" class="h-5 w-5" />
            </button>

            <!-- Desktop Collapse Toggle (>= lg) -->
            <button
                type="button"
                @click="$emit('toggle-collapse')"
                class="hidden min-h-[36px] min-w-[36px] cursor-pointer items-center justify-center rounded-lg p-1.5 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/20 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200 lg:flex"
                :title="
                    sidebarCollapsed ? 'Perluas Sidebar' : 'Ciutkan Sidebar'
                "
                aria-label="Toggle Sidebar"
            >
                <svg
                    class="h-4.5 w-4.5 transition-transform duration-200"
                    :class="sidebarCollapsed ? 'rotate-180' : ''"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
                    />
                </svg>
            </button>

            <!-- Enterprise Global Search Bar Trigger -->
            <div
                class="hidden items-center gap-2 rounded-lg border border-slate-200/90 bg-slate-50/80 px-3 py-1.5 text-xs text-slate-400 transition-colors dark:border-slate-800 dark:bg-slate-850/80 dark:text-slate-500 sm:flex"
            >
                <Icon name="search" class="h-3.5 w-3.5" />
                <span class="text-xs text-slate-400 dark:text-slate-500"
                    >Cari master data, transaksi...</span
                >
                <kbd
                    class="shadow-2xs ms-4 rounded border border-slate-200 bg-white px-1.5 py-0.5 font-mono text-[10px] font-medium text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400"
                >
                    ⌘K
                </kbd>
            </div>
        </div>

        <!-- RIGHT: Live BI Rates, Tenant Status, Theme Toggle, User Profile -->
        <div class="flex items-center gap-1.5 sm:gap-3">
            <!-- Enterprise Environment Status Pill -->
            <div
                class="hidden items-center gap-2 rounded-full border border-slate-200/80 bg-slate-50/80 px-2.5 py-1 text-[11px] font-medium text-slate-600 dark:border-slate-800 dark:bg-slate-850/80 dark:text-slate-300 xl:flex"
            >
                <span
                    class="h-2 w-2 animate-pulse rounded-full bg-emerald-500"
                ></span>
                <span>PT. Hiruta Kogyo Indonesia &bull; HQ</span>
            </div>

            <!-- Exchange Rate Widget -->
            <div class="hidden md:block">
                <NavbarExchangeRate />
            </div>

            <!-- Theme Toggle -->
            <ThemeToggle />

            <!-- User Menu Dropdown -->
            <Dropdown align="right" width="56">
                <template #trigger>
                    <button
                        type="button"
                        class="flex min-h-[44px] sm:min-h-[38px] cursor-pointer items-center gap-2 rounded-lg p-1.5 text-sm font-medium text-slate-700 transition-all duration-150 hover:bg-slate-100 focus:outline-none dark:text-slate-300 dark:hover:bg-slate-800/80"
                    >
                        <Avatar
                            :src="user.avatar_url"
                            :name="user.name"
                            size="sm"
                        />
                        <div class="hidden text-start md:block">
                            <span
                                class="block max-w-[130px] truncate text-xs font-bold text-slate-800 dark:text-slate-200"
                            >
                                {{ user.name }}
                            </span>
                            <span
                                class="block text-[10px] text-slate-500 dark:text-slate-400"
                            >
                                Superadmin
                            </span>
                        </div>
                        <Icon
                            name="chevron-down"
                            class="h-3.5 w-3.5 text-slate-400"
                        />
                    </button>
                </template>

                <template #content>
                    <!-- User Email Header -->
                    <div
                        class="border-b border-slate-100 px-4 py-2.5 dark:border-slate-800"
                    >
                        <p
                            class="text-[11px] font-medium text-slate-500 dark:text-slate-400"
                        >
                            Masuk sebagai:
                        </p>
                        <p
                            class="truncate text-xs font-bold text-slate-900 dark:text-slate-100"
                        >
                            {{ user.email }}
                        </p>
                    </div>

                    <!-- Profile Link -->
                    <DropdownLink :href="route('profile.edit')">
                        <div class="flex items-center gap-2">
                            <Icon name="user" class="h-4 w-4 text-slate-400" />
                            <span>Pengaturan Profil & Keamanan 2FA</span>
                        </div>
                    </DropdownLink>

                    <div
                        class="border-t border-slate-100 dark:border-slate-800"
                    ></div>

                    <!-- Logout Link -->
                    <DropdownLink
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="w-full text-start text-rose-600 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300"
                    >
                        <div class="flex items-center gap-2">
                            <svg
                                class="h-4 w-4 text-rose-500"
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
    </header>
</template>
