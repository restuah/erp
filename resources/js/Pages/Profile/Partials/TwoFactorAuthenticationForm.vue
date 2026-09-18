<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
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

const page = usePage();
const user = computed(() => page.props.auth.user);

// --- State for Enabling 2FA & OTP Modal ---
const enabling2Fa = ref(false);
const showOtpModal = ref(false);
const otpDigits = ref(['', '', '', '', '', '']);
const digitInputs = ref([]);

const formOtp = useForm({
    otp: '',
});

// If there's already a pending 2FA activation on page load
watch(
    () => props.pendingTwoFactor,
    (isPending) => {
        if (isPending && !props.twoFactorEnabled) {
            showOtpModal.value = true;
            nextTick(() => focusFirstDigit());
        }
    },
    { immediate: true },
);

// Cooldown Timer for Resending OTP
const cooldown = ref(props.twoFactorCooldown || 0);
let timerInterval = null;

const startCooldownTimer = (duration) => {
    cooldown.value = duration;
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (cooldown.value > 0) {
            cooldown.value--;
        } else {
            clearInterval(timerInterval);
        }
    }, 1000);
};

watch(
    () => props.twoFactorCooldown,
    (val) => {
        if (val > 0) {
            startCooldownTimer(val);
        }
    },
    { immediate: true },
);

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

const focusFirstDigit = () => {
    if (digitInputs.value[0]) {
        digitInputs.value[0].focus();
    }
};

// Request 2FA activation
const startEnable2Fa = () => {
    enabling2Fa.value = true;
    router.post(
        route('two-factor.enable'),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showOtpModal.value = true;
                otpDigits.value = ['', '', '', '', '', ''];
                formOtp.reset();
                formOtp.clearErrors();
                startCooldownTimer(60);
                nextTick(() => focusFirstDigit());
            },
            onFinish: () => {
                enabling2Fa.value = false;
            },
        },
    );
};

// Handle digit input typing
const onDigitInput = (index, event) => {
    const val = event.target.value;
    if (val.length > 0) {
        const char = val.slice(-1);
        if (/^\d$/.test(char)) {
            otpDigits.value[index] = char;
            if (index < 5 && digitInputs.value[index + 1]) {
                digitInputs.value[index + 1].focus();
            }
        } else {
            otpDigits.value[index] = '';
        }
    }
    formOtp.otp = otpDigits.value.join('');
};

const onDigitKeydown = (index, event) => {
    if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
        if (digitInputs.value[index - 1]) {
            digitInputs.value[index - 1].focus();
        }
    }
};

const onDigitPaste = (event) => {
    event.preventDefault();
    const pasted = event.clipboardData.getData('text').trim();
    if (/^\d{6}$/.test(pasted)) {
        for (let i = 0; i < 6; i++) {
            otpDigits.value[i] = pasted[i];
        }
        formOtp.otp = pasted;
        if (digitInputs.value[5]) {
            digitInputs.value[5].focus();
        }
    }
};

// Submit OTP verification
const submitVerifyOtp = () => {
    formOtp.otp = otpDigits.value.join('');
    formOtp.post(route('two-factor.confirm'), {
        preserveScroll: true,
        onSuccess: () => {
            showOtpModal.value = false;
            otpDigits.value = ['', '', '', '', '', ''];
            formOtp.reset();
        },
        onError: () => {
            otpDigits.value = ['', '', '', '', '', ''];
            formOtp.reset();
            nextTick(() => focusFirstDigit());
        },
    });
};

// Resend OTP
const isResending = ref(false);
const resendOtp = () => {
    if (cooldown.value > 0 || isResending.value) return;

    isResending.value = true;
    router.post(
        route('two-factor.resend'),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                startCooldownTimer(60);
            },
            onFinish: () => {
                isResending.value = false;
            },
        },
    );
};

// Cancel OTP
const isCancelling = ref(false);
const cancelOtp = () => {
    if (isCancelling.value) return;

    isCancelling.value = true;
    router.delete(route('two-factor.cancel'), {
        preserveScroll: true,
        onSuccess: () => {
            showOtpModal.value = false;
            otpDigits.value = ['', '', '', '', '', ''];
            formOtp.reset();
        },
        onFinish: () => {
            isCancelling.value = false;
        },
    });
};

