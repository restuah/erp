<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import Badge from '@/Components/Atoms/Badge.vue';
import Button from '@/Components/Atoms/Button.vue';
import Icon from '@/Components/Atoms/Icon.vue';
import SelectInput from '@/Components/Atoms/SelectInput.vue';
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
    rates: Object,
    currencies: Array,
    stats: Object,
    filters: Object,
});

// Filters
const search = ref(props.filters.search || '');
const selectedDate = ref(props.filters.date || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const selectedCurrency = ref(props.filters.currency || '');
let filterTimeout = null;

const activeFiltersCount = computed(() => {
    let count = 0;
    if (selectedDate.value) count++;
    if (startDate.value || endDate.value) count++;
    if (selectedCurrency.value) count++;
    return count;
});

const applyFilters = () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(
            route('exchange-rates.index'),
            {
                search: search.value || undefined,
                date: selectedDate.value || undefined,
                start_date:
                    !selectedDate.value && startDate.value
                        ? startDate.value
                        : undefined,
                end_date:
                    !selectedDate.value && endDate.value
                        ? endDate.value
                        : undefined,
                currency: selectedCurrency.value || undefined,
            },
            { preserveState: true, replace: true },
        );
    }, 250);
};

watch([search, selectedDate, startDate, endDate, selectedCurrency], () => {
    applyFilters();
});

const resetFilters = () => {
    search.value = '';
    selectedDate.value = '';
    startDate.value = '';
    endDate.value = '';
    selectedCurrency.value = '';
    applyFilters();
};

const formatRupiah = (val, decimals = 2) => {
    if (val === null || val === undefined || isNaN(val)) return 'Rp 0,00';
    return (
        'Rp ' +
        Number(val).toLocaleString('id-ID', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: 4,
        })
    );
};

const formatDateIndo = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

// --- MODAL SINKRONISASI BANK INDONESIA ---
const showSyncModal = ref(false);
const syncForm = useForm({
    start_date: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
        .toISOString()
        .split('T')[0],
    end_date: new Date().toISOString().split('T')[0],
    currency_codes: [],
});

const openSyncModal = () => {
    syncForm.reset();
    syncForm.clearErrors();
    syncForm.start_date = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
        .toISOString()
        .split('T')[0];
    syncForm.end_date = new Date().toISOString().split('T')[0];
    syncForm.currency_codes = [];
    showSyncModal.value = true;
};

const submitSync = () => {
    syncForm.post(route('exchange-rates.sync'), {
        preserveScroll: true,
        onSuccess: () => {
            showSyncModal.value = false;
        },
    });
};

