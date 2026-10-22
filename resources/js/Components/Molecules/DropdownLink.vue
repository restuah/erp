<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2.5 text-start text-xs font-medium text-slate-700 transition duration-150 ease-in-out hover:bg-slate-50 focus:bg-slate-50 focus:outline-none dark:text-slate-300 dark:hover:bg-slate-700/60 dark:focus:bg-slate-700/60"
    >
        <slot />
    </Link>
</template>