// --- State for Disabling 2FA ---
const confirmingDisable = ref(false);
const passwordInput = ref(null);

const formDisable = useForm({
    password: '',
});

const confirmDisable2Fa = () => {
    confirmingDisable.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const submitDisable2Fa = () => {
    formDisable.delete(route('two-factor.disable'), {
        preserveScroll: true,
        onSuccess: () => closeDisableModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => formDisable.reset(),
    });
};

const closeDisableModal = () => {
    confirmingDisable.value = false;
    formDisable.clearErrors();
    formDisable.reset();
};
</script>

<template>
    <section>
        <header>
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-colors duration-200"
                    :class="
                        twoFactorEnabled
                            ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400'
                            : 'bg-indigo-100 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400'
                    "
                >
                    <!-- Shield Icon -->
                    <svg
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="2"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
                        />
                    </svg>
                </div>

                <div>
                    <h2
                        class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                    >
                        Autentikasi Dua Faktor (2FA)
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Tingkatkan keamanan akun ERP Anda dengan verifikasi OTP
                        melalui email saat masuk.
                    </p>
                </div>
            </div>
        </header>

        <!-- Current Status Box -->
        <div
            class="mt-6 rounded-xl border p-4 transition-colors duration-200 sm:p-5"
            :class="
                twoFactorEnabled
                    ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-900/50 dark:bg-emerald-950/20'
                    : 'border-gray-200 bg-gray-50/70 dark:border-gray-700/60 dark:bg-gray-800/40'
            "
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <div class="flex items-center gap-2.5">
                        <span
                            class="text-sm font-semibold text-gray-800 dark:text-gray-200"
                        >
                            Status 2FA:
                        </span>
                        <span
                            v-if="twoFactorEnabled"
                            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400"
                            ></span>
                            Aktif
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1.5 rounded-full bg-gray-200 px-3 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full bg-gray-500 dark:bg-gray-400"
                            ></span>
                            Tidak Aktif
                        </span>
                    </div>

                    <p
                        class="mt-2 text-xs leading-relaxed text-gray-600 dark:text-gray-300 sm:max-w-md"
                    >
                        <template v-if="twoFactorEnabled">
                            Autentikasi dua faktor aktif. Anda akan menerima
                            kode OTP 6-digit di
                            <span
                                class="font-medium text-gray-900 dark:text-white"
                                >{{ user?.email }}</span
                            >
                            setiap kali melakukan login ke akun ini.
                            <span
                                v-if="twoFactorConfirmedAt"
                                class="mt-1 block text-gray-500 dark:text-gray-400"
                            >
                                Diaktifkan pada: {{ twoFactorConfirmedAt }}
                            </span>
                        </template>
                        <template v-else>
                            Jika 2FA diaktifkan, sistem akan mengirim kode OTP
                            ke email Anda setiap kali login. Akun Anda akan
                            terlindungi meskipun kata sandi diketahui oleh orang
                            lain.
                        </template>
                    </p>
                </div>

                <div class="shrink-0">
                    <DangerButton
                        v-if="twoFactorEnabled"
                        type="button"
                        @click="confirmDisable2Fa"
                    >
                        Nonaktifkan 2FA
                    </DangerButton>

                    <PrimaryButton
                        v-else
                        type="button"
                        :disabled="enabling2Fa"
                        @click="startEnable2Fa"
                    >
                        <svg
                            v-if="enabling2Fa"
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
                                d="M4 12a8 8 0 018-8v8H4z"
                            ></path>
                        </svg>
                        {{ enabling2Fa ? 'Mengirim OTP...' : 'Aktifkan 2FA' }}
                    </PrimaryButton>
                </div>
            </div>
        </div>

        <!-- MODAL: OTP VERIFICATION FOR ACTIVATING 2FA -->
        <Modal :show="showOtpModal" @close="cancelOtp" maxWidth="md">
            <div class="p-6">
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-700/60"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold text-gray-900 dark:text-white"
                            >
                                Verifikasi Pengaktifan 2FA
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Masukkan 6 digit kode OTP dari email Anda
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="cancelOtp"
                        class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <div class="mt-4 text-center">
                    <p class="text-xs text-gray-600 dark:text-gray-300">
                        Kami telah mengirimkan kode verifikasi 6 digit ke:
                    </p>
                    <p
                        class="mt-1 break-all text-sm font-semibold text-indigo-600 dark:text-indigo-400"
                    >
                        {{ user?.email }}
                    </p>
                </div>

                <!-- 6-digit OTP Inputs -->
                <form @submit.prevent="submitVerifyOtp" class="mt-6">
                    <div
                        class="flex justify-center gap-2 sm:gap-3"
                        @paste="onDigitPaste"
                    >
                        <input
                            v-for="(digit, idx) in otpDigits"
                            :key="idx"
                            :ref="(el) => (digitInputs[idx] = el)"
                            type="text"
                            inputmode="numeric"
                            maxlength="1"
                            :value="digit"
                            @input="onDigitInput(idx, $event)"
                            @keydown="onDigitKeydown(idx, $event)"
                            class="h-12 w-11 rounded-lg border border-gray-300 text-center text-xl font-bold tracking-tight text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-indigo-400 sm:h-14 sm:w-12"
                        />
                    </div>

                    <InputError
                        :message="formOtp.errors.otp"
                        class="mt-3 text-center"
                    />

                    <!-- Cooldown / Resend Section -->
                    <div class="mt-5 text-center">
                        <p
                            v-if="cooldown > 0"
                            class="text-xs text-gray-500 dark:text-gray-400"
                        >
                            Kirim ulang kode dalam
                            <span
                                class="font-semibold text-indigo-600 dark:text-indigo-400"
                                >{{ cooldown }}s</span
                            >
                        </p>
                        <button
                            v-else
                            type="button"
                            @click="resendOtp"
                            :disabled="isResending"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 hover:underline disabled:opacity-50 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            {{
                                isResending
                                    ? 'Mengirim ulang...'
                                    : 'Kirim Ulang Kode OTP'
                            }}
                        </button>
                    </div>

                    <!-- Modal Actions -->
                    <div
                        class="mt-6 flex items-center justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700/60"
                    >
                        <SecondaryButton
                            type="button"
                            @click="cancelOtp"
                            :disabled="formOtp.processing || isCancelling"
                        >
                            Batal
                        </SecondaryButton>

                        <PrimaryButton
                            type="submit"
                            :disabled="
                                formOtp.processing || formOtp.otp.length < 6
                            "
                        >
                            <svg
                                v-if="formOtp.processing"
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
                                    d="M4 12a8 8 0 018-8v8H4z"
                                ></path>
                            </svg>
                            Verifikasi & Aktifkan
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- MODAL: CONFIRM DISABLE 2FA (PASSWORD REQUIRED) -->
        <Modal
            :show="confirmingDisable"
            @close="closeDisableModal"
            maxWidth="md"
        >
            <div class="p-6">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-950/60 dark:text-red-400"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h2
                            class="text-base font-bold text-gray-900 dark:text-gray-100"
                        >
                            Nonaktifkan Autentikasi Dua Faktor?
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Tindakan ini akan menurunkan tingkat keamanan akun
                            Anda.
                        </p>
                    </div>
                </div>

                <p
                    class="mt-4 text-xs leading-relaxed text-gray-600 dark:text-gray-300"
                >
                    Setelah 2FA dinonaktifkan, Anda tidak akan lagi diminta
                    memasukkan kode verifikasi email saat login. Masukkan kata
                    sandi akun Anda untuk mengonfirmasi.
                </p>

                <form @submit.prevent="submitDisable2Fa" class="mt-4">
                    <div>
                        <InputLabel
                            for="disable_2fa_password"
                            value="Kata Sandi Saat Ini"
                        />
                        <PasswordInput
                            id="disable_2fa_password"
                            ref="passwordInput"
                            v-model="formDisable.password"
                            class="mt-1 block w-full"
                            placeholder="Masukkan kata sandi akun..."
                            required
                        />
                        <InputError
                            :message="formDisable.errors.password"
                            class="mt-2"
                        />
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <SecondaryButton
                            type="button"
                            @click="closeDisableModal"
                        >
                            Batal
                        </SecondaryButton>

                        <DangerButton
                            type="submit"
                            :disabled="formDisable.processing"
                        >
                            {{
                                formDisable.processing
                                    ? 'Menonaktifkan...'
                                    : 'Ya, Nonaktifkan 2FA'
                            }}
                        </DangerButton>
                    </div>
                </form>
            </div>
        </Modal>
    </section>
</template>
