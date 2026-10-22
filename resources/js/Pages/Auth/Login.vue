<script setup>
import Button from '@/Components/Atoms/Button.vue';
import Checkbox from '@/Components/Atoms/Checkbox.vue';
import InputError from '@/Components/Atoms/InputError.vue';
import InputLabel from '@/Components/Atoms/InputLabel.vue';
import PasswordInput from '@/Components/Atoms/PasswordInput.vue';
import TextInput from '@/Components/Atoms/TextInput.vue';
import GuestTemplate from '@/Components/Templates/GuestTemplate.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestTemplate>
        <Head title="Masuk ke Akun ERP" />

        <!-- Header above form -->
        <div class="mb-6 border-b border-slate-100 pb-4 dark:border-slate-800">
            <h1
                class="text-lg font-bold tracking-tight text-slate-900 dark:text-white"
            >
                Masuk ke Akun
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Sistem Manajemen Terpadu PT. Hiruta Kogyo Indonesia
            </p>
        </div>

        <div v-if="status" class="mb-4 rounded-lg bg-emerald-50 p-3 text-xs font-semibold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="Alamat Email" required />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="nama@hiruta-kogyo.co.id"
                    :error="Boolean(form.errors.email)"
                />

                <InputError class="mt-1.5" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Kata Sandi" required />

                <PasswordInput
                    id="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan kata sandi..."
                    :error="Boolean(form.errors.password)"
                />

                <InputError class="mt-1.5" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="flex cursor-pointer items-center gap-2">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="text-xs font-medium text-slate-600 dark:text-slate-400"
                        >Ingat saya di perangkat ini</span
                    >
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 focus:outline-none"
                >
                    Lupa sandi?
                </Link>
            </div>

            <div class="pt-2">
                <Button
                    type="submit"
                    variant="primary"
                    size="md"
                    class="w-full shadow-md shadow-blue-600/20"
                    :disabled="form.processing"
                    :loading="form.processing"
                >
                    Masuk ke Sistem ERP
                </Button>
            </div>
        </form>
    </GuestTemplate>
</template>
