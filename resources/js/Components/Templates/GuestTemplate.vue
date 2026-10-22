<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '../Atoms/ApplicationLogo.vue';
import ThemeToggle from '../Atoms/ThemeToggle.vue';
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center justify-center bg-slate-50 p-4 transition-colors duration-200 dark:bg-slate-950 sm:p-6"
    >
        <!-- Top Theme Toggle Floating -->
        <div class="absolute end-4 top-4">
            <ThemeToggle />
        </div>

        <div class="w-full sm:max-w-md">
            <div class="mb-6 flex flex-col items-center text-center">
                <Link
                    :href="route('dashboard')"
                    class="flex flex-col items-center gap-2.5 focus:outline-none"
                >
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-600/20 dark:bg-blue-600"
                    >
                        <ApplicationLogo class="h-8 w-8 fill-current" />
                    </div>
                    <div>
                        <span
                            class="text-xl font-bold tracking-tight text-slate-900 dark:text-white"
                        >
                            PT. Hiruta Kogyo Indonesia
                        </span>
                        <p
                            class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400"
                        >
                            Enterprise ERP Suite &bull; v2.4
                        </p>
                    </div>
                </Link>
            </div>

            <div
                class="w-full overflow-hidden rounded-2xl border border-slate-200/90 bg-white p-6 shadow-xl transition-colors duration-200 dark:border-slate-800 dark:bg-slate-900 sm:p-8"
            >
                <slot />
            </div>

            <p
                class="mt-6 text-center text-xs text-slate-400 dark:text-slate-500"
            >
                &copy; {{ new Date().getFullYear() }} PT. Hiruta Kogyo Indonesia &bull; Enterprise Resource Planning
            </p>
        </div>
    </div>
</template>
