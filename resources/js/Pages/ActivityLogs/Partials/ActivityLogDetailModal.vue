<script setup>
import { computed, ref } from 'vue';
import Button from '@/Components/Atoms/Button.vue';
import Modal from '@/Components/Atoms/Modal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    log: {
        type: Object,
        default: null,
    },
    eventsMap: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(['close']);

const activeDetailTab = ref('changes');
const copiedJson = ref(false);
const copiedId = ref(false);

const closeModal = () => {
    emit('close');
};

const copyRawJson = async () => {
    if (!props.log) return;
    try {
        await navigator.clipboard.writeText(JSON.stringify(props.log, null, 2));
        copiedJson.value = true;
        setTimeout(() => {
            copiedJson.value = false;
        }, 2000);
    } catch {
        // clipboard unavailable
    }
};

const copyLogId = async (id) => {
    try {
        await navigator.clipboard.writeText(id);
        copiedId.value = true;
        setTimeout(() => {
            copiedId.value = false;
        }, 2000);
    } catch {
        // clipboard unavailable
    }
};

const formatDateTime = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    }).format(date);
};

const timeAgo = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);

    if (seconds < 60) return 'Baru saja';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes} menit lalu`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours} jam lalu`;
    const days = Math.floor(hours / 24);
    if (days < 30) return `${days} hari lalu`;
    return '';
};

const getEventBadgeClass = (event) => {
    switch (event) {
        case 'created':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800';
        case 'updated':
            return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800';
        case 'deleted':
            return 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800';
        case 'force_deleted':
            return 'bg-red-100 text-red-800 border-red-300 dark:bg-red-950 dark:text-red-300 dark:border-red-800 font-semibold';
        case 'restored':
        case 'bulk_restore':
            return 'bg-teal-50 text-teal-700 border-teal-200 dark:bg-teal-950/60 dark:text-teal-300 dark:border-teal-800';
        case 'login':
            return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800';
        case 'logout':
            return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800';
        case 'failed_login':
            return 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/60 dark:text-red-300 dark:border-red-800 font-semibold';
        case 'password_reset':
            return 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800';
        default:
            return 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700';
    }
};

const getEventLabel = (event) => {
    return props.eventsMap[event] || event;
};

const diffEntries = computed(() => {
    if (!props.log?.properties) return [];
    const propsData = props.log.properties;

    if (propsData.old && propsData.attributes) {
        const keys = Object.keys(propsData.attributes);
        return keys.map((key) => ({
            field: key,
            old: propsData.old[key],
            new: propsData.attributes[key],
        }));
    }

    if (propsData.attributes) {
        return Object.entries(propsData.attributes).map(([key, val]) => ({
            field: key,
            old: null,
            new: val,
        }));
    }

    return [];
});

const formatPropertyValue = (val) => {
    if (val === null || val === undefined) return '(kosong)';
    if (typeof val === 'boolean') return val ? 'true' : 'false';
    if (typeof val === 'object') return JSON.stringify(val, null, 2);
    if (val === '') return '(string kosong)';
    return String(val);
};
</script>

