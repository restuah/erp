<script setup>
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Badge from '@/Components/Atoms/Badge.vue';
import Button from '@/Components/Atoms/Button.vue';
import Icon from '@/Components/Atoms/Icon.vue';
import TextInput from '@/Components/Atoms/TextInput.vue';
import ConfirmDialog from '@/Components/Molecules/ConfirmDialog.vue';
import FormField from '@/Components/Molecules/FormField.vue';
import Pagination from '@/Components/Molecules/Pagination.vue';
import StatsCard from '@/Components/Molecules/StatsCard.vue';
import DataTable from '@/Components/Organisms/DataTable.vue';
import FilterBar from '@/Components/Organisms/FilterBar.vue';
import ModalDialog from '@/Components/Organisms/ModalDialog.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import AuthenticatedTemplate from '@/Components/Templates/AuthenticatedTemplate.vue';

const props = defineProps({
    currencies: Object,
    stats: Object,
    filters: Object,
});

// Search filter
const search = ref(props.filters.search || '');
let searchTimeout = null;

watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('currencies.index'),
            { search: val || undefined },
            { preserveState: true, replace: true },
        );
    }, 300);
});

const resetFilters = () => {
    search.value = '';
    router.get(
        route('currencies.index'),
        {},
        { preserveState: true, replace: true },
    );
};

