<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ApplicationLogo from '../Atoms/ApplicationLogo.vue';
import Avatar from '../Atoms/Avatar.vue';
import Icon from '../Atoms/Icon.vue';
import SidebarLink from '../Molecules/SidebarLink.vue';

defineProps({
    mobileOpen: {
        type: Boolean,
        default: false,
    },
    collapsed: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['update:mobileOpen', 'close']);

const page = usePage();
const user = computed(
    () => page.props.auth?.user || { name: 'User', email: '' },
);
</script>

<template>
    <div>
        <!-- MOBILE BACKDROP OVERLAY (< lg) -->
        <Transition
            enter-active-class="transition-opacity ease-linear duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity ease-linear duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="mobileOpen"
                @click="
                    $emit('update:mobileOpen', false);
                    $emit('close');
                "
                class="backdrop-blur-xs fixed inset-0 z-40 bg-slate-950/60 lg:hidden"
            />
        </Transition>

        <!-- SIDEBAR CONTAINER -->
        <aside
            :class="[
                // Mobile Slide-over
                'fixed inset-y-0 start-0 z-50 flex h-full flex-col border-r border-slate-200/90 bg-white transition-all duration-300 ease-in-out dark:border-slate-800 dark:bg-slate-900 lg:static lg:z-auto lg:h-full lg:shrink-0',
                mobileOpen
                    ? 'translate-x-0'
                    : '-translate-x-full lg:translate-x-0',
                // Desktop Width
                collapsed ? 'lg:w-20' : 'lg:w-64',
                'w-72 shadow-xl lg:shadow-none',
            ]"
        >
            <!-- Header / Logo -->
            <div
                class="flex h-14 shrink-0 items-center justify-between border-b border-slate-100 px-4 dark:border-slate-800"
            >
                <Link
                    :href="route('dashboard')"
                    class="flex items-center gap-3 overflow-hidden focus:outline-none"
                >
                    <div
                        class="shadow-xs flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white dark:bg-blue-600"
                    >
                        <ApplicationLogo class="h-5 w-5 fill-current" />
                    </div>
                    <div
                        v-if="!collapsed"
                        class="flex flex-col transition-opacity duration-200"
                    >
                        <span
                            class="text-sm font-bold tracking-tight text-slate-900 dark:text-white"
                        >
                            PT. Mandiri Jaya
                        </span>
                        <span
                            class="text-[10px] font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400"
                        >
                            Enterprise ERP &bull; HQ
                        </span>
                    </div>
                </Link>

                <!-- Mobile Close Button -->
                <button
                    type="button"
                    @click="
                        $emit('update:mobileOpen', false);
                        $emit('close');
                    "
                    class="cursor-pointer rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 focus:outline-none dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200 lg:hidden"
                    title="Tutup Menu"
                >
                    <Icon name="close" class="h-5 w-5" />
                </button>
            </div>

            <!-- Navigation Links -->
            <nav
                class="min-h-0 flex-1 space-y-5 overflow-y-auto overscroll-contain px-3 py-3.5"
            >
                <!-- SECTION 1: IKHTISAR UTAMA -->
                <div>
                    <div
                        v-if="!collapsed"
                        class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500"
                    >
                        Ikhtisar Utama
                    </div>
                    <div class="space-y-0.5">
                        <SidebarLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                            :collapsed="collapsed"
                            title="Dashboard"
                        >
                            <template #icon>
                                <Icon name="dashboard" class="h-4.5 w-4.5" />
                            </template>
                            Dashboard
                        </SidebarLink>
                    </div>
                </div>

                <!-- SECTION 2: PENGADAAN & REKANAN -->
                <div>
                    <div
                        v-if="!collapsed"
                        class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500"
                    >
                        Pengadaan & Rekanan
                    </div>
                    <div class="space-y-0.5">
                        <SidebarLink
                            :href="route('suppliers.index')"
                            :active="route().current('suppliers.*')"
                            :collapsed="collapsed"
                            title="Master Supplier"
                        >
                            <template #icon>
                                <Icon name="truck" class="h-4.5 w-4.5" />
                            </template>
                            Master Supplier
                        </SidebarLink>

                        <SidebarLink
                            :href="route('term-of-payments.index')"
                            :active="route().current('term-of-payments.*')"
                            :collapsed="collapsed"
                            title="Term of Payment (TOP)"
                        >
                            <template #icon>
                                <Icon name="credit-card" class="h-4.5 w-4.5" />
                            </template>
                            Term of Payment (TOP)
                        </SidebarLink>
                    </div>
                </div>

                <!-- SECTION 3: KEUANGAN & AKUNTANSI -->
                <div>
                    <div
                        v-if="!collapsed"
                        class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500"
                    >
                        Keuangan & Akuntansi
                    </div>
                    <div class="space-y-0.5">
                        <SidebarLink
                            :href="route('budgets.index')"
                            :active="route().current('budgets.*')"
                            :collapsed="collapsed"
                            title="Master Budget"
                        >
                            <template #icon>
                                <Icon name="calculator" class="h-4.5 w-4.5" />
                            </template>
                            Master Budget
                        </SidebarLink>

                        <SidebarLink
                            :href="route('budget-classifications.index')"
                            :active="
                                route().current('budget-classifications.*')
                            "
                            :collapsed="collapsed"
                            title="Klasifikasi Budget"
                        >
                            <template #icon>
                                <Icon name="tag" class="h-4.5 w-4.5" />
                            </template>
                            Klasifikasi Budget
                        </SidebarLink>

                        <SidebarLink
                            :href="route('chart-of-accounts.index')"
                            :active="route().current('chart-of-accounts.*')"
                            :collapsed="collapsed"
                            title="Bagan Akun (COA)"
                        >
                            <template #icon>
                                <Icon name="book-open" class="h-4.5 w-4.5" />
                            </template>
                            Bagan Akun (COA)
                        </SidebarLink>

                        <SidebarLink
                            :href="route('exchange-rates.index')"
                            :active="route().current('exchange-rates.*')"
                            :collapsed="collapsed"
                            title="Master Kurs"
                        >
                            <template #icon>
                                <Icon name="refresh" class="h-4.5 w-4.5" />
                            </template>
                            Master Kurs (BI)
                        </SidebarLink>

                        <SidebarLink
                            :href="route('currencies.index')"
                            :active="route().current('currencies.*')"
                            :collapsed="collapsed"
                            title="Master Mata Uang"
                        >
                            <template #icon>
                                <Icon name="dollar" class="h-4.5 w-4.5" />
                            </template>
                            Master Mata Uang
                        </SidebarLink>
                    </div>
                </div>

                <!-- SECTION 4: OPERASIONAL PABRIK -->
                <div>
                    <div
                        v-if="!collapsed"
                        class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500"
                    >
                        Operasional Pabrik
                    </div>
                    <div class="space-y-0.5">
                        <SidebarLink
                            :href="route('calendar.index')"
                            :active="route().current('calendar.*')"
                            :collapsed="collapsed"
                            title="Master Kalender"
                        >
                            <template #icon>
                                <Icon name="calendar" class="h-4.5 w-4.5" />
                            </template>
                            Master Kalender
                        </SidebarLink>

                        <SidebarLink
                            :href="route('unit-of-measures.index')"
                            :active="route().current('unit-of-measures.*')"
                            :collapsed="collapsed"
                            title="Satuan Ukur (UOM)"
                        >
                            <template #icon>
                                <Icon name="scale" class="h-4.5 w-4.5" />
                            </template>
                            Satuan Ukur (UOM)
                        </SidebarLink>
                    </div>
                </div>

                <!-- SECTION 5: TATA KELOLA & AUDIT -->
                <div>
                    <div
                        v-if="!collapsed"
                        class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500"
                    >
                        Tata Kelola & Audit
                    </div>
                    <div class="space-y-0.5">
                        <SidebarLink
                            :href="route('users.index')"
                            :active="route().current('users.*')"
                            :collapsed="collapsed"
                            title="Pengguna"
                        >
                            <template #icon>
                                <Icon name="user" class="h-4.5 w-4.5" />
                            </template>
                            Pengguna
                        </SidebarLink>

                        <SidebarLink
                            :href="route('roles.index')"
                            :active="route().current('roles.*')"
                            :collapsed="collapsed"
                            title="Peran & Hak Akses"
                        >
                            <template #icon>
                                <Icon name="shield" class="h-4.5 w-4.5" />
                            </template>
                            Peran (Role)
                        </SidebarLink>

                        <SidebarLink
                            :href="route('permissions.index')"
                            :active="route().current('permissions.*')"
                            :collapsed="collapsed"
                            title="Izin Akses"
                        >
                            <template #icon>
                                <Icon name="lock" class="h-4.5 w-4.5" />
                            </template>
                            Izin Akses (Permission)
                        </SidebarLink>

                        <SidebarLink
                            :href="route('activity-logs.index')"
                            :active="route().current('activity-logs.*')"
                            :collapsed="collapsed"
                            title="Log Aktivitas"
                        >
                            <template #icon>
                                <Icon name="clock" class="h-4.5 w-4.5" />
                            </template>
                            Log Audit Aktivitas
                        </SidebarLink>

                        <SidebarLink
                            :href="route('recycle-bin.index')"
                            :active="route().current('recycle-bin.*')"
                            :collapsed="collapsed"
                            title="Tempat Sampah"
                        >
                            <template #icon>
                                <Icon name="trash" class="h-4.5 w-4.5" />
                            </template>
                            Tempat Sampah
                        </SidebarLink>
                    </div>
                </div>
            </nav>

            <!-- Bottom User & Branch Info -->
            <div
                class="shrink-0 border-t border-slate-100 p-3 dark:border-slate-800"
            >
                <div
                    class="flex items-center gap-3 rounded-lg bg-slate-50/80 p-2 dark:bg-slate-850/80"
                >
                    <Avatar
                        :src="user.avatar_url"
                        :name="user.name"
                        size="sm"
                    />
                    <div v-if="!collapsed" class="min-w-0 flex-1">
                        <p
                            class="truncate text-xs font-bold text-slate-800 dark:text-slate-200"
                        >
                            {{ user.name }}
                        </p>
                        <p
                            class="flex items-center gap-1.5 text-[10px] font-medium text-emerald-600 dark:text-emerald-400"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                            ></span>
                            Terotentikasi &bull; 2FA OK
                        </p>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</template>
