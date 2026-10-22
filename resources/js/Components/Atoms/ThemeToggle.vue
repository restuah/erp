<script setup>
import { useTheme } from '@/Composables/useTheme';

defineProps({
    showLabel: {
        type: Boolean,
        default: false,
    },
});

const { isDark, toggleTheme } = useTheme();
</script>

<template>
    <button
        type="button"
        @click="toggleTheme"
        :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
        :aria-label="
            isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'
        "
        class="shadow-xs group relative inline-flex cursor-pointer items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[38px] sm:min-w-[38px] rounded-lg border border-slate-200/90 bg-white p-2 text-slate-500 transition-all duration-200 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-200"
    >
        <!-- Sun Icon (shown when dark mode is active) -->
        <svg
            v-if="isDark"
            class="h-5 w-5 text-amber-400 transition-transform duration-300 ease-out group-hover:rotate-45"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
        >
            <circle
                cx="12"
                cy="12"
                r="4"
                fill="currentColor"
                fill-opacity="0.2"
            ></circle>
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
            />
        </svg>

        <!-- Moon Icon (shown when light mode is active) -->
        <svg
            v-else
            class="h-5 w-5 text-slate-600 transition-transform duration-300 ease-out group-hover:-rotate-12 dark:text-slate-300"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
            />
        </svg>

        <span
            v-if="showLabel"
            class="ms-2 text-xs font-semibold text-slate-700 dark:text-slate-300"
        >
            {{ isDark ? 'Terang' : 'Gelap' }}
        </span>
    </button>
</template>
