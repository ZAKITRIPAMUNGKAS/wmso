<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { PhEnvelopeSimple, PhArrowLeft, PhCircleNotch } from "@phosphor-icons/vue";

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout :processing="form.processing">
        <Head title="Lupa Password" />

        <div class="mb-6 animate-fade-down">
            <h2 class="text-2xl font-black text-slate-900 tracking-wider text-center uppercase mb-3">
                LUPA PASSWORD
            </h2>
            <p class="text-xs text-slate-500 text-center font-medium leading-relaxed">
                Lupa password Anda? Masukkan alamat email terdaftar dan kami akan mengirimkan tautan untuk mengatur ulang password baru Anda.
            </p>
        </div>

        <div v-if="status" class="mb-4 text-xs font-bold text-emerald-600 text-center bg-emerald-50 py-3 rounded-full border border-emerald-100 animate-fade-in">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div class="space-y-1.5 animate-fade-up stagger-1">
                <div class="relative group">
                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-emerald-700 transition-colors duration-300">
                        <PhEnvelopeSimple :size="20" />
                    </span>
                    <input 
                        type="email" 
                        v-model="form.email"
                        placeholder="Email Terdaftar" 
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full bg-white border border-slate-300 text-slate-900 rounded-full pl-14 pr-6 py-3.5 outline-none focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-700 transition-all placeholder:text-slate-400 text-sm font-semibold shadow-sm"
                    >
                </div>
                <InputError class="mt-1 ml-4" :message="form.errors.email" />
            </div>

            <div class="pt-2 animate-fade-up stagger-2">
                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="w-full bg-[#046c4e] hover:bg-[#03543d] text-white font-extrabold py-4 rounded-full shadow-lg shadow-emerald-900/20 transition-all duration-300 transform active:scale-[0.98] flex items-center justify-center gap-2 tracking-wide text-xs md:text-sm uppercase cursor-pointer"
                >
                    <template v-if="form.processing">
                        <PhCircleNotch :size="18" weight="bold" class="animate-spin" />
                        Mengirim...
                    </template>
                    <template v-else>
                        Kirim Tautan Reset Password
                    </template>
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <Link :href="route('login')" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-emerald-700 transition-colors">
                <PhArrowLeft :size="14" weight="bold" />
                Kembali ke Halaman Login
            </Link>
        </div>
    </GuestLayout>
</template>