// --- MODAL INPUT KURS MANUAL ---
const showCreateModal = ref(false);
const createForm = useForm({
    currency_id: '',
    date: new Date().toISOString().split('T')[0],
    unit: 1.0,
    rate_buy: '',
    rate_sell: '',
    rate_middle: '',
    source: 'Manual',
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.date = new Date().toISOString().split('T')[0];
    createForm.unit = 1.0;
    createForm.source = 'Manual';
    showCreateModal.value = true;
};

const autoCalculateMiddleRate = (formObj) => {
    const buy = parseFloat(formObj.rate_buy);
    const sell = parseFloat(formObj.rate_sell);
    if (!isNaN(buy) && !isNaN(sell) && buy > 0 && sell > 0) {
        formObj.rate_middle = ((buy + sell) / 2).toFixed(2);
    }
};

const submitCreate = () => {
    createForm.post(route('exchange-rates.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

// --- MODAL UBAH KURS ---
const showEditModal = ref(false);
const rateToEdit = ref(null);
const editForm = useForm({
    currency_id: '',
    date: '',
    unit: 1.0,
    rate_buy: '',
    rate_sell: '',
    rate_middle: '',
    source: '',
});

const openEditModal = (rate) => {
    rateToEdit.value = rate;
    editForm.reset();
    editForm.clearErrors();
    editForm.currency_id = rate.currency_id;
    editForm.date = rate.date;
    editForm.unit = rate.unit;
    editForm.rate_buy = rate.rate_buy;
    editForm.rate_sell = rate.rate_sell;
    editForm.rate_middle = rate.rate_middle;
    editForm.source = rate.source || 'Manual';
    showEditModal.value = true;
};

const submitEdit = () => {
    if (!rateToEdit.value) return;
    editForm.put(route('exchange-rates.update', rateToEdit.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            rateToEdit.value = null;
        },
    });
};

// --- DELETE MODAL ---
const showDeleteModal = ref(false);
const rateToDelete = ref(null);

const openDeleteModal = (rate) => {
    rateToDelete.value = rate;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!rateToDelete.value) return;
    router.delete(route('exchange-rates.destroy', rateToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            rateToDelete.value = null;
        },
    });
};
</script>

<template>
    <Head title="Master Kurs Mata Uang" />

    <AuthenticatedTemplate>
        <!-- PAGE HEADER ORGANISM -->
        <PageHeader
            title="Master Kurs Valuta Asing"
            subtitle="Pusat kurs transaksi harian valuta asing (JISDOR / Bank Indonesia) untuk konversi pengadaan, penjualan, dan akuntansi."
            :breadcrumbs="[
                { label: 'Dashboard', href: route('dashboard') },
                { label: 'Master Data' },
                { label: 'Kurs Valas' },
            ]"
        >
            <template #badge>
                <Badge variant="info" dot size="sm"> Live BI & Manual </Badge>
            </template>

            <template #actions>
                <Button variant="secondary" size="md" @click="openCreateModal">
                    <template #icon-left>
                        <Icon name="plus" class="h-4 w-4" />
                    </template>
                    Input Manual
                </Button>
                <Button variant="primary" size="md" @click="openSyncModal">
                    <template #icon-left>
                        <Icon name="refresh" class="h-4 w-4" />
                    </template>
                    Sinkronisasi Bank Indonesia
                </Button>
            </template>
        </PageHeader>

        <!-- STATS OVERVIEW CARDS -->
        <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <StatsCard
                title="Total Record Kurs"
                :value="stats.total_records || 0"
                subtitle="Data historis kurs"
                icon-name="calendar"
                icon-color="blue"
            />
            <StatsCard
                title="Valas Terpantau"
                :value="stats.total_currencies || 0"
                subtitle="Mata uang asing"
                icon-name="dollar"
                icon-color="emerald"
            />
            <StatsCard
                title="Update Terakhir"
                :value="formatDateIndo(stats.latest_date)"
                subtitle="Tanggal data terbaru"
                icon-name="calendar"
                icon-color="blue"
            />
            <StatsCard
                title="Sumber Utama"
                value="Bank Indonesia"
                subtitle="JISDOR / BI Feed"
                icon-name="check"
                icon-color="amber"
            />
        </div>

        <!-- FILTER BAR ORGANISM -->
        <FilterBar
            v-model:search="search"
            search-placeholder="Cari kode valas atau sumber data..."
            :active-filters-count="activeFiltersCount"
            @reset="resetFilters"
        >
            <template #filters>
                <SelectInput
                    v-model="selectedCurrency"
                    size="sm"
                    class="w-full sm:w-auto"
                >
                    <option value="">Semua Mata Uang</option>
                    <option v-for="c in currencies" :key="c.id" :value="c.code">
                        {{ c.code }} - {{ c.name }}
                    </option>
                </SelectInput>

                <TextInput
                    v-model="selectedDate"
                    type="date"
                    size="sm"
                    class="w-full sm:w-auto"
                    title="Pilih Tanggal Spesifik"
                />
            </template>
        </FilterBar>

        <!-- RESPONSIVE DATA TABLE ORGANISM -->
        <DataTable
            :items="rates.data"
            empty-title="Belum Ada Data Kurs"
            empty-description="Belum ada riwayat kurs yang cocok dengan kriteria filter."
            empty-action-text="Sinkronisasi Kurs Sekarang"
            @empty-action="openSyncModal"
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
                                {{ item.currency?.code }}
                            </span>
                            <span class="text-xs text-slate-500">
                                ({{ item.unit }} Unit)
                            </span>
                        </div>

                        <Badge
                            :variant="
                                item.source === 'Bank Indonesia'
                                    ? 'info'
                                    : 'neutral'
                            "
                            size="sm"
                        >
                            {{ item.source || 'Manual' }}
                        </Badge>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-400">Tanggal:</span>
                        <span
                            class="font-bold text-slate-900 dark:text-white"
                            >{{ formatDateIndo(item.date) }}</span
                        >
                    </div>

                    <div
                        class="grid grid-cols-3 gap-2 rounded-xl bg-slate-50 p-2.5 text-center text-xs dark:bg-slate-800/80"
                    >
                        <div>
                            <span class="block text-[10px] text-slate-400"
                                >Beli</span
                            >
                            <span
                                class="font-mono font-semibold text-slate-700 dark:text-slate-300"
                                >{{ formatRupiah(item.rate_buy) }}</span
                            >
                        </div>
                        <div>
                            <span
                                class="block text-[10px] font-bold text-blue-500"
                                >Tengah</span
                            >
                            <span
                                class="font-mono font-bold text-blue-600 dark:text-blue-400"
                                >{{ formatRupiah(item.rate_middle) }}</span
                            >
                        </div>
                        <div>
                            <span class="block text-[10px] text-slate-400"
                                >Jual</span
                            >
                            <span
                                class="font-mono font-semibold text-slate-700 dark:text-slate-300"
                                >{{ formatRupiah(item.rate_sell) }}</span
                            >
                        </div>
                    </div>

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
                    <th scope="col" class="px-4 py-2.5 text-start font-bold">
                        Tanggal
                    </th>
                    <th scope="col" class="px-4 py-2.5 text-start font-bold">
                        Mata Uang
                    </th>
                    <th scope="col" class="px-4 py-2.5 text-center font-bold">
                        Satuan
                    </th>
                    <th scope="col" class="px-4 py-2.5 text-end font-bold">
                        Kurs Beli (IDR)
                    </th>
                    <th scope="col" class="px-4 py-2.5 text-end font-bold">
                        Kurs Tengah (IDR)
                    </th>
                    <th scope="col" class="px-4 py-2.5 text-end font-bold">
                        Kurs Jual (IDR)
                    </th>
                    <th scope="col" class="px-4 py-2.5 text-start font-bold">
                        Sumber
                    </th>
                    <th scope="col" class="px-4 py-2.5 text-end font-bold">
                        Aksi
                    </th>
                </tr>
            </template>

            <template #row="{ item }">
                <td
                    class="px-4 py-3 font-medium text-slate-900 dark:text-white"
                >
                    {{ formatDateIndo(item.date) }}
                </td>
                <td class="px-4 py-3">
                    <span
                        class="font-mono text-xs font-bold text-blue-600 dark:text-blue-400"
                    >
                        {{ item.currency?.code }}
                    </span>
                    <span
                        class="ms-1.5 text-xs text-slate-500 dark:text-slate-400"
                    >
                        {{ item.currency?.name }}
                    </span>
                </td>
                <td
                    class="px-4 py-3 text-center font-mono text-xs text-slate-600 dark:text-slate-400"
                >
                    {{ item.unit }}
                </td>
                <td
                    class="px-4 py-3 text-end font-mono text-xs tabular-nums text-slate-700 dark:text-slate-300"
                >
                    {{ formatRupiah(item.rate_buy) }}
                </td>
                <td
                    class="px-4 py-3 text-end font-mono text-sm font-bold tabular-nums text-blue-600 dark:text-blue-400"
                >
                    {{ formatRupiah(item.rate_middle) }}
                </td>
                <td
                    class="px-4 py-3 text-end font-mono text-xs tabular-nums text-slate-700 dark:text-slate-300"
                >
                    {{ formatRupiah(item.rate_sell) }}
                </td>
                <td class="px-4 py-3">
                    <Badge
                        :variant="
                            item.source === 'Bank Indonesia'
                                ? 'info'
                                : 'neutral'
                        "
                        size="sm"
                    >
                        {{ item.source || 'Manual' }}
                    </Badge>
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
                <Pagination :links="rates.links" />
            </template>
        </DataTable>

        <!-- SINKRONISASI MODAL -->
        <ModalDialog
            :show="showSyncModal"
            title="Sinkronisasi Kurs Bank Indonesia"
            subtitle="Tarik data kurs valuta asing resmi dari web feed Bank Indonesia"
            max-width="md"
            @close="showSyncModal = false"
        >
            <form @submit.prevent="submitSync" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <FormField
                        label="Tanggal Mulai"
                        required
                        :error="syncForm.errors.start_date"
                    >
                        <TextInput v-model="syncForm.start_date" type="date" />
                    </FormField>
                    <FormField
                        label="Tanggal Selesai"
                        required
                        :error="syncForm.errors.end_date"
                    >
                        <TextInput v-model="syncForm.end_date" type="date" />
                    </FormField>
                </div>
            </form>

            <template #footer>
                <Button
                    variant="secondary"
                    size="md"
                    @click="showSyncModal = false"
                >
                    Batal
                </Button>
                <Button
                    variant="primary"
                    size="md"
                    :loading="syncForm.processing"
                    @click="submitSync"
                >
                    <template #icon-left>
                        <Icon name="refresh" class="h-4 w-4" />
                    </template>
                    Mulai Sinkronisasi
                </Button>
            </template>
        </ModalDialog>

        <!-- INPUT MANUAL MODAL -->
        <ModalDialog
            :show="showCreateModal"
            title="Input Kurs Valas Manual"
            max-width="md"
            @close="showCreateModal = false"
        >
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <FormField
                        label="Mata Uang"
                        required
                        :error="createForm.errors.currency_id"
                    >
                        <SelectInput v-model="createForm.currency_id">
                            <option value="">-- Pilih --</option>
                            <option
                                v-for="c in currencies"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.code }}
                            </option>
                        </SelectInput>
                    </FormField>

                    <FormField
                        label="Tanggal Berlaku"
                        required
                        :error="createForm.errors.date"
                    >
                        <TextInput v-model="createForm.date" type="date" />
                    </FormField>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <FormField
                        label="Kurs Beli (IDR)"
                        required
                        :error="createForm.errors.rate_buy"
                    >
                        <TextInput
                            v-model="createForm.rate_buy"
                            type="number"
                            step="0.01"
                            @input="autoCalculateMiddleRate(createForm)"
                        />
                    </FormField>

                    <FormField
                        label="Kurs Jual (IDR)"
                        required
                        :error="createForm.errors.rate_sell"
                    >
                        <TextInput
                            v-model="createForm.rate_sell"
                            type="number"
                            step="0.01"
                            @input="autoCalculateMiddleRate(createForm)"
                        />
                    </FormField>
                </div>

                <FormField
                    label="Kurs Tengah (Otomatis / Manual)"
                    required
                    :error="createForm.errors.rate_middle"
                >
                    <TextInput
                        v-model="createForm.rate_middle"
                        type="number"
                        step="0.01"
                        class="font-bold text-blue-600 dark:text-blue-400"
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
                    Simpan Kurs
                </Button>
            </template>
        </ModalDialog>

        <!-- EDIT MODAL -->
        <ModalDialog
            :show="showEditModal"
            title="Ubah Data Kurs"
            max-width="md"
            @close="showEditModal = false"
        >
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <FormField
                        label="Kurs Beli (IDR)"
                        required
                        :error="editForm.errors.rate_buy"
                    >
                        <TextInput
                            v-model="editForm.rate_buy"
                            type="number"
                            step="0.01"
                            @input="autoCalculateMiddleRate(editForm)"
                        />
                    </FormField>

                    <FormField
                        label="Kurs Jual (IDR)"
                        required
                        :error="editForm.errors.rate_sell"
                    >
                        <TextInput
                            v-model="editForm.rate_sell"
                            type="number"
                            step="0.01"
                            @input="autoCalculateMiddleRate(editForm)"
                        />
                    </FormField>
                </div>

                <FormField
                    label="Kurs Tengah (IDR)"
                    required
                    :error="editForm.errors.rate_middle"
                >
                    <TextInput
                        v-model="editForm.rate_middle"
                        type="number"
                        step="0.01"
                        class="font-bold text-blue-600 dark:text-blue-400"
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
                    Simpan Perubahan
                </Button>
            </template>
        </ModalDialog>

        <!-- DELETE CONFIRMATION -->
        <ConfirmDialog
            :show="showDeleteModal"
            title="Hapus Data Kurs"
            :message="`Apakah Anda yakin ingin menghapus data kurs ${rateToDelete?.currency?.code} pada tanggal ${formatDateIndo(rateToDelete?.date)}?`"
            confirm-text="Ya, Hapus Kurs"
            @close="showDeleteModal = false"
            @confirm="executeDelete"
        />
    </AuthenticatedTemplate>
</template>
