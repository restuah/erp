<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-slate-50 pt-6 transition-colors duration-200 dark:bg-gray-900 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/" class="flex flex-col items-center gap-2">
                <ApplicationLogo
                    class="h-16 w-16 fill-current text-indigo-600 dark:text-indigo-400"
                />
                <span
                    class="text-xl font-bold tracking-tight text-gray-900 dark:text-white"
                    >ERP System</span
                >
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden border border-gray-100 bg-white px-6 py-5 shadow-lg transition-colors duration-200 dark:border-gray-700/60 dark:bg-gray-800 sm:max-w-md sm:rounded-xl"
        >
            <slot />
        </div>
    </div>
</template>
