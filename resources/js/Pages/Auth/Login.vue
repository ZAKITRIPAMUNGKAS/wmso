<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    PhEnvelopeSimple, 
    PhLockSimple, 
    PhArrowRight,
    PhCircleNotch
} from "@phosphor-icons/vue";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showError = ref(false);
const authStatus = ref('');
const authMessage = ref('');
const isSubmitting = ref(false);

const submit = () => {
    if (form.processing || isSubmitting.value) return;

    isSubmitting.value = true;
    authStatus.value = 'loading';
    authMessage.value = 'Memvalidasi Akses...';

    form.post(route('login'), {
        onSuccess: () => {
            authStatus.value = 'success';
            authMessage.value = 'Berhasil Masuk! Membuka Dashboard...';
        },
        onError: () => {
            authStatus.value = 'error';
            authMessage.value = 'Gagal Masuk! Periksa Email & Password';
            
            showError.value = true;

            setTimeout(() => {
                isSubmitting.value = false;
                authStatus.value = '';
                authMessage.value = '';
                form.reset('password');

                setTimeout(() => {
                    showError.value = false;
                }, 4000);
            }, 1800);
        },
        onFinish: () => {
            if (authStatus.value !== 'error') {
                form.reset('password');
            }
        }
    });
};
</script>

<template>
    <!-- Floating Error Popup -->
    <div v-if="showError && Object.keys(form.errors).length > 0" class="fixed top-8 left-1/2 -translate-x-1/2 z-[200] w-[90%] max-w-sm animate-bounce-in">
        <div class="bg-rose-500 text-white p-4 rounded-2xl shadow-2xl shadow-rose-200 border border-rose-400 flex items-center gap-4">
            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center shrink-0">
                <PhLockSimple :size="20" weight="fill" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest opacity-70">Akses Ditolak</p>
                <p class="text-xs font-bold leading-tight">{{ Object.values(form.errors)[0] }}</p>
            </div>
            <button @click="showError = false" class="ml-auto text-white/50 hover:text-white">
                <PhArrowRight :size="16" weight="bold" />
            </button>
        </div>
    </div>

    <GuestLayout :processing="isSubmitting" :auth-status="authStatus" :auth-message="authMessage">
        <Head title="Masuk Sistem" />

        <!-- Form Title (Exact match SIGN IN NOW style) -->
        <div class="text-center mb-8 animate-fade-down">
            <h2 class="text-3xl font-black text-slate-900 tracking-wide uppercase">
                SIGN IN NOW
            </h2>
        </div>

        <div v-if="status" class="mb-4 text-xs font-bold text-emerald-600 text-center bg-emerald-50 py-3 rounded-full border border-emerald-100 animate-fade-in">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4 md:space-y-5">
            <!-- Email Input (Pill Shaped matching reference image) -->
            <div class="space-y-1 animate-fade-up stagger-1">
                <div class="relative group">
                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-[#046c4e] transition-colors duration-300">
                        <PhEnvelopeSimple :size="20" />
                    </span>
                    <input 
                        type="email" 
                        v-model="form.email"
                        placeholder="Email" 
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full bg-white border border-slate-300 text-slate-900 rounded-full pl-14 pr-6 py-4 outline-none focus:ring-4 focus:ring-[#046c4e]/10 focus:border-[#046c4e] transition-all placeholder:text-slate-400 text-sm font-semibold shadow-sm"
                    >
                </div>
                <InputError class="mt-1 ml-5" :message="form.errors.email" />
            </div>

            <!-- Password Input (Pill Shaped matching reference image) -->
            <div class="space-y-1 animate-fade-up stagger-2">
                <div class="relative group">
                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-[#046c4e] transition-colors duration-300">
                        <PhLockSimple :size="20" />
                    </span>
                    <input 
                        type="password" 
                        v-model="form.password"
                        placeholder="Password" 
                        required
                        autocomplete="current-password"
                        class="w-full bg-white border border-slate-300 text-slate-900 rounded-full pl-14 pr-6 py-4 outline-none focus:ring-4 focus:ring-[#046c4e]/10 focus:border-[#046c4e] transition-all placeholder:text-slate-400 text-sm font-semibold shadow-sm"
                    >
                </div>
                <InputError class="mt-1 ml-5" :message="form.errors.password" />
            </div>

            <!-- Checkbox & Forgot Password Row -->
            <div class="flex justify-between items-center px-4 py-1 animate-fade-up stagger-3">
                <label class="flex items-center gap-2.5 cursor-pointer text-xs font-semibold text-slate-600 select-none">
                    <input type="checkbox" v-model="form.remember" class="rounded border-slate-300 text-[#046c4e] focus:ring-[#046c4e] w-4 h-4" />
                    <span>Ingat Saya</span>
                </label>

                <Link 
                    v-if="canResetPassword"
                    :href="route('password.request')" 
                    class="text-xs text-slate-500 hover:text-[#046c4e] font-bold transition-colors underline underline-offset-2"
                >
                    Lupa Password?
                </Link>
            </div>

            <!-- Submit Button (Pill Shaped Deep Emerald Button matching reference image) -->
            <div class="pt-2 animate-fade-up stagger-4">
                <button 
                    type="submit" 
                    :disabled="isSubmitting"
                    class="w-full bg-[#046c4e] hover:bg-[#03543d] text-white font-bold py-4 rounded-full shadow-lg shadow-[#046c4e]/25 transition-all duration-300 transform active:scale-[0.98] flex items-center justify-center gap-2 tracking-wide text-base cursor-pointer"
                >
                    <template v-if="isSubmitting">
                        <PhCircleNotch :size="20" weight="bold" class="animate-spin" />
                        Memproses...
                    </template>
                    <template v-else>
                        Sign In
                    </template>
                </button>
            </div>
        </form>

        <!-- Divider Line -->
        <div class="my-6 flex items-center justify-center gap-4 text-xs font-semibold text-slate-400 uppercase tracking-widest">
            <div class="h-px bg-slate-200 flex-1"></div>
            <span>Atau</span>
            <div class="h-px bg-slate-200 flex-1"></div>
        </div>

        <!-- Footer Link (Matching reference image) -->
        <div class="text-center text-xs font-semibold text-slate-500">
            Belum Memiliki Akun? <span class="text-[#046c4e] font-bold cursor-pointer hover:underline">Hubungi Admin Gudang</span>
        </div>
    </GuestLayout>
</template>