<template>
    <Modal :show="show" @close="closeModal" max-width="3xl">
        <div v-if="log" class="flex flex-col">
            <!-- Modal Header -->
            <div
                class="flex items-center justify-between border-b border-gray-200/80 bg-white px-6 py-4 dark:border-gray-700/80 dark:bg-gray-800"
            >
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="shadow-2xs flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-700/60 dark:text-gray-300"
                    >
                        <svg
                            class="h-5 w-5"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3
                            class="text-base font-bold tracking-tight text-gray-900 dark:text-white sm:text-lg"
                        >
                            Detail Riwayat Aktivitas
                        </h3>
                        <div
                            class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400"
                        >
                            <span>{{ formatDateTime(log.created_at) }}</span>
                            <span class="text-gray-300 dark:text-gray-600"
                                >•</span
                            >
                            <span
                                class="font-medium text-gray-600 dark:text-gray-300"
                            >
                                {{ timeAgo(log.created_at) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right: Badges & Close Button -->
                <div class="flex shrink-0 items-center gap-2.5 sm:gap-3">
                    <span
                        :class="[
                            'inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold uppercase tracking-wider',
                            getEventBadgeClass(log.event),
                        ]"
                    >
                        {{ getEventLabel(log.event) }}
                    </span>

                    <span
                        class="hidden items-center rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-gray-700 dark:border-gray-700 dark:bg-gray-700/60 dark:text-gray-300 sm:inline-flex"
                    >
                        {{ log.log_name }}
                    </span>

                    <div
                        class="hidden h-5 w-px bg-gray-200 dark:bg-gray-700 sm:block"
                    ></div>

                    <button
                        type="button"
                        @click="closeModal"
                        class="focus:outline-hidden rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 focus:ring-2 focus:ring-blue-500 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                        title="Tutup (Esc)"
                    >
                        <svg
                            class="h-5 w-5"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div
                class="max-h-[75vh] space-y-5 overflow-y-auto bg-slate-50/60 p-6 dark:bg-gray-900/50"
            >
                <!-- SECTION 1: Deskripsi Proses -->
                <div
                    class="shadow-2xs rounded-xl border border-gray-200/80 bg-white p-5 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div
                        class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                    >
                        Deskripsi Proses
                    </div>
                    <div
                        class="mt-1.5 text-base font-semibold leading-relaxed text-gray-900 dark:text-white"
                    >
                        {{ log.description }}
                    </div>
                    <div
                        v-if="log.subject_label"
                        class="mt-3 flex flex-wrap items-center gap-2"
                    >
                        <span
                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-950/60 dark:text-blue-300"
                        >
                            <span class="font-bold">Target:</span>
                            <span>{{ log.subject_label }}</span>
                        </span>
                        <span
                            v-if="log.subject_type"
                            class="font-mono text-[11px] text-gray-400 dark:text-gray-500"
                        >
                            ({{ log.subject_type }})
                        </span>
                    </div>
                </div>

                <!-- SECTION 2: Identitas Pelaksana & Jaringan -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- User Pelaksana -->
                    <div
                        class="shadow-2xs flex items-center gap-3.5 rounded-xl border border-gray-200/80 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400"
                        >
                            <svg
                                class="h-5 w-5"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div
                                class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                            >
                                Dilakukan Oleh
                            </div>
                            <div
                                class="truncate text-sm font-bold text-gray-900 dark:text-white"
                            >
                                {{ log.causer_name }}
                            </div>
                            <div
                                class="truncate text-xs text-gray-500 dark:text-gray-400"
                            >
                                {{ log.causer_email || 'Sistem / Anonim' }}
                            </div>
                        </div>
                    </div>

                    <!-- Detail Jaringan & Perangkat -->
                    <div
                        class="shadow-2xs rounded-xl border border-gray-200/80 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div
                            class="text-[11px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500"
                        >
                            IP Address & Perangkat
                        </div>
                        <div
                            class="mt-1 font-mono text-sm font-semibold text-gray-900 dark:text-white"
                        >
                            {{ log.ip_address || 'Tidak Diketahui' }}
                        </div>
                        <div
                            class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400"
                            :title="log.user_agent"
                        >
                            {{ log.user_agent || 'User-Agent tidak tercatat' }}
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: TAB PERUBAHAN DATA / RAW JSON -->
                <div
                    class="shadow-2xs rounded-xl border border-gray-200/80 bg-white dark:border-gray-700 dark:bg-gray-800"
                >
                    <!-- Tabs Navigation -->
                    <div
                        class="flex items-center justify-between border-b border-gray-100 px-5 pt-3 dark:border-gray-700"
                    >
                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="activeDetailTab = 'changes'"
                                :class="[
                                    'border-b-2 px-4 py-2.5 text-xs font-bold transition-all',
                                    activeDetailTab === 'changes'
                                        ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                                ]"
                            >
                                Perubahan Nilai Data (Diff)
                                <span
                                    v-if="diffEntries.length > 0"
                                    class="ms-1.5 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300"
                                >
                                    {{ diffEntries.length }}
                                </span>
                            </button>
                            <button
                                type="button"
                                @click="activeDetailTab = 'raw'"
                                :class="[
                                    'border-b-2 px-4 py-2.5 text-xs font-bold transition-all',
                                    activeDetailTab === 'raw'
                                        ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                                ]"
                            >
                                Metadata Mentah (JSON)
                            </button>
                        </div>
                    </div>

                    <!-- Content: Diff Tab -->
                    <div v-if="activeDetailTab === 'changes'" class="p-5">
                        <div
                            v-if="
                                log.event === 'updated' &&
                                diffEntries.length > 0
                            "
                            class="space-y-3"
                        >
                            <div
                                class="grid grid-cols-12 gap-3 rounded-lg bg-gray-50 px-4 py-2.5 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:bg-gray-900/60 dark:text-gray-400"
                            >
                                <div class="col-span-3">Kolom / Atribut</div>
                                <div
                                    class="col-span-4 text-rose-600 dark:text-rose-400"
                                >
                                    Nilai Sebelum (Old)
                                </div>
                                <div
                                    class="col-span-5 text-emerald-600 dark:text-emerald-400"
                                >
                                    Nilai Sesudah (New)
                                </div>
                            </div>

                            <div
                                v-for="diff in diffEntries"
                                :key="diff.field"
                                class="dark:hover:bg-gray-750 grid grid-cols-12 gap-3 rounded-lg border border-gray-100 p-3 transition hover:bg-gray-50/50 dark:border-gray-700/60"
                            >
                                <div
                                    class="col-span-3 flex items-center text-xs font-semibold text-gray-800 dark:text-gray-200"
                                >
                                    <span
                                        class="rounded bg-gray-100 px-2 py-1 font-mono text-[11px] text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        {{ diff.field }}
                                    </span>
                                </div>
                                <div
                                    class="col-span-4 rounded-md border border-rose-200/70 bg-rose-50/60 p-2.5 font-mono text-xs text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300"
                                >
                                    <span class="block break-all line-through">
                                        {{ formatPropertyValue(diff.old) }}
                                    </span>
                                </div>
                                <div
                                    class="col-span-5 rounded-md border border-emerald-200/70 bg-emerald-50/60 p-2.5 font-mono text-xs font-bold text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300"
                                >
                                    <span class="block break-all">
                                        {{ formatPropertyValue(diff.new) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else-if="diffEntries.length > 0"
                            class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                        >
                            <table class="w-full text-start text-xs">
                                <thead
                                    class="border-b border-gray-200 bg-gray-50 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-400"
                                >
                                    <tr>
                                        <th class="w-1/3 px-4 py-3 text-start">
                                            Kolom / Atribut
                                        </th>
                                        <th class="w-2/3 px-4 py-3 text-start">
                                            Nilai Tercatat
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-gray-100 font-mono text-xs dark:divide-gray-700"
                                >
                                    <tr
                                        v-for="diff in diffEntries"
                                        :key="diff.field"
                                        class="transition hover:bg-gray-50 dark:hover:bg-gray-700/30"
                                    >
                                        <td
                                            class="px-4 py-3 font-sans font-bold text-gray-800 dark:text-gray-200"
                                        >
                                            <span
                                                class="rounded bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                            >
                                                {{ diff.field }}
                                            </span>
                                        </td>
                                        <td
                                            class="break-all px-4 py-3 font-semibold text-gray-900 dark:text-white"
                                        >
                                            {{ formatPropertyValue(diff.new) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            v-else
                            class="rounded-lg border border-dashed border-gray-200 p-8 text-center text-xs text-gray-400 dark:border-gray-700 dark:text-gray-500"
                        >
                            Tidak ada rincian atribut data spesifik yang diubah
                            pada proses ini.
                        </div>
                    </div>

                    <!-- Content: Raw JSON Tab -->
                    <div v-if="activeDetailTab === 'raw'" class="space-y-3 p-5">
                        <div class="flex justify-end">
                            <button
                                type="button"
                                @click="copyRawJson"
                                class="shadow-2xs inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                            >
                                <svg
                                    v-if="!copiedJson"
                                    class="h-3.5 w-3.5 text-gray-500"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="h-3.5 w-3.5 text-emerald-500"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                {{
                                    copiedJson
                                        ? 'Tersalin ke Clipboard!'
                                        : 'Salin Data JSON'
                                }}
                            </button>
                        </div>
                        <pre
                            class="max-h-72 overflow-x-auto rounded-xl bg-gray-900 p-4 font-mono text-xs leading-relaxed text-emerald-400 shadow-inner"
                        ><code>{{ JSON.stringify(log, null, 2) }}</code></pre>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div
                class="flex items-center justify-between border-t border-gray-200/80 bg-white px-6 py-4 dark:border-gray-700 dark:bg-gray-800"
            >
                <div
                    class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500"
                >
                    <span
                        class="max-w-[260px] truncate font-mono text-[11px]"
                        :title="log.id"
                    >
                        ID: {{ log.id }}
                    </span>
                    <button
                        type="button"
                        @click="copyLogId(log.id)"
                        class="transition hover:text-blue-600"
                        title="Salin ID Log"
                    >
                        <svg
                            class="h-3.5 w-3.5"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                            />
                        </svg>
                    </button>
                    <span
                        v-if="copiedId"
                        class="text-[10px] font-bold text-emerald-600"
                    >
                        Tersalin!
                    </span>
                </div>

                <Button
                    variant="secondary"
                    @click="closeModal"
                    class="px-4 py-2 text-xs font-semibold"
                >
                    Tutup
                </Button>
            </div>
        </div>
    </Modal>
</template>
