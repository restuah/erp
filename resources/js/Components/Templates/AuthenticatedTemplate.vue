<script setup>
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import AppHeader from '../Organisms/AppHeader.vue';
import AppSidebar from '../Organisms/AppSidebar.vue';
import FlashAlerts from '../Organisms/FlashAlerts.vue';

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
        class="flex h-screen overflow-hidden bg-slate-50 font-sans text-slate-800 antialiased transition-colors duration-200 dark:bg-slate-950 dark:text-slate-100"
    >
        <!-- FLASH NOTIFICATIONS TOAST -->
        <FlashAlerts />

        <!-- APP SIDEBAR (Mobile Slideover Drawer + Desktop Collapsible) -->
        <AppSidebar
            v-model:mobile-open="sidebarMobileOpen"
            :collapsed="sidebarCollapsed"
            @close="sidebarMobileOpen = false"
        />

        <!-- MAIN LAYOUT COLUMN -->
        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
            <!-- TOP NAVBAR -->
            <AppHeader
                :sidebar-collapsed="sidebarCollapsed"
                @toggle-mobile-sidebar="sidebarMobileOpen = !sidebarMobileOpen"
                @toggle-collapse="toggleSidebarCollapsed"
            />

            <!-- SCROLLABLE PAGE REGION -->
            <div
                scroll-region
                class="flex min-h-0 flex-1 flex-col overflow-y-auto overscroll-contain"
            >
                <!-- OPTIONAL HEADER SLOT -->
                <div
                    v-if="$slots.header"
                    class="backdrop-blur-xs shrink-0 border-b border-slate-200/90 bg-white/80 px-4 py-4 dark:border-slate-800 dark:bg-slate-900/80 sm:px-6 lg:px-8"
                >
                    <slot name="header" />
                </div>

                <!-- MAIN PAGE CONTENT (Expansive Enterprise Width) -->
                <main
                    class="mx-auto w-full max-w-[1600px] flex-1 p-4 sm:p-6 lg:p-7"
                >
                    <slot />
                </main>

                <!-- ENTERPRISE FOOTER -->
                <footer
                    class="backdrop-blur-xs mt-auto shrink-0 border-t border-slate-200/70 bg-white/40 py-3.5 text-center text-xs text-slate-400 transition-colors dark:border-slate-800/80 dark:bg-slate-900/40 dark:text-slate-500"
                >
                    &copy; {{ new Date().getFullYear() }} PT. Mandiri Jaya Utama
                    &bull; Enterprise Resource Planning (ERP) Suite &bull; v2.4
                </footer>
            </div>
        </div>
    </div>
</template>
