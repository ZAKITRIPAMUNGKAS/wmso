<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { PhLockKey, PhCheckCircle } from "@phosphor-icons/vue";

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">
                Update Keamanan
            </h2>
            <p class="mt-1 text-xs font-bold text-slate-400 leading-relaxed uppercase tracking-wider">
                Gunakan kata sandi yang kuat dan acak untuk menjaga keamanan akun Anda.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="space-y-6">
            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Kata Sandi Saat Ini</label>
                <input
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="input-base w-full font-bold text-slate-700"
                    autocomplete="current-password"
                />
                <p v-if="form.errors.current_password" class="mt-2 text-[10px] font-black text-rose-500 uppercase tracking-widest">{{ form.errors.current_password }}</p>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Kata Sandi Baru</label>
                <input
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="input-base w-full font-bold text-slate-700"
                    autocomplete="new-password"
                />
                <p v-if="form.errors.password" class="mt-2 text-[10px] font-black text-rose-500 uppercase tracking-widest">{{ form.errors.password }}</p>
            </div>

            <div>
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Konfirmasi Kata Sandi</label>
                <input
                    v-model="form.password_confirmation"
                    type="password"
                    class="input-base w-full font-bold text-slate-700"
                    autocomplete="new-password"
                />
                <p v-if="form.errors.password_confirmation" class="mt-2 text-[10px] font-black text-rose-500 uppercase tracking-widest">{{ form.errors.password_confirmation }}</p>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <button 
                    type="submit"
                    :disabled="form.processing"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest transition shadow-xl shadow-indigo-500/25 flex items-center gap-2 active:scale-95 disabled:opacity-50"
                >
                    <PhLockKey :size="18" weight="bold" />
                    <span>Perbarui Kata Sandi</span>
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
                        Sandi Berhasil Diubah
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
