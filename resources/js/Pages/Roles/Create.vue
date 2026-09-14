<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    permissions: Array,
});

const form = useForm({
    name: '',
    guard_name: 'web',
    permissions: [],
});

// Group permissions by prefix (e.g. users.create -> "users")
const groupedPermissions = computed(() => {
    const groups = {};
    props.permissions.forEach((perm) => {
        const parts = perm.name.split('.');
        const groupKey = parts.length > 1 ? parts[0] : 'general';
        if (!groups[groupKey]) {
            groups[groupKey] = [];
        }
        groups[groupKey].push(perm);
    });
    return groups;
});

const selectAll = () => {
    form.permissions = props.permissions.map((p) => p.name);
};

const deselectAll = () => {
    form.permissions = [];
};

const isGroupAllSelected = (groupPerms) => {
    return groupPerms.every((p) => form.permissions.includes(p.name));
};

const toggleGroup = (groupPerms) => {
    if (isGroupAllSelected(groupPerms)) {
        const groupNames = groupPerms.map((p) => p.name);
        form.permissions = form.permissions.filter(
            (name) => !groupNames.includes(name),
        );
    } else {
        const groupNames = groupPerms.map((p) => p.name);
        const newSet = new Set([...form.permissions, ...groupNames]);
        form.permissions = Array.from(newSet);
    }
};

const submit = () => {
    form.post(route('roles.store'));
};
</script>

<template>
    <Head title="Tambah Peran Baru" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                        Tambah Peran & Tetapkan Hak Akses
                    </h1>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Buat peran baru dan pilih hak akses yang diperbolehkan
                        untuk peran ini.
                    </p>
                </div>
                <Link :href="route('roles.index')">
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

        <form @submit.prevent="submit" class="mx-auto max-w-5xl space-y-6">
            <!-- Basic Info Card -->
            <div
                class="shadow-xs rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700/60 dark:bg-gray-800"
            >
                <h3
                    class="mb-4 text-base font-semibold text-gray-900 dark:text-white"
                >
                    Informasi Peran
                </h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <InputLabel for="name" value="Nama Peran" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full text-sm"
                            placeholder="contoh: Manajer Gudang, Kasir, Staff Finance"
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
                </div>
            </div>

            <!-- Permissions Card -->
            <div
                class="shadow-xs rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700/60 dark:bg-gray-800"
            >
                <div
                    class="mb-6 flex flex-col gap-3 border-b border-gray-100 pb-4 dark:border-gray-700/60 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h3
                            class="text-base font-semibold text-gray-900 dark:text-white"
                        >
                            Distribusi Hak Akses (Permissions)
                        </h3>
                        <p
                            class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                        >
                            Centang izin yang ingin diberikan ke peran ini.
                            Terpilih:
                            <strong
                                class="text-indigo-600 dark:text-indigo-400"
                                >{{ form.permissions.length }}</strong
                            >
                            dari {{ permissions.length }} izin.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="selectAll"
                            class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            Pilih Semua
                        </button>
                        <button
                            type="button"
                            @click="deselectAll"
                            class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            Hapus Pilihan
                        </button>
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="(perms, groupName) in groupedPermissions"
                        :key="groupName"
                        class="rounded-xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-700/50 dark:bg-gray-900/40"
                    >
                        <div
                            class="mb-3 flex items-center justify-between border-b border-gray-200/60 pb-2 dark:border-gray-700/60"
                        >
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300"
                            >
                                Modul: {{ groupName }}
                            </span>
                            <button
                                type="button"
                                @click="toggleGroup(perms)"
                                class="text-[11px] font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                            >
                                {{
                                    isGroupAllSelected(perms)
                                        ? 'Batal Semua'
                                        : 'Pilih Semua'
                                }}
                            </button>
                        </div>
                        <div class="space-y-2.5">
                            <label
                                v-for="perm in perms"
                                :key="perm.id"
                                class="flex cursor-pointer items-start gap-2.5 rounded-lg p-1.5 transition-colors hover:bg-white dark:hover:bg-gray-800"
                            >
                                <Checkbox
                                    v-model:checked="form.permissions"
                                    :value="perm.name"
                                    class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500"
                                />
                                <div class="text-xs leading-tight">
                                    <span
                                        class="block font-mono font-medium text-gray-800 dark:text-gray-200"
                                    >
                                        {{ perm.name }}
                                    </span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <InputError class="mt-4" :message="form.errors.permissions" />
            </div>

            <!-- Submit buttons -->
            <div class="flex items-center justify-end gap-3 pt-4">
                <Link :href="route('roles.index')">
                    <SecondaryButton>Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">
                    Simpan Peran & Izin
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
