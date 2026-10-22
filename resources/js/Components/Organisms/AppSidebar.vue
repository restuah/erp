<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';
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
const roles = computed(() => user.value.roles || []);
const permissions = computed(() => user.value.permissions || []);
const isSuperadmin = computed(() => roles.value.includes('Superadmin'));

// Authorization helper functions
const can = (permission) => {
    if (!permission) return true;
    if (isSuperadmin.value) return true;
    return permissions.value.includes(permission);
};

const hasRole = (role) => {
    if (!role) return true;
    if (isSuperadmin.value) return true;
    return roles.value.includes(role);
};

// Route check helpers - Master
const isProcurementActive = computed(() => {
    return (
        route().current('suppliers.*') ||
        route().current('term-of-payments.*')
    );
});

const isFinanceActive = computed(() => {
    return (
        route().current('budgets.*') ||
        route().current('chart-of-accounts.*') ||
        route().current('exchange-rates.*') ||
        route().current('currencies.*')
    );
});

const isOperationsActive = computed(() => {
    return route().current('calendar.*');
});

const isSupportingActive = computed(() => {
    return (
        route().current('unit-of-measures.*') ||
        route().current('budget-classifications.*')
    );
});

const isMasterActive = computed(() => {
    return (
        isProcurementActive.value ||
        isFinanceActive.value ||
        isOperationsActive.value ||
        isSupportingActive.value
    );
});

// Route check helpers - Miscellaneous (Tata Kelola & Audit)
const isGovernanceActive = computed(() => {
    return (
        route().current('users.*') ||
        route().current('roles.*') ||
        route().current('permissions.*')
    );
});

const isAuditActive = computed(() => {
    return (
        route().current('activity-logs.*') ||
        route().current('recycle-bin.*')
    );
});

const isMiscellaneousActive = computed(() => {
    return isGovernanceActive.value || isAuditActive.value;
});

// Accordion open/collapse states
const openMenus = reactive({
    master: false,
    procurement: false,
    finance: false,
    operations: false,
    supporting: false,
    miscellaneous: false,
    governance: false,
    audit: false,
});

// Auto-expand active groups based on active route
const syncOpenMenusWithRoute = () => {
    if (isMasterActive.value) openMenus.master = true;
    if (isProcurementActive.value) openMenus.procurement = true;
    if (isFinanceActive.value) openMenus.finance = true;
    if (isOperationsActive.value) openMenus.operations = true;
    if (isSupportingActive.value) openMenus.supporting = true;

    if (isMiscellaneousActive.value) openMenus.miscellaneous = true;
    if (isGovernanceActive.value) openMenus.governance = true;
    if (isAuditActive.value) openMenus.audit = true;
};

syncOpenMenusWithRoute();

watch(
    () => page.url,
    () => {
        syncOpenMenusWithRoute();
    },
);

const toggleMenu = (key) => {
    openMenus[key] = !openMenus[key];
};

// 3-Level Menu Structure with Permission & Role Filtering
const menuStructure = computed(() => {
    return {
        master: {
            title: 'Master Data',
            icon: 'database',
            active: isMasterActive.value,
            groups: [
                {
                    key: 'procurement',
                    title: 'Pengadaan & Rekanan',
                    icon: 'truck',
                    active: isProcurementActive.value,
                    children: [
                        {
                            title: 'Master Supplier',
                            href: route('suppliers.index'),
                            active: route().current('suppliers.*'),
                            permission: 'suppliers.view',
                        },
                        {
                            title: 'Term of Payment (TOP)',
                            href: route('term-of-payments.index'),
                            active: route().current('term-of-payments.*'),
                            permission: 'term_of_payments.view',
                        },
                    ].filter((item) => can(item.permission) && hasRole(item.role)),
                },
                {
                    key: 'finance',
                    title: 'Keuangan & Akuntansi',
                    icon: 'calculator',
                    active: isFinanceActive.value,
                    children: [
                        {
                            title: 'Master Budget',
                            href: route('budgets.index'),
                            active: route().current('budgets.*'),
                            permission: 'budgets.view',
                        },
                        {
                            title: 'Bagan Akun (COA)',
                            href: route('chart-of-accounts.index'),
                            active: route().current('chart-of-accounts.*'),
                            permission: 'coa.view',
                        },
                        {
                            title: 'Master Kurs (BI)',
                            href: route('exchange-rates.index'),
                            active: route().current('exchange-rates.*'),
                            permission: 'exchange_rates.view',
                        },
                        {
                            title: 'Master Mata Uang',
                            href: route('currencies.index'),
                            active: route().current('currencies.*'),
                            permission: 'currencies.view',
                        },
                    ].filter((item) => can(item.permission) && hasRole(item.role)),
                },
                {
                    key: 'operations',
                    title: 'Operasional Pabrik',
                    icon: 'calendar',
                    active: isOperationsActive.value,
                    children: [
                        {
                            title: 'Master Kalender',
                            href: route('calendar.index'),
                            active: route().current('calendar.*'),
                            permission: 'calendar.view',
                        },
                    ].filter((item) => can(item.permission) && hasRole(item.role)),
                },
                {
                    key: 'supporting',
                    title: 'Data Pendukung',
                    icon: 'layers',
                    active: isSupportingActive.value,
                    children: [
                        {
                            title: 'Satuan Ukur (UOM)',
                            href: route('unit-of-measures.index'),
                            active: route().current('unit-of-measures.*'),
                            permission: 'uom.view',
                        },
                        {
                            title: 'Klasifikasi Budget',
                            href: route('budget-classifications.index'),
                            active: route().current('budget-classifications.*'),
                            permission: 'budget_classifications.view',
                        },
                    ].filter((item) => can(item.permission) && hasRole(item.role)),
                },
            ].filter((group) => group.children.length > 0),
        },
        miscellaneous: {
            title: 'Miscellaneous',
            icon: 'cog',
            active: isMiscellaneousActive.value,
            groups: [
                {
                    key: 'governance',
                    title: 'Tata Kelola',
                    icon: 'shield',
                    active: isGovernanceActive.value,
                    children: [
                        {
                            title: 'Pengguna',
                            href: route('users.index'),
                            active: route().current('users.*'),
                            permission: 'users.view',
                        },
                        {
                            title: 'Peran (Role)',
                            href: route('roles.index'),
                            active: route().current('roles.*'),
                            permission: 'roles.view',
                        },
                        {
                            title: 'Izin Akses (Permission)',
                            href: route('permissions.index'),
                            active: route().current('permissions.*'),
                            permission: 'permissions.view',
                        },
                    ].filter((item) => can(item.permission) && hasRole(item.role)),
                },
                {
                    key: 'audit',
                    title: 'Audit & Log',
                    icon: 'clock',
                    active: isAuditActive.value,
                    children: [
                        {
                            title: 'Log Audit Aktivitas',
                            href: route('activity-logs.index'),
                            active: route().current('activity-logs.*'),
                            permission: 'activity_logs.view',
                        },
                        {
                            title: 'Tempat Sampah',
                            href: route('recycle-bin.index'),
                            active: route().current('recycle-bin.*'),
                            role: 'Superadmin',
                        },
                    ].filter((item) => can(item.permission) && hasRole(item.role)),
                },
            ].filter((group) => group.children.length > 0),
        },
    };
});

