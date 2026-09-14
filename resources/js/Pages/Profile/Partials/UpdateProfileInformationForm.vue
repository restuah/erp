<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const user = page.props.auth.user;

const avatarPreview = ref(user.avatar_url || null);
const avatarInput = ref(null);

const form = useForm({
    _method: 'patch',
    name: user.name,
    email: user.email,
    avatar: null,
    remove_avatar: false,
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
    form.post(route('profile.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            if (form.avatar) {
                form.avatar = null;
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Informasi Profil
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Perbarui foto profil, nama lengkap, dan alamat email akun Anda.
            </p>
        </header>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <!-- AVATAR UPLOAD SECTION -->
            <div>
                <InputLabel value="Foto Profil" />

                <div class="mt-2 flex items-center gap-5">
                    <!-- Preview Box -->
                    <div class="group relative">
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
                    </div>

                    <!-- Actions -->
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
                            <svg
                                class="-ms-0.5 me-1.5 h-4 w-4 text-gray-500 dark:text-gray-400"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                            Pilih Foto Baru
                        </SecondaryButton>

                        <button
                            v-if="avatarPreview"
                            type="button"
                            @click="removeAvatar"
                            class="text-xs font-medium text-red-600 hover:text-red-700 hover:underline dark:text-red-400 dark:hover:text-red-300 sm:ms-2"
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

            <div>
                <InputLabel for="name" value="Nama Lengkap" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Alamat Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800 dark:text-gray-200">
                    Alamat email Anda belum terverifikasi.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                    >
                        Klik di sini untuk mengirim ulang email verifikasi.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600 dark:text-green-400"
                >
                    Tautan verifikasi baru telah dikirimkan ke alamat email
                    Anda.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing"
                    >Simpan Perubahan</PrimaryButton
                >

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm font-medium text-emerald-600 dark:text-emerald-400"
                    >
                        Tersimpan.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
