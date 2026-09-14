<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    user: Object,
    userRoles: Array,
    roles: Array,
});

const avatarPreview = ref(props.user.avatar_url || null);
const avatarInput = ref(null);

const form = useForm({
    _method: 'put',
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    avatar: null,
    remove_avatar: false,
    roles: [...props.userRoles],
});

const onAvatarSelected = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.avatar = file;
        form.remove_avatar = false;
        avatarPreview.value = URL.createObjectURL(file);
    }
};

const removeAvatar = () => {
    form.avatar = null;
    form.remove_avatar = true;
    avatarPreview.value = null;
    if (avatarInput.value) {
        avatarInput.value.value = '';
    }
};

const submit = () => {
    form.post(route('users.update', props.user.id), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head :title="`Edit Pengguna: ${user.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                        Edit Pengguna: {{ user.name }}
                    </h1>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Perbarui rincian pengguna, foto profil avatar, dan hak
                        peran sistem.
                    </p>
                </div>
                <Link :href="route('users.index')">
                    <SecondaryButton class="flex items-center gap-1.5">
                        <svg
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
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                        Kembali
                    </SecondaryButton>
                </Link>
            </div>
        </template>

        <form @submit.prevent="submit" class="mx-auto max-w-4xl space-y-6">
            <!-- Account & Avatar Card -->
            <div
                class="shadow-xs rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700/60 dark:bg-gray-800"
            >
                <h3
                    class="mb-6 text-base font-semibold text-gray-900 dark:text-white"
                >
                    Informasi Akun
                </h3>

                <!-- Avatar Upload Section -->
                <div
                    class="mb-6 border-b border-gray-100 pb-6 dark:border-gray-700/60"
                >
                    <InputLabel value="Foto Profil / Avatar" />
                    <div class="mt-3 flex items-center gap-5">
                        <div
                            class="shadow-xs flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-gray-300 bg-gray-50 text-xl font-bold text-gray-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-500"
                        >
                            <img
                                v-if="avatarPreview"
                                :src="avatarPreview"
                                alt="Avatar preview"
                                class="h-full w-full object-cover"
                            />
                            <span
                                v-else
                                class="text-2xl font-bold uppercase text-indigo-600 dark:text-indigo-400"
                            >
                                {{ user.name.charAt(0) }}
                            </span>
                        </div>
                        <div
                            class="flex flex-col gap-2 sm:flex-row sm:items-center"
                        >
                            <input
                                ref="avatarInput"
                                type="file"
                                accept="image/png, image/jpeg, image/jpg, image/webp"
                                class="hidden"
                                @change="onAvatarSelected"
                            />
                            <SecondaryButton
                                type="button"
                                @click="avatarInput.click()"
                                class="text-xs"
                            >
                                Ganti Foto
                            </SecondaryButton>
                            <button
                                v-if="avatarPreview"
                                type="button"
                                @click="removeAvatar"
                                class="text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400 sm:ms-2"
                            >
                                Hapus Foto
                            </button>
                        </div>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        Format: JPG, JPEG, PNG, WEBP. Maksimal 2MB.
                    </p>
                    <InputError class="mt-2" :message="form.errors.avatar" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <InputLabel for="name" value="Nama Lengkap" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full text-sm"
                            required
                            autofocus
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Alamat Email" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full text-sm"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel
                            for="password"
                            value="Kata Sandi Baru (Opsional)"
                        />
                        <TextInput
                            id="password"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full text-sm"
                            placeholder="Biarkan kosong jika tidak ingin mengubah"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.password"
                        />
                    </div>

                    <div>
                        <InputLabel
                            for="password_confirmation"
                            value="Konfirmasi Kata Sandi Baru"
                        />
                        <TextInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            class="mt-1 block w-full text-sm"
                            placeholder="Ulangi kata sandi baru"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.password_confirmation"
                        />
                    </div>
                </div>
            </div>

            <!-- Role Assignment Card -->
            <div
                class="shadow-xs rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700/60 dark:bg-gray-800"
            >
                <h3
                    class="mb-2 text-base font-semibold text-gray-900 dark:text-white"
                >
                    Penugasan Peran (Roles)
                </h3>
                <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                    Pilih peran yang diberikan kepada pengguna ini.
                </p>

                <div
                    class="grid grid-cols-1 gap-3 sm:grid-cols-2 md:grid-cols-3"
                >
                    <label
                        v-for="role in roles"
                        :key="role.id"
                        class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200/80 bg-gray-50/60 p-3.5 transition-colors hover:bg-white dark:border-gray-700/60 dark:bg-gray-700/30 dark:hover:bg-gray-700/50"
                    >
                        <Checkbox
                            v-model:checked="form.roles"
                            :value="role.name"
                            class="rounded text-indigo-600 focus:ring-indigo-500"
                        />
                        <span
                            class="text-sm font-semibold text-gray-800 dark:text-gray-200"
                        >
                            {{ role.name }}
                        </span>
                    </label>
                </div>
                <InputError class="mt-2" :message="form.errors.roles" />
            </div>

            <!-- Submit buttons -->
            <div class="flex items-center justify-end gap-3 pt-4">
                <Link :href="route('users.index')">
                    <SecondaryButton>Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">
                    Simpan Perubahan
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
