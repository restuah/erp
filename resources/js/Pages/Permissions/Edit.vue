<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    permission: Object,
});

const form = useForm({
    name: props.permission.name,
    guard_name: props.permission.guard_name,
});

const submit = () => {
    form.put(route('permissions.update', props.permission.id));
};
</script>

<template>
    <Head :title="`Edit Izin: ${permission.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                        Edit Izin Sistem
                    </h1>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Ubah detail informasi izin sistem.
                    </p>
                </div>
                <Link :href="route('permissions.index')">
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

        <div class="mx-auto max-w-2xl">
            <div
                class="shadow-xs rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700/60 dark:bg-gray-800"
            >
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel
                            for="name"
                            value="Nama Izin (Permission Name)"
                        />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full font-mono text-sm"
                            required
                            autofocus
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="guard_name" value="Guard Name" />
                        <TextInput
                            id="guard_name"
                            v-model="form.guard_name"
                            type="text"
                            class="mt-1 block w-full text-sm"
                            required
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.guard_name"
                        />
                    </div>

                    <div
                        class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700/60"
                    >
                        <Link :href="route('permissions.index')">
                            <SecondaryButton>Batal</SecondaryButton>
                        </Link>
                        <PrimaryButton :disabled="form.processing">
                            Simpan Perubahan
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
