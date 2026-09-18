<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Lupa Kata Sandi" />

        <!-- Header above form with Theme Toggle -->
        <div
            class="mb-6 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-700/80"
        >
            <div>
                <h1
                    class="text-lg font-bold tracking-tight text-gray-900 dark:text-white"
                >
                    Lupa Kata Sandi
                </h1>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Sistem Manajemen Terpadu ERP
                </p>
            </div>
            <ThemeToggle />
        </div>

        <div
            class="mb-5 text-sm leading-relaxed text-gray-600 dark:text-gray-300"
        >
            Masukkan alamat email akun ERP Anda yang sudah terdaftar. Kami akan
            mengirimkan tautan reset kata sandi dengan masa berlaku
            <span class="font-semibold text-indigo-600 dark:text-indigo-400"
                >30 menit</span
            >.
        </div>

        <!-- Success notification banner -->
        <div
            v-if="status"
            class="mb-5 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50/80 p-3.5 text-sm text-emerald-800 dark:border-emerald-800/40 dark:bg-emerald-950/40 dark:text-emerald-300"
        >
            <svg
                class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.8"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </svg>
            <div>
                <p class="font-medium text-emerald-900 dark:text-emerald-200">
                    Email Terkirim!
                </p>
                <p
                    class="mt-0.5 text-xs text-emerald-700 dark:text-emerald-300"
                >
                    {{ status }}
                </p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="Alamat Email Terdaftar" />

                <div class="relative mt-1">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"
                            />
                        </svg>
                    </div>
                    <TextInput
                        id="email"
                        type="email"
                        class="block w-full pl-10"
                        v-model="form.email"
                        placeholder="contoh: user@erp.test"
                        required
                        autofocus
                        autocomplete="email"
                    />
                </div>

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="pt-2">
                <PrimaryButton
                    class="w-full justify-center py-2.5 shadow-sm"
                    :class="{
                        'cursor-not-allowed opacity-50': form.processing,
                    }"
                    :disabled="form.processing"
                >
                    <svg
                        v-if="form.processing"
                        class="-ml-1 mr-2 h-4 w-4 animate-spin text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <span v-if="form.processing">Mengirim Tautan...</span>
                    <span v-else>Kirim Tautan Reset Kata Sandi</span>
                </PrimaryButton>
            </div>

            <div
                class="border-t border-gray-100 pt-3 text-center dark:border-gray-700/60"
            >
                <Link
                    :href="route('login')"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-600 transition-colors hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                >
                    <svg
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"
                        />
                    </svg>
                    <span>Kembali ke Halaman Masuk</span>
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
