<script setup>
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import AuthenticatedTemplate from '@/Components/Templates/AuthenticatedTemplate.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import TwoFactorAuthenticationForm from './Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    pendingEmail: {
        type: String,
        default: null,
    },
    otpCooldown: {
        type: Number,
        default: 0,
    },
    twoFactorEnabled: {
        type: Boolean,
        default: false,
    },
    twoFactorConfirmedAt: {
        type: String,
        default: null,
    },
    pendingTwoFactor: {
        type: Boolean,
        default: false,
    },
    twoFactorCooldown: {
        type: Number,
        default: 0,
    },
});
</script>

<template>
    <Head title="Pengaturan Profil" />

    <AuthenticatedTemplate>
        <template #header>
            <PageHeader
                title="Pengaturan Profil"
                subtitle="Kelola informasi akun pengguna, kata sandi, dan keamanan otentikasi dua faktor."
                :breadcrumbs="[
                    { label: 'Dashboard', href: route('dashboard') },
                    { label: 'Profil Pengguna' },
                ]"
            />
        </template>

        <div class="mx-auto max-w-5xl space-y-6">
            <div
                class="shadow-xs rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700/60 dark:bg-gray-800"
            >
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    :pending-email="pendingEmail"
                    :otp-cooldown="otpCooldown"
                />
            </div>

            <!-- Two-Factor Authentication Setting -->
            <div
                class="shadow-xs rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700/60 dark:bg-gray-800"
            >
                <TwoFactorAuthenticationForm
                    :two-factor-enabled="twoFactorEnabled"
                    :two-factor-confirmed-at="twoFactorConfirmedAt"
                    :pending-two-factor="pendingTwoFactor"
                    :two-factor-cooldown="twoFactorCooldown"
                />
            </div>

            <div
                class="shadow-xs rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700/60 dark:bg-gray-800"
            >
                <UpdatePasswordForm />
            </div>

            <div
                class="shadow-xs rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700/60 dark:bg-gray-800"
            >
                <DeleteUserForm />
            </div>
        </div>
    </AuthenticatedTemplate>
</template>
