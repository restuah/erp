<script setup>
import { useTheme } from '@/Composables/useTheme';

defineProps({
    showLabel: {
        type: Boolean,
        default: false,
    },
    variant: {
        type: String,
        default: 'button', // 'button' or 'compact'
    },
});

const { isDark, toggleTheme } = useTheme();
</script>

<template>
    <button
        type="button"
        @click="toggleTheme"
        :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
        :aria-label="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
        class="group relative inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white p-2 text-gray-500 shadow-sm transition-all duration-200 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/40 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-700/70 dark:hover:text-gray-200"
    >
        <!-- Sun Icon (shown when isDark is true, clicking will switch to Light) -->
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

        <!-- Moon Icon (shown when isDark is false, clicking will switch to Dark) -->
        <svg
            v-else
            class="h-5 w-5 text-slate-600 transition-transform duration-300 ease-out group-hover:-rotate-12 dark:text-gray-300"
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
            class="ms-2 text-xs font-medium text-gray-700 dark:text-gray-300"
        >
            {{ isDark ? 'Light' : 'Dark' }}
        </span>
    </button>
</template>