// Create Modal State & Form
const showCreateModal = ref(false);
const createForm = useForm({
    code: '',
    name: '',
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
};

const submitCreate = () => {
    createForm.code = createForm.code.toUpperCase().trim();
    createForm.post(route('currencies.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// Edit Modal State & Form
const showEditModal = ref(false);
const currencyToEdit = ref(null);
const editForm = useForm({
    code: '',
    name: '',
});

const openEditModal = (currency) => {
    currencyToEdit.value = currency;
    editForm.reset();
    editForm.clearErrors();
    editForm.code = currency.code;
    editForm.name = currency.name;
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!currencyToEdit.value) return;
    editForm.code = editForm.code.toUpperCase().trim();
    editForm.put(route('currencies.update', currencyToEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            currencyToEdit.value = null;
        },
    });
};

// Delete Modal State & Action
const showDeleteModal = ref(false);
const currencyToDelete = ref(null);
const isDeleting = ref(false);

const openDeleteModal = (currency) => {
    currencyToDelete.value = currency;
    showDeleteModal.value = true;
};

const submitDelete = () => {
    if (!currencyToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('currencies.destroy', currencyToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            currencyToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Master Mata Uang" />

    <AuthenticatedTemplate>
        <!-- PAGE HEADER ORGANISM -->
        <PageHeader
            title="Master Mata Uang"
            subtitle="Daftar mata uang internasional dan domestik yang digunakan dalam transaksi pembelian, penjualan, dan pembukuan ERP."
            :breadcrumbs="[
                { label: 'Dashboard', href: route('dashboard') },
                { label: 'Master Data' },
                { label: 'Mata Uang' },
            ]"
        >
            <template #badge>
                <Badge variant="primary" size="sm">
                    {{ stats.total }} Mata Uang
                </Badge>
            </template>

            <template #actions>
                <Button variant="primary" size="md" @click="openCreateModal">
                    <template #icon-left>
                        <Icon name="plus" class="h-4 w-4" />
                    </template>
                    Tambah Mata Uang
                </Button>
            </template>
        </PageHeader>

        <!-- STATS OVERVIEW CARDS -->
        <div
            class="mb-6 grid max-w-lg grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-2"
        >
            <StatsCard
                title="Total Mata Uang"
                :value="stats.total"
                subtitle="Semua valuta terdaftar"
                icon-name="dollar"
                icon-color="indigo"
            />
            <StatsCard
                title="Mata Uang Utama"
                value="IDR"
                subtitle="Mata uang dasar ERP"
                icon-name="check"
                icon-color="emerald"
            />
        </div>

        <!-- FILTER BAR ORGANISM -->
        <FilterBar
            v-model:search="search"
            search-placeholder="Cari kode valas (cth: USD, EUR, IDR) atau nama mata uang..."
            @reset="resetFilters"
        />

        <!-- RESPONSIVE DATA TABLE ORGANISM -->
        <DataTable
            :items="currencies.data"
            empty-title="Belum Ada Mata Uang"
            empty-description="Belum ada data mata uang yang cocok dengan pencarian Anda."
            empty-action-text="Tambah Mata Uang Sekarang"
            @empty-action="openCreateModal"
        >
            <!-- 1. MOBILE CARD LIST VIEW (< md) -->
            <template #card="{ item }">
                <div class="space-y-3">
                    <div
                        class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2 dark:border-slate-700/60"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="font-mono text-base font-bold text-blue-600 dark:text-blue-400"
                            >
                                {{ item.code }}
                            </span>
                            <Badge
                                v-if="item.code === 'IDR'"
                                variant="success"
                                size="sm"
                            >
                                Base Currency
                            </Badge>
                        </div>

                        <span class="font-mono text-xs text-slate-400"
                            >ID: #{{ item.id }}</span
                        >
                    </div>

                    <p class="text-sm font-bold text-slate-900 dark:text-white">
                        {{ item.name }}
                    </p>

                    <div
                        class="flex items-center justify-end gap-2 border-t border-slate-100 pt-2 dark:border-slate-700/60"
                    >
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="openEditModal(item)"
                            title="Ubah"
                        >
                            <template #icon-left>
                                <Icon name="edit" class="h-4 w-4" />
                            </template>
                            Ubah
                        </Button>
                        <Button
                            v-if="item.code !== 'IDR'"
                            variant="danger"
                            size="sm"
                            @click="openDeleteModal(item)"
                            title="Hapus"
                        >
                            <template #icon-left>
                                <Icon name="trash" class="h-4 w-4" />
                            </template>
                        </Button>
                    </div>
                </div>
            </template>

            <!-- 2. DESKTOP DATA TABLE (>= md) -->
            <template #head>
                <tr>
                    <th scope="col" class="px-5 py-3.5 font-bold">
                        Kode Mata Uang
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">
                        Nama Mata Uang
                    </th>
                    <th scope="col" class="px-5 py-3.5 font-bold">Status</th>
                    <th scope="col" class="px-5 py-3.5 text-end font-bold">
                        Aksi
                    </th>
                </tr>
            </template>

            <template #row="{ item }">
                <td
                    class="px-5 py-4 font-mono text-sm font-bold text-blue-600 dark:text-blue-400"
                >
                    {{ item.code }}
                </td>
                <td class="px-5 py-4 font-bold text-slate-900 dark:text-white">
                    {{ item.name }}
                </td>
                <td class="px-5 py-4">
                    <Badge
                        v-if="item.code === 'IDR'"
                        variant="success"
                        size="sm"
                    >
                        Base Currency
                    </Badge>
                    <Badge v-else variant="primary" size="sm"> Valas </Badge>
                </td>
                <td class="px-5 py-4 text-end">
                    <div class="flex items-center justify-end gap-1.5">
                        <Button
                            variant="secondary"
                            size="xs"
                            @click="openEditModal(item)"
                            title="Ubah"
                        >
                            <Icon name="edit" class="h-4 w-4" />
                        </Button>
                        <Button
                            v-if="item.code !== 'IDR'"
                            variant="danger"
                            size="xs"
                            @click="openDeleteModal(item)"
                            title="Hapus"
                        >
                            <Icon name="trash" class="h-4 w-4" />
                        </Button>
                    </div>
                </td>
            </template>

            <template #pagination>
                <Pagination :links="currencies.links" />
            </template>
        </DataTable>

        <!-- CREATE MODAL -->
        <ModalDialog
            :show="showCreateModal"
            title="Tambah Mata Uang Baru"
            max-width="md"
            @close="showCreateModal = false"
        >
            <form @submit.prevent="submitCreate" class="space-y-4">
                <FormField
                    label="Kode Valas (ISO Code)"
                    required
                    :error="createForm.errors.code"
                >
                    <TextInput
                        v-model="createForm.code"
                        placeholder="Contoh: USD, JPY, SGD"
                        maxlength="5"
                        class="font-mono font-bold uppercase"
                        :error="!!createForm.errors.code"
                    />
                </FormField>

                <FormField
                    label="Nama Mata Uang"
                    required
                    :error="createForm.errors.name"
                >
                    <TextInput
                        v-model="createForm.name"
                        placeholder="Contoh: United States Dollar"
                        :error="!!createForm.errors.name"
                    />
                </FormField>
            </form>

            <template #footer>
                <Button
                    variant="secondary"
                    size="md"
                    @click="showCreateModal = false"
                >
                    Batal
                </Button>
                <Button
                    variant="primary"
                    size="md"
                    :loading="createForm.processing"
                    @click="submitCreate"
                >
                    <template #icon-left>
                        <Icon name="check" class="h-4 w-4" />
                    </template>
                    Simpan Mata Uang
                </Button>
            </template>
        </ModalDialog>

        <!-- EDIT MODAL -->
        <ModalDialog
            :show="showEditModal"
            :title="`Ubah Mata Uang: ${currencyToEdit?.code || ''}`"
            max-width="md"
            @close="showEditModal = false"
        >
            <form @submit.prevent="submitEdit" class="space-y-4">
                <FormField
                    label="Kode Valas"
                    required
                    :error="editForm.errors.code"
                >
                    <TextInput
                        v-model="editForm.code"
                        class="font-mono font-bold uppercase"
                        :error="!!editForm.errors.code"
                    />
                </FormField>

                <FormField
                    label="Nama Mata Uang"
                    required
                    :error="editForm.errors.name"
                >
                    <TextInput
                        v-model="editForm.name"
                        :error="!!editForm.errors.name"
                    />
                </FormField>
            </form>

            <template #footer>
                <Button
                    variant="secondary"
                    size="md"
                    @click="showEditModal = false"
                >
                    Batal
                </Button>
                <Button
                    variant="primary"
                    size="md"
                    :loading="editForm.processing"
                    @click="submitEdit"
                >
                    <template #icon-left>
                        <Icon name="check" class="h-4 w-4" />
                    </template>
                    Simpan Perubahan
                </Button>
            </template>
        </ModalDialog>

        <!-- DELETE CONFIRMATION -->
        <ConfirmDialog
            :show="showDeleteModal"
            title="Hapus Mata Uang"
            :message="`Apakah Anda yakin ingin menghapus mata uang '${currencyToDelete?.code}' (${currencyToDelete?.name})?`"
            confirm-text="Ya, Hapus Mata Uang"
            :loading="isDeleting"
            @close="showDeleteModal = false"
            @confirm="submitDelete"
        />
    </AuthenticatedTemplate>
</template>
