<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();

// Ambil data kurs dari props global Inertia
const ratesList = computed(() => page.props.navbar_exchange_rates || []);

const selectedCode = ref('USD');
const isSyncing = ref(false);

// Inisialisasi mata uang terpilih (disimpan di localStorage)
onMounted(() => {
    try {
        const saved = localStorage.getItem('erp_navbar_currency');
        if (saved && ratesList.value.some((r) => r.currency_code === saved)) {
            selectedCode.value = saved;
        } else if (ratesList.value.length > 0) {
            const hasUsd = ratesList.value.find((r) => r.currency_code === 'USD');
            selectedCode.value = hasUsd ? 'USD' : ratesList.value[0].currency_code;
        }
    } catch {
        // localStorage not available
    }
});

// Simpan pilihan currency jika berubah
watch(selectedCode, (val) => {
    if (val) {
        try {
            localStorage.setItem('erp_navbar_currency', val);
        } catch {
            // localStorage not available
        }
    }
});

// Kurs mata uang aktif yang sedang dipilih
const currentRate = computed(() => {
    return ratesList.value.find((r) => r.currency_code === selectedCode.value) || null;
});

// Satuan unit kurs (misal 1 atau 100)
const formattedUnit = computed(() => {
    const u = currentRate.value?.unit;
    if (!u) return 1;
    return Number(u) % 1 === 0 ? parseInt(u, 10) : Number(u);
});

// Nilai tukar terformat dalam format Rupiah (contoh: Rp. 16.000,00)
const formattedRate = computed(() => {
    if (!currentRate.value || currentRate.value.rate_middle === null || currentRate.value.rate_middle === undefined) {
        return 'Rp. -';
    }
    return (
        'Rp. ' +
        Number(currentRate.value.rate_middle).toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })
    );
});

// Format teks Last Updated At
const lastUpdatedText = computed(() => {
    if (!currentRate.value) return 'Belum ada data';
    if (currentRate.value.formatted_updated_at) {
        return currentRate.value.formatted_updated_at;
    }
    if (currentRate.value.date) {
        return currentRate.value.date;
    }
    return 'Belum disinkronkan';
});

// Fungsi sinkronisasi manual dari Bank Indonesia
const handleSync = () => {
    if (isSyncing.value) return;

    isSyncing.value = true;
    router.post(
        route('exchange-rates.sync'),
        {
            currency_codes: selectedCode.value ? [selectedCode.value] : undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isSyncing.value = false;
            },
            onError: () => {
                isSyncing.value = false;
            },
        },
    );
};
</script>

<template>
    <div
        v-if="ratesList && ratesList.length > 0"
        class="flex items-center gap-2 rounded-xl border border-gray-200/80 bg-gray-50/70 px-2.5 py-1 shadow-2xs transition-colors duration-150 hover:border-indigo-200 dark:border-gray-700/80 dark:bg-gray-800/80 dark:hover:border-gray-600"
    >
        <!-- Info Kurs: Satuan, Select Currency, Nilai Tukar & Last Updated At -->
        <div class="flex flex-col justify-center leading-tight">
            <!-- Baris 1: [Unit] [Select Currency] = [Nilai Tukar] -->
            <div class="flex items-center gap-1.5 text-xs font-semibold text-gray-800 dark:text-gray-100">
                <!-- Satuan (Contoh: 1 atau 100) -->
                <span class="font-bold tabular-nums text-gray-700 dark:text-gray-300">
                    {{ formattedUnit }}
                </span>

                <!-- Dropdown Select Mata Uang -->
                <div class="relative inline-flex items-center">
                    <select
                        v-model="selectedCode"
                        class="cursor-pointer appearance-none rounded-md border border-gray-300/80 bg-white py-0.5 pe-5 ps-1.5 text-xs font-bold tracking-tight text-indigo-600 shadow-2xs transition-colors hover:border-indigo-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-indigo-300 bg-none [background-image:none]"
                        style="background-image: none !important;"
                        title="Pilih Mata Uang"
                    >
                        <option
                            v-for="c in ratesList"
                            :key="c.currency_code"
                            :value="c.currency_code"
                        >
                            {{ c.currency_code }}
                        </option>
                    </select>
                    <!-- Custom Arrow Icon -->
                    <svg
                        class="pointer-events-none absolute end-1 h-3 w-3 text-gray-400 dark:text-gray-400"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>

                <!-- Tanda '=' -->
                <span class="font-medium text-gray-400 dark:text-gray-500">=</span>

                <!-- Nilai Tukar Rupiah (Contoh: Rp. 16.000,00) -->
                <span class="font-bold tabular-nums text-emerald-600 dark:text-emerald-400">
                    {{ formattedRate }}
                </span>
            </div>

            <!-- Baris 2: Last Updated At -->
            <div
                class="flex items-center gap-1 text-[10px] text-gray-500 dark:text-gray-400"
                :title="`Terakhir diperbarui: ${lastUpdatedText}`"
            >
                <svg
                    class="h-2.5 w-2.5 shrink-0 text-gray-400 dark:text-gray-500"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <span class="hidden sm:inline">last updated at</span>
                <span class="sm:hidden">update:</span>
                <span class="font-medium text-gray-600 dark:text-gray-300">
                    {{ lastUpdatedText }}
                </span>
            </div>
        </div>

        <!-- Tombol Sinkronisasi Manual Bank Indonesia -->
        <button
            type="button"
            @click="handleSync"
            :disabled="isSyncing"
            class="group relative flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 shadow-2xs transition-all duration-150 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-600 active:scale-95 disabled:cursor-not-allowed disabled:opacity-60 dark:border-gray-700 dark:bg-gray-700/80 dark:text-gray-400 dark:hover:border-indigo-500/50 dark:hover:bg-indigo-950/40 dark:hover:text-indigo-300"
            :title="
                isSyncing
                    ? 'Sedang menyinkronkan data kurs dari Bank Indonesia...'
                    : `Sinkronisasi kurs ${selectedCode} secara manual dari Bank Indonesia`
            "
        >
            <svg
                class="h-3.5 w-3.5 transition-transform duration-300"
                :class="{
                    'animate-spin text-indigo-600 dark:text-indigo-400': isSyncing,
                    'group-hover:rotate-45': !isSyncing,
                }"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2.2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                />
            </svg>
        </button>
    </div>
</template>
