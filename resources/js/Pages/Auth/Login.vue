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
        <Head title="Log in" />

        <!-- Header above form -->
        <div class="mb-6 border-b border-gray-100 pb-4 dark:border-gray-700/80">
            <h1
                class="text-lg font-bold tracking-tight text-gray-900 dark:text-white"
            >
                Masuk ke Akun
            </h1>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Sistem Manajemen Terpadu ERP
            </p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <PasswordInput
                    id="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600 dark:text-gray-400"
                        >Remember me</span
                    >
                </label>
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                >
                    Lupa kata sandi?
                </Link>

                <Button
                    type="submit"
                    variant="primary"
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    :loading="form.processing"
                >
                    Log in
                </Button>
            </div>
        </form>
    </GuestTemplate>
</template>
