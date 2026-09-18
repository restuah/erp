<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { nextTick, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    cooldown: {
        type: Number,
        default: 0,
    },
});

const otpDigits = ref(['', '', '', '', '', '']);
const digitInputs = ref([]);

const form = useForm({
    otp: '',
});

// Resend Cooldown Timer
const currentCooldown = ref(props.cooldown || 0);
let timerInterval = null;

const startCooldownTimer = (seconds) => {
    currentCooldown.value = seconds;
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (currentCooldown.value > 0) {
            currentCooldown.value--;
        } else {
            clearInterval(timerInterval);
        }
    }, 1000);
};

onMounted(() => {
    if (props.cooldown > 0) {
        startCooldownTimer(props.cooldown);
    }
    nextTick(() => {
        if (digitInputs.value[0]) {
            digitInputs.value[0].focus();
        }
    });
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

// Digit Input Navigation
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
    form.otp = otpDigits.value.join('');

    // If all 6 digits entered, automatically attempt submit
    if (form.otp.length === 6) {
        submit();
    }
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
        form.otp = pasted;
        if (digitInputs.value[5]) {
            digitInputs.value[5].focus();
        }
        submit();
    }
};

const submit = () => {
    form.otp = otpDigits.value.join('');
    form.post(route('two-factor.verify'), {
        onError: () => {
            otpDigits.value = ['', '', '', '', '', ''];
            form.reset();
            nextTick(() => {
                if (digitInputs.value[0]) {
                    digitInputs.value[0].focus();
                }
            });
        },
    });
};

// Resend OTP
const isResending = ref(false);
const resendOtp = () => {
    if (currentCooldown.value > 0 || isResending.value) return;

    isResending.value = true;
    router.post(
        route('two-factor.challenge.resend'),
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

// Cancel and Return to Login
const isCancelling = ref(false);
const cancelChallenge = () => {
    isCancelling.value = true;
    router.delete(route('two-factor.challenge.cancel'), {
        onFinish: () => {
            isCancelling.value = false;
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi Dua Faktor" />

        <!-- Header above form with Theme Toggle -->
        <div
            class="mb-6 flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-700/80"
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
                    <h1
                        class="text-lg font-bold tracking-tight text-gray-900 dark:text-white"
                    >
                        Verifikasi 2FA
                    </h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Keamanan Masuk ERP
                    </p>
                </div>
            </div>
            <ThemeToggle />
        </div>

        <div class="text-center">
            <p class="text-xs text-gray-600 dark:text-gray-300">
                Silakan masukkan 6 digit kode verifikasi yang dikirimkan ke:
            </p>
            <p
                class="mt-1 text-sm font-semibold text-indigo-600 dark:text-indigo-400"
            >
                {{ email }}
            </p>
        </div>

        <form @submit.prevent="submit" class="mt-6">
            <!-- 6-digit OTP Inputs -->
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

            <InputError :message="form.errors.otp" class="mt-3 text-center" />

            <!-- Resend Section -->
            <div class="mt-5 text-center">
                <p
                    v-if="currentCooldown > 0"
                    class="text-xs text-gray-500 dark:text-gray-400"
                >
                    Kirim ulang kode dalam
                    <span
                        class="font-semibold text-indigo-600 dark:text-indigo-400"
                        >{{ currentCooldown }}s</span
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

            <div class="mt-6 flex flex-col gap-2.5">
                <PrimaryButton
                    class="w-full justify-center py-2.5"
                    :disabled="form.processing || form.otp.length < 6"
                >
                    <svg
                        v-if="form.processing"
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
                    Verifikasi & Masuk
                </PrimaryButton>

                <button
                    type="button"
                    @click="cancelChallenge"
                    :disabled="isCancelling"
                    class="text-center text-xs font-medium text-gray-500 hover:text-gray-700 hover:underline dark:text-gray-400 dark:hover:text-gray-200"
                >
                    Batalkan dan Kembali ke Login
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
