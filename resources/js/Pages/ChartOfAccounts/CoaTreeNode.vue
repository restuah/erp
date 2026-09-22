<script setup>
import { computed } from 'vue';

const props = defineProps({
    node: {
        type: Object,
        required: true,
    },
    expandedKeys: {
        type: Object,
        required: true,
    },
    depth: {
        type: Number,
        default: 0,
    },
    search: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['toggle', 'add-child', 'edit', 'delete']);

const hasChildren = computed(() => {
    return props.node.children && props.node.children.length > 0;
});

const isExpanded = computed(() => {
    return !!props.expandedKeys[props.node.id];
});

const toggleExpand = () => {
    emit('toggle', props.node.id);
};

const matchesSearch = computed(() => {
    if (!props.search) return false;
    const s = props.search.toLowerCase();
    return (
        props.node.account_code.toLowerCase().includes(s) ||
        props.node.account_name.toLowerCase().includes(s)
    );
});
</script>

<template>
    <div class="select-none">
        <!-- Node Row -->
        <div
            class="group flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 py-2.5 px-3 transition-colors hover:bg-indigo-50/40 dark:border-gray-700/50 dark:hover:bg-gray-700/50"
            :class="{
                'bg-amber-50/60 dark:bg-amber-950/30 border-l-2 border-l-amber-500': matchesSearch,
                'font-semibold text-gray-900 dark:text-white': hasChildren || !node.postable,
                'text-gray-700 dark:text-gray-200': !hasChildren && node.postable,
            }"
            :style="{ paddingLeft: `${depth * 28 + 12}px` }"
        >
            <!-- Left Area: Expand Toggle + Icon + Code & Name -->
            <div class="flex items-center gap-2 min-w-0 flex-1">
                <!-- Toggle Button -->
                <button
                    v-if="hasChildren"
                    type="button"
                    @click.stop="toggleExpand"
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-gray-500 hover:bg-gray-200 hover:text-gray-800 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-100 transition-transform"
                    :title="isExpanded ? 'Tutup sub-akun' : 'Buka sub-akun'"
                >
                    <svg
                        class="h-4 w-4 transition-transform duration-200"
                        :class="{ 'rotate-90': isExpanded }"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </button>
                <div v-else class="w-6 shrink-0 flex items-center justify-center text-gray-300 dark:text-gray-600">
                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                </div>

                <!-- Type Icon (Folder for Parent, Document for Postable) -->
                <div
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs"
                    :class="
                        hasChildren || !node.postable
                            ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/70 dark:text-amber-300 dark:border dark:border-amber-800/50'
                            : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/70 dark:text-emerald-300 dark:border dark:border-emerald-800/50'
                    "
                >
                    <!-- Folder Icon -->
                    <svg
                        v-if="hasChildren || !node.postable"
                        class="h-4 w-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"
                        />
                    </svg>
                    <!-- Document Icon -->
                    <svg
                        v-else
                        class="h-4 w-4"
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

                <!-- Account Code -->
                <span
                    class="font-mono text-xs font-bold tracking-tight rounded px-1.5 py-0.5"
                    :class="
                        hasChildren || !node.postable
                            ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border dark:border-indigo-800/50'
                            : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:border dark:border-gray-700'
                    "
                >
                    {{ node.account_code }}
                </span>

                <!-- Account Name -->
                <span class="truncate text-sm" :title="node.account_name">
                    {{ node.account_name }}
                </span>

                <!-- Children Count Badge if any -->
                <span
                    v-if="hasChildren"
                    class="text-[11px] font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-900 dark:border dark:border-gray-700 px-1.5 py-0.5 rounded-full"
                >
                    {{ node.children.length }} sub-akun
                </span>
            </div>

            <!-- Right Area: Badges + Action Buttons -->
            <div class="flex items-center gap-2 shrink-0">
                <!-- Level Badge -->
                <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 dark:bg-gray-900 dark:text-gray-300 dark:border dark:border-gray-700">
                    L{{ node.level }}
                </span>

                <!-- Kategori Badge (BS / PL) -->
                <span
                    class="rounded px-2 py-0.5 text-[11px] font-semibold uppercase"
                    :class="
                        node.kategori === 'bs'
                            ? 'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800/60'
                            : 'bg-teal-50 text-teal-700 border border-teal-200 dark:bg-teal-950/60 dark:text-teal-300 dark:border-teal-800/60'
                    "
                    :title="node.kategori === 'bs' ? 'Neraca (Balance Sheet)' : 'Laba Rugi (Profit & Loss)'"
                >
                    {{ node.kategori === 'bs' ? 'Neraca (BS)' : 'Laba Rugi (PL)' }}
                </span>

                <!-- Jenis Normal Balance (Debit / Credit) -->
                <span
                    class="rounded px-2 py-0.5 text-[11px] font-semibold uppercase"
                    :class="
                        node.jenis === 'debit'
                            ? 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800/60'
                            : 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800/60'
                    "
                >
                    {{ node.jenis }}
                </span>

                <!-- Postable Status Badge -->
                <span
                    v-if="node.postable"
                    class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-medium text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800/60"
                    title="Dapat dipilih dalam penjournalan transaksi"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Postable
                </span>
                <span
                    v-else
                    class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-medium text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800/60"
                    title="Akun induk / header (tidak dapat dijurnal)"
                >
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    Header
                </span>

                <!-- Action Buttons -->
                <div class="flex items-center gap-1 pl-2">
                    <!-- Quick Add Sub-Account -->
                    <button
                        type="button"
                        @click.stop="emit('add-child', node)"
                        class="inline-flex items-center gap-1 rounded-md border border-indigo-200 bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 focus:outline-none dark:border-indigo-700/70 dark:bg-indigo-950/60 dark:text-indigo-300 dark:hover:bg-indigo-900/80 transition-colors"
                        title="Tambah Sub-Akun di bawah akun ini"
                    >
                        <svg class="h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>+ Sub-Akun</span>
                    </button>

                    <!-- Edit Button -->
                    <button
                        type="button"
                        @click.stop="emit('edit', node)"
                        class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-indigo-600 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-indigo-300 transition-colors"
                        title="Edit Akun"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>

                    <!-- Delete Button -->
                    <button
                        type="button"
                        @click.stop="emit('delete', node)"
                        class="rounded-md p-1.5 text-gray-500 hover:bg-red-50 hover:text-red-600 focus:outline-none dark:text-gray-400 dark:hover:bg-red-950/40 dark:hover:text-red-400 transition-colors"
                        :title="hasChildren ? 'Akun memiliki sub-akun dan tidak dapat dihapus' : 'Hapus Akun'"
                    >
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Recursive Children with Guide Line -->
        <div v-if="hasChildren && isExpanded" class="relative">
            <CoaTreeNode
                v-for="child in node.children"
                :key="child.id"
                :node="child"
                :expanded-keys="expandedKeys"
                :depth="depth + 1"
                :search="search"
                @toggle="emit('toggle', $event)"
                @add-child="emit('add-child', $event)"
                @edit="emit('edit', $event)"
                @delete="emit('delete', $event)"
            />
        </div>
    </div>
</template>
