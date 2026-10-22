<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Icon from '../Atoms/Icon.vue';

const page = usePage();
const flash = computed(() => page.props.flash || {});

const showSuccess = ref(false);
const showError = ref(false);
let timer = null;

watch(
    () => flash.value,
    (newVal) => {
        if (newVal?.success) {
            showSuccess.value = true;
            if (timer) clearTimeout(timer);
            timer = setTimeout(() => {
                showSuccess.value = false;
            }, 4000);
        }
        if (newVal?.error) {
            showError.value = true;
            if (timer) clearTimeout(timer);
            timer = setTimeout(() => {
                showError.value = false;
            }, 6000);
        }
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <div
        class="pointer-events-none fixed end-4 top-4 z-50 flex w-full max-w-sm flex-col gap-2 px-4"
    >
        <!-- Success Alert -->
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showSuccess && flash.success"
                class="pointer-events-auto flex items-center justify-between rounded-xl border border-emerald-200 bg-white p-3.5 shadow-lg dark:border-emerald-800/60 dark:bg-slate-800"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                    >
                        <Icon name="check" class="h-4 w-4" />
                    </div>
                    <div>
                        <p
                            class="text-xs font-bold text-slate-900 dark:text-white"
                        >
                            Berhasil
                        </p>
                        <p class="text-xs text-slate-600 dark:text-slate-300">
                            {{ flash.success }}
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="showSuccess = false"
                    class="rounded-lg p-1 text-slate-400 hover:text-slate-600 focus:outline-none dark:hover:text-slate-200"
                >
                    <Icon name="close" class="h-4 w-4" />
                </button>
            </div>
        </Transition>

        <!-- Error Alert -->
        <Transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showError && flash.error"
                class="pointer-events-auto flex items-center justify-between rounded-xl border border-rose-200 bg-white p-3.5 shadow-lg dark:border-rose-800/60 dark:bg-slate-800"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400"
                    >
                        <Icon name="alert" class="h-4 w-4" />
                    </div>
                    <div>
                        <p
                            class="text-xs font-bold text-slate-900 dark:text-white"
                        >
                            Terjadi Kesalahan
                        </p>
                        <p class="text-xs text-slate-600 dark:text-slate-300">
                            {{ flash.error }}
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="showError = false"
                    class="rounded-lg p-1 text-slate-400 hover:text-slate-600 focus:outline-none dark:hover:text-slate-200"
                >
                    <Icon name="close" class="h-4 w-4" />
                </button>
            </div>
        </Transition>
    </div>
</template>
