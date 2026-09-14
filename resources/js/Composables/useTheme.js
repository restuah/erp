import { ref } from 'vue';

const isDark = ref(false);

export function useTheme() {
    const updateHtmlClass = (dark) => {
        if (typeof document !== 'undefined') {
            if (dark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    };

    const initTheme = () => {
        if (typeof window === 'undefined') return;

        const storedTheme = localStorage.getItem('theme');
        if (storedTheme) {
            isDark.value = storedTheme === 'dark';
        } else {
            const systemPrefersDark = window.matchMedia(
                '(prefers-color-scheme: dark)',
            ).matches;
            isDark.value = systemPrefersDark;
        }
        updateHtmlClass(isDark.value);
    };

    const toggleTheme = () => {
        isDark.value = !isDark.value;
        if (typeof window !== 'undefined') {
            localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
            updateHtmlClass(isDark.value);
        }
    };

    const setTheme = (theme) => {
        isDark.value = theme === 'dark';
        if (typeof window !== 'undefined') {
            localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
            updateHtmlClass(isDark.value);
        }
    };

    // Initialize on first import if in browser
    if (typeof window !== 'undefined') {
        if (document.documentElement.classList.contains('dark')) {
            isDark.value = true;
        } else {
            initTheme();
        }
    }

    return {
        isDark,
        initTheme,
        toggleTheme,
        setTheme,
    };
}
