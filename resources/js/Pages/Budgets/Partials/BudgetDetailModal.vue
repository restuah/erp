<script setup>
import Avatar from '@/Components/Atoms/Avatar.vue';
import Badge from '@/Components/Atoms/Badge.vue';
import Button from '@/Components/Atoms/Button.vue';
import Icon from '@/Components/Atoms/Icon.vue';
import ModalDialog from '@/Components/Organisms/ModalDialog.vue';

defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    budget: {
        type: Object,
        default: null,
    },
});

defineEmits(['close', 'edit']);
</script>

<template>
    <ModalDialog
        :show="show"
        max-width="2xl"
        title="Detail Anggaran & Alur Approval"
        subtitle="Rincian informasi budget master dan alur persetujuan"
        @close="$emit('close')"
    >
        <div v-if="budget" class="space-y-5 text-xs">
            <!-- Header Card -->
            <div
                class="flex items-start justify-between gap-3 rounded-xl border border-slate-200/80 bg-slate-50 p-4 dark:border-slate-700/60 dark:bg-slate-800/60"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="font-mono text-xs font-bold text-slate-500 dark:text-slate-400"
                        >
                            {{ budget.code }}
                        </span>
                        <Badge
                            v-if="budget.classification"
                            variant="primary"
                            size="sm"
                        >
                            {{ budget.classification.name }}
                        </Badge>
                    </div>
                    <h3
                        class="mt-1 text-base font-bold text-slate-900 dark:text-white sm:text-lg"
                    >
                        {{ budget.name }}
                    </h3>
                    <p
                        v-if="budget.description"
                        class="mt-1 text-slate-500 dark:text-slate-400"
                    >
                        {{ budget.description }}
                    </p>
                </div>

                <Badge
                    :variant="budget.is_active ? 'success' : 'danger'"
                    dot
                    size="md"
                >
                    {{ budget.is_active ? 'Aktif' : 'Nonaktif' }}
                </Badge>
            </div>

            <!-- PIC Info -->
            <div
                class="rounded-xl border border-slate-200/80 p-4 dark:border-slate-700/60 dark:bg-slate-800/40"
            >
                <span
                    class="mb-2 block text-[11px] font-bold uppercase tracking-wider text-slate-400"
                >
                    Person In Charge (PIC)
                </span>
                <div v-if="budget.pic" class="flex items-center gap-3">
                    <Avatar :name="budget.pic.name" size="sm" />
                    <div>
                        <p class="font-bold text-slate-900 dark:text-white">
                            {{ budget.pic.name }}
                        </p>
                        <p
                            class="text-[11px] text-slate-500 dark:text-slate-400"
                        >
                            {{ budget.pic.email }}
                        </p>
                    </div>
                </div>
                <span v-else class="text-slate-400">Belum ditentukan</span>
            </div>

            <!-- Checker Hierarchy / Approval Flow -->
            <div
                class="rounded-xl border border-slate-200/80 p-4 dark:border-slate-700/60 dark:bg-slate-800/40"
            >
                <span
                    class="mb-3 block text-[11px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400"
                >
                    Alur Persetujuan Bertingkat ({{
                        budget.checkers?.length || 0
                    }}
                    Tingkat)
                </span>

                <div
                    v-if="budget.checkers && budget.checkers.length > 0"
                    class="space-y-3"
                >
                    <div
                        v-for="(checker, idx) in budget.checkers"
                        :key="checker.id || idx"
                        class="flex items-center gap-3 rounded-lg border border-slate-200/60 bg-slate-50 p-3 dark:border-slate-700/60 dark:bg-slate-800/80"
                    >
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white"
                        >
                            {{ idx + 1 }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <span
                                class="block text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400"
                            >
                                {{ checker.role_title || `Tingkat ${idx + 1}` }}
                            </span>
                            <p
                                class="truncate font-semibold text-slate-900 dark:text-white"
                            >
                                {{
                                    checker.user?.name ||
                                    'Pemeriksa Belum Dipilih'
                                }}
                            </p>
                            <p
                                v-if="checker.user?.email"
                                class="truncate text-[11px] text-slate-400"
                            >
                                {{ checker.user.email }}
                            </p>
                        </div>
                    </div>
                </div>
                <span v-else class="text-slate-400"
                    >Belum ada alur persetujuan yang diset.</span
                >
            </div>
        </div>

        <template #footer>
            <Button variant="secondary" size="md" @click="$emit('close')">
                Tutup
            </Button>
            <Button variant="primary" size="md" @click="$emit('edit', budget)">
                <template #icon-left>
                    <Icon name="edit" class="h-4 w-4" />
                </template>
                Ubah Budget
            </Button>
        </template>
    </ModalDialog>
</template>
