<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    PhPackage, 
    PhEnvelopeSimple, 
    PhLockSimple, 
    PhArrowRight 
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

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Login" />

        <div class="text-center mb-8 md:mb-10">
            <div class="inline-flex items-center justify-center w-16 h-16 md:w-20 md:h-20 bg-indigo-600 rounded-2xl md:rounded-3xl mb-4 md:mb-6 shadow-lg shadow-indigo-500/30 transform hover:rotate-6 transition-all duration-300">
                <PhPackage :size="32" weight="fill" class="text-white md:hidden" />
                <PhPackage :size="40" weight="fill" class="text-white hidden md:block" />
            </div>
            <h2 class="text-2xl md:text-4xl font-black text-white tracking-tight leading-tight">{{ $page.props.company.name }}</h2>
            <p v-if="$page.props.company.tagline" class="text-slate-400 mt-2 md:mt-3 text-xs md:text-base font-bold uppercase tracking-widest opacity-60">{{ $page.props.company.tagline }}</p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-bold text-emerald-400 text-center bg-emerald-500/10 py-3 rounded-xl border border-emerald-500/20">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5 md:space-y-6">
            <div class="space-y-2">
                <label class="block text-xs md:text-sm font-bold text-slate-300 ml-1 uppercase tracking-wider">Email / Username</label>
                <div class="relative group">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors duration-300">
                        <PhEnvelopeSimple :size="20" class="md:hidden" />
                        <PhEnvelopeSimple :size="24" class="hidden md:block" />
                    </span>
                    <input 
                        type="email" 
                        v-model="form.email"
                        placeholder="email@contoh.com" 
                        required
                        autofocus
                        class="w-full bg-white/5 border border-white/10 text-white rounded-2xl pl-12 pr-4 py-3.5 md:py-5 outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all placeholder:text-slate-600 text-sm md:text-base font-bold"
                    >
                </div>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="space-y-2">
                <div class="flex justify-between items-center px-1">
                    <label class="text-xs md:text-sm font-bold text-slate-300 uppercase tracking-wider">Password</label>
                    <Link 
                        v-if="canResetPassword"
                        :href="route('password.request')" 
                        class="text-[10px] md:text-xs text-indigo-400 hover:text-indigo-300 font-bold transition-colors uppercase tracking-widest"
                    >
                        Lupa?
                    </Link>
                </div>
                <div class="relative group">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-indigo-400 transition-colors duration-300">
                        <PhLockSimple :size="20" class="md:hidden" />
                        <PhLockSimple :size="24" class="hidden md:block" />
                    </span>
                    <input 
                        type="password" 
                        v-model="form.password"
                        placeholder="••••••••" 
                        required
                        class="w-full bg-white/5 border border-white/10 text-white rounded-2xl pl-12 pr-4 py-3.5 md:py-5 outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all placeholder:text-slate-600 font-black text-sm md:text-base"
                    >
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <button 
                type="submit" 
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
                class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-black py-4 md:py-5 rounded-2xl shadow-xl shadow-indigo-500/20 transition-all duration-300 transform active:scale-[0.98] group flex items-center justify-center gap-3 mt-4 md:mt-8"
            >
                Masuk Sistem
                <PhArrowRight :size="20" weight="bold" class="transition-transform group-hover:translate-x-1" />
            </button>
        </form>


    </GuestLayout>
</template>