// Dropdown sections to render
const dropdownSections = computed(() => {
    return [
        {
            key: 'master',
            label: 'Data Induk (Master)',
            dropdown: menuStructure.value.master,
        },
        {
            key: 'miscellaneous',
            label: 'Sistem & Lainnya',
            dropdown: menuStructure.value.miscellaneous,
        },
    ].filter((s) => s.dropdown.groups.length > 0);
});
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
                class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-contain px-3 py-3.5"
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

                <!-- 3-LEVEL ACCORDION SECTIONS (Master Data & Miscellaneous) -->
                <div
                    v-for="section in dropdownSections"
                    :key="section.key"
                    class="space-y-1"
                >
                    <div
                        v-if="!collapsed"
                        class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500"
                    >
                        {{ section.label }}
                    </div>

                    <!-- DESKTOP EXPANDED & MOBILE: FULL ACCORDION -->
                    <div v-if="!collapsed" class="space-y-1">
                        <!-- Level 1 Trigger: Master Data / Miscellaneous -->
                        <button
                            type="button"
                            @click="toggleMenu(section.key)"
                            :class="[
                                'group flex w-full items-center justify-between rounded-lg px-3 py-2 text-[13px] font-semibold transition-all duration-150',
                                section.dropdown.active
                                    ? 'bg-blue-50/80 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300'
                                    : 'text-slate-700 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-slate-800/60 dark:hover:text-slate-100',
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    :class="[
                                        'shrink-0 transition-colors',
                                        section.dropdown.active
                                            ? 'text-blue-600 dark:text-blue-400'
                                            : 'text-slate-400 group-hover:text-slate-700 dark:text-slate-400 dark:group-hover:text-slate-200',
                                    ]"
                                >
                                    <Icon :name="section.dropdown.icon" class="h-4.5 w-4.5" />
                                </span>
                                <span>{{ section.dropdown.title }}</span>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <span
                                    v-if="section.dropdown.active && !openMenus[section.key]"
                                    class="h-1.5 w-1.5 rounded-full bg-blue-600 dark:bg-blue-400"
                                />
                                <Icon
                                    name="chevron-down"
                                    :class="[
                                        'h-4 w-4 text-slate-400 transition-transform duration-200',
                                        openMenus[section.key]
                                            ? 'rotate-180 text-blue-600 dark:text-blue-400'
                                            : 'rotate-0',
                                    ]"
                                />
                            </div>
                        </button>

                        <!-- Level 2 Sub-dropdowns Container -->
                        <div
                            v-show="openMenus[section.key]"
                            class="ms-3.5 space-y-1 border-s border-slate-200/80 ps-2.5 my-1.5 dark:border-slate-800"
                        >
                            <div
                                v-for="group in section.dropdown.groups"
                                :key="group.key"
                                class="space-y-0.5"
                            >
                                <!-- Level 2 Trigger -->
                                <button
                                    type="button"
                                    @click="toggleMenu(group.key)"
                                    :class="[
                                        'group flex w-full items-center justify-between rounded-md px-2.5 py-1.5 text-xs font-semibold transition-all duration-150',
                                        group.active
                                            ? 'bg-blue-50/60 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300'
                                            : 'text-slate-600 hover:bg-slate-100/70 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/50 dark:hover:text-slate-200',
                                    ]"
                                >
                                    <div class="flex items-center gap-2 truncate">
                                        <Icon
                                            :name="group.icon"
                                            :class="[
                                                'h-3.5 w-3.5 shrink-0 transition-colors',
                                                group.active
                                                    ? 'text-blue-600 dark:text-blue-400'
                                                    : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-500 dark:group-hover:text-slate-300',
                                            ]"
                                        />
                                        <span class="truncate">{{ group.title }}</span>
                                    </div>
                                    <Icon
                                        name="chevron-down"
                                        :class="[
                                            'h-3.5 w-3.5 shrink-0 text-slate-400 transition-transform duration-200',
                                            openMenus[group.key]
                                                ? 'rotate-180 text-blue-600 dark:text-blue-400'
                                                : 'rotate-0',
                                        ]"
                                    />
                                </button>

                                <!-- Level 3 Children Links -->
                                <div
                                    v-show="openMenus[group.key]"
                                    class="ms-2.5 space-y-0.5 border-s border-slate-200/60 ps-2 my-1 dark:border-slate-800/80"
                                >
                                    <Link
                                        v-for="child in group.children"
                                        :key="child.href"
                                        :href="child.href"
                                        :class="[
                                            'group flex items-center gap-2 rounded-md px-2 py-1.5 text-xs font-medium transition-all duration-150',
                                            child.active
                                                ? 'border border-blue-200/70 bg-blue-50/90 font-semibold text-blue-700 shadow-2xs dark:border-blue-900/40 dark:bg-blue-950/70 dark:text-blue-300'
                                                : 'border border-transparent text-slate-600 hover:bg-slate-100/60 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/40 dark:hover:text-slate-200',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'h-1.5 w-1.5 shrink-0 rounded-full transition-all',
                                                child.active
                                                    ? 'scale-125 bg-blue-600 dark:bg-blue-400'
                                                    : 'bg-slate-300 group-hover:bg-slate-400 dark:bg-slate-600 dark:group-hover:bg-slate-500',
                                            ]"
                                        />
                                        <span class="truncate">{{ child.title }}</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DESKTOP COLLAPSED: COMPACT ICON WITH HOVER FLYOUT -->
                    <div v-else class="relative group">
                        <button
                            type="button"
                            :class="[
                                'relative flex w-full items-center justify-center rounded-lg p-2 transition-all',
                                section.dropdown.active
                                    ? 'bg-blue-50/90 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300'
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800',
                            ]"
                            :title="section.dropdown.title"
                        >
                            <Icon :name="section.dropdown.icon" class="h-5 w-5" />
                            <span
                                v-if="section.dropdown.active"
                                class="absolute -start-1 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-blue-600 dark:bg-blue-400"
                            />
                        </button>

                        <!-- Floating Flyout Menu on Hover -->
                        <div
                            class="invisible absolute start-full top-0 z-50 ms-2 max-h-[85vh] w-64 overflow-y-auto rounded-xl border border-slate-200/90 bg-white p-3 opacity-0 shadow-xl transition-all duration-200 group-hover:visible group-hover:opacity-100 dark:border-slate-800 dark:bg-slate-900"
                        >
                            <div
                                class="mb-2 flex items-center justify-between border-b border-slate-100 px-1 pb-1.5 dark:border-slate-800"
                            >
                                <span class="text-xs font-bold text-slate-900 dark:text-white">
                                    {{ section.dropdown.title }}
                                </span>
                                <span
                                    class="text-[10px] font-semibold uppercase text-blue-600 dark:text-blue-400"
                                >
                                    ERP
                                </span>
                            </div>

                            <div
                                v-for="group in section.dropdown.groups"
                                :key="group.key"
                                class="mb-3 last:mb-0"
                            >
                                <div
                                    class="flex items-center gap-1.5 px-1 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500"
                                >
                                    <Icon :name="group.icon" class="h-3.5 w-3.5" />
                                    <span>{{ group.title }}</span>
                                </div>
                                <div class="mt-0.5 space-y-0.5">
                                    <Link
                                        v-for="child in group.children"
                                        :key="child.href"
                                        :href="child.href"
                                        :class="[
                                            'flex items-center gap-2 rounded-md px-2 py-1.5 text-xs font-medium transition-colors',
                                            child.active
                                                ? 'bg-blue-50/90 font-semibold text-blue-700 dark:bg-blue-950/70 dark:text-blue-300'
                                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200',
                                        ]"
                                    >
                                        <span
                                            :class="[
                                                'h-1.5 w-1.5 shrink-0 rounded-full',
                                                child.active
                                                    ? 'bg-blue-600 dark:bg-blue-400'
                                                    : 'bg-slate-300 dark:bg-slate-600',
                                            ]"
                                        />
                                        <span class="truncate">{{ child.title }}</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
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
