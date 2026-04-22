<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { PhCheckCircle } from "@phosphor-icons/vue";

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">
                Informasi Personal
            </h2>
            <p class="mt-1 text-xs font-bold text-slate-400 leading-relaxed uppercase tracking-wider">
                Perbarui nama akun dan alamat email terdaftar Anda.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="space-y-6"
        >
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Nama Lengkap</label>
                <input
                    v-model="form.name"
                    type="text"
                    class="input-base w-full font-bold text-slate-700"
                    required
                    autofocus
                    autocomplete="name"
                />
                <p v-if="form.errors.name" class="mt-2 text-[10px] font-black text-rose-500 uppercase tracking-widest">{{ form.errors.name }}</p>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Alamat Email</label>
                <input
                    v-model="form.email"
                    type="email"
                    class="input-base w-full font-bold text-slate-700"
                    required
                    autocomplete="username"
                />
                <p v-if="form.errors.email" class="mt-2 text-[10px] font-black text-rose-500 uppercase tracking-widest">{{ form.errors.email }}</p>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <div class="p-4 bg-amber-50 rounded-2xl border border-amber-100 mt-4">
                    <p class="text-xs font-bold text-amber-700 leading-relaxed">
                        Alamat email Anda belum diverifikasi.
                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="ml-1 text-indigo-600 underline hover:text-indigo-800 focus:outline-none transition"
                        >
                            Klik di sini untuk mengirim ulang email verifikasi.
                        </Link>
                    </p>

                    <div
                        v-show="status === 'verification-link-sent'"
                        class="mt-2 text-xs font-black text-emerald-600 uppercase tracking-widest"
                    >
                        Link verifikasi baru telah dikirim ke alamat email Anda.
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button 
                    type="submit"
                    :disabled="form.processing"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest transition shadow-xl shadow-indigo-500/25 flex items-center gap-2 active:scale-95 disabled:opacity-50"
                >
                    <PhCheckCircle :size="18" weight="bold" />
                    <span>Simpan Perubahan</span>
                </button>

                <Transition
                    enter-active-class="transition duration-500 ease-out"
                    enter-from-class="opacity-0 translate-x-2"
                    leave-active-class="transition ease-in"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-[10px] font-black text-emerald-600 uppercase tracking-widest flex items-center gap-1.5"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                        Profil Berhasil Diperbarui
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
