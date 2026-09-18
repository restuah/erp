<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
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
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const avatarPreview = ref(user.value.avatar_url || null);
const avatarInput = ref(null);

const form = useForm({
    _method: 'patch',
    name: user.value.name,
    email: user.value.email,
    avatar: null,
    remove_avatar: false,
});

// Watch user updates to sync form
watch(
    () => user.value.email,
    (newEmail) => {
        form.email = newEmail;
    },
);

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

// --- OTP Verification Modal & Flow ---
const showOtpModal = ref(false);
const otpDigits = ref(['', '', '', '', '', '']);
const digitInputs = ref([]);

const formOtp = useForm({
    otp: '',
});

// Auto-open modal if there's a pending email verification
watch(
    () => props.pendingEmail,
    (val) => {
        if (val) {
            showOtpModal.value = true;
            nextTick(() => focusFirstDigit());
        }
    },
    { immediate: true },
);

// Cooldown Timer for Resend OTP
const cooldown = ref(props.otpCooldown || 0);
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
    () => props.otpCooldown,
    (newVal) => {
        if (newVal > 0) {
            startCooldownTimer(newVal);
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

const onDigitInput = (index, event) => {
    const val = event.target.value;

    // Handle single character numeric input
    if (val.length > 0) {
        // Keep only the last character if typed
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

const submitVerifyOtp = () => {
    formOtp.otp = otpDigits.value.join('');
    formOtp.post(route('profile.email.verify'), {
        preserveScroll: true,
        onSuccess: () => {
            showOtpModal.value = false;
            otpDigits.value = ['', '', '', '', '', ''];
            formOtp.reset();
        },
    });
};

const isResending = ref(false);
const resendOtp = () => {
    if (cooldown.value > 0 || isResending.value) return;

    isResending.value = true;
    router.post(
        route('profile.email.resend'),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isResending.value = false;
                startCooldownTimer(60);
            },
        },
    );
};

const isCancelling = ref(false);
const cancelOtp = () => {
    if (isCancelling.value) return;

    isCancelling.value = true;
    router.delete(route('profile.email.cancel'), {
        preserveScroll: true,
        onSuccess: () => {
            showOtpModal.value = false;
            otpDigits.value = ['', '', '', '', '', ''];
            formOtp.reset();
            form.email = user.value.email;
        },
        onFinish: () => {
            isCancelling.value = false;
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

        <!-- PENDING EMAIL VERIFICATION BANNER -->
        <div
            v-if="pendingEmail"
            class="mt-4 flex flex-col gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-900 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-200 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex items-start gap-3">
                <svg
                    class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 7.5h.008v.008H12v-.008z"
                    />
                </svg>
                <div>
                    <p class="text-sm font-semibold">
                        Permintaan Perubahan Email Menunggu Verifikasi
                    </p>
                    <p
                        class="mt-0.5 text-xs text-amber-700 dark:text-amber-300"
                    >
                        Kode OTP telah dikirim ke
                        <strong>{{ pendingEmail }}</strong
                        >. Email belum aktif sebelum OTP diverifikasi.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 self-end sm:self-center">
                <button
                    type="button"
                    @click="showOtpModal = true"
                    class="shadow-xs focus:outline-hidden rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-500 dark:bg-amber-700 dark:hover:bg-amber-600"
                >
                    Masukkan OTP
                </button>
                <button
                    type="button"
                    @click="cancelOtp"
                    :disabled="isCancelling"
                    class="focus:outline-hidden rounded-md border border-amber-300 bg-white px-2.5 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-800 dark:bg-gray-800 dark:text-amber-300 dark:hover:bg-gray-700"
                >
                    Batalkan
                </button>
            </div>
        </div>

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

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Jika email diubah, kode OTP verifikasi akan dikirimkan ke
                    alamat email baru tersebut sebelum tersimpan.
                </p>

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
                <PrimaryButton :disabled="form.processing">
                    Simpan Perubahan
                </PrimaryButton>

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

        <!-- OTP VERIFICATION MODAL -->
        <Modal :show="showOtpModal" @close="showOtpModal = false" maxWidth="md">
            <div class="p-6">
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
                                    d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold text-gray-900 dark:text-white"
                            >
                                Verifikasi Email Baru
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Masukkan 6 digit kode OTP
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="showOtpModal = false"
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
                        {{ pendingEmail }}
                    </p>
                    <p
                        class="mt-1 text-[11px] text-gray-400 dark:text-gray-500"
                    >
                        Kode berlaku selama 15 menit
                    </p>
                </div>

                <form @submit.prevent="submitVerifyOtp" class="mt-6">
                    <!-- 6 Digit Input Boxes -->
                    <div
                        class="flex justify-center gap-2 sm:gap-3"
                        @paste="onDigitPaste"
                    >
                        <input
                            v-for="(digit, index) in otpDigits"
                            :key="index"
                            ref="digitInputs"
                            type="text"
                            inputmode="numeric"
                            maxlength="1"
                            v-model="otpDigits[index]"
                            @input="onDigitInput(index, $event)"
                            @keydown="onDigitKeydown(index, $event)"
                            class="shadow-xs h-12 w-11 rounded-lg border border-gray-300 text-center text-xl font-bold text-gray-900 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-indigo-400"
                        />
                    </div>

                    <div v-if="formOtp.errors.otp" class="mt-3 text-center">
                        <p
                            class="text-xs font-medium text-red-600 dark:text-red-400"
                        >
                            {{ formOtp.errors.otp }}
                        </p>
                    </div>

                    <!-- Resend Section -->
                    <div class="mt-4 text-center">
                        <p
                            v-if="cooldown > 0"
                            class="text-xs text-gray-500 dark:text-gray-400"
                        >
                            Kirim ulang kode dalam
                            <span
                                class="font-semibold text-indigo-600 dark:text-indigo-400"
                                >{{ cooldown }} detik</span
                            >
                        </p>
                        <button
                            v-else
                            type="button"
                            @click="resendOtp"
                            :disabled="isResending"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 hover:underline dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            {{
                                isResending
                                    ? 'Mengirim...'
                                    : 'Kirim Ulang Kode OTP'
                            }}
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div
                        class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"
                    >
                        <SecondaryButton
                            type="button"
                            @click="cancelOtp"
                            :disabled="isCancelling"
                            class="w-full justify-center text-xs sm:w-auto"
                        >
                            {{
                                isCancelling
                                    ? 'Membatalkan...'
                                    : 'Batalkan Perubahan'
                            }}
                        </SecondaryButton>

                        <PrimaryButton
                            type="submit"
                            :disabled="
                                formOtp.processing ||
                                otpDigits.join('').length !== 6
                            "
                            class="w-full justify-center text-xs sm:w-auto"
                            :class="{
                                'cursor-not-allowed opacity-50':
                                    otpDigits.join('').length !== 6 ||
                                    formOtp.processing,
                            }"
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
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <span v-if="formOtp.processing"
                                >Memverifikasi...</span
                            >
                            <span v-else>Verifikasi & Simpan Email</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </section>
</template>
