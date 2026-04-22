<script setup>
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { PhTrash, PhX } from "@phosphor-icons/vue";

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section>
        <header class="mb-6">
            <h2 class="text-sm font-black text-rose-800 uppercase tracking-widest">
                Hapus Akun
            </h2>
            <p class="mt-1 text-xs font-bold text-rose-400/80 leading-relaxed uppercase tracking-wider">
                Setelah akun Anda dihapus, semua data dan sumber dayanya akan dihapus secara permanen.
            </p>
        </header>

        <button 
            @click="confirmUserDeletion"
            class="bg-rose-600 hover:bg-rose-700 text-white px-8 py-3 rounded-2xl font-black text-xs uppercase tracking-widest transition shadow-xl shadow-rose-500/25 flex items-center gap-2 active:scale-95"
        >
            <PhTrash :size="18" weight="bold" />
            <span>Hapus Akun Permanen</span>
        </button>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-8 font-sans">
                <h2 class="text-xl font-black text-slate-800 tracking-tight">
                    Apakah Anda yakin ingin menghapus akun?
                </h2>

                <p class="mt-2 text-xs font-bold text-slate-500 leading-relaxed uppercase tracking-wider">
                    Setelah akun Anda dihapus, semua sumber daya dan datanya akan dihapus secara permanen. Silakan masukkan kata sandi Anda untuk mengonfirmasi bahwa Anda ingin menghapus akun secara permanen.
                </p>

                <div class="mt-6">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Konfirmasi Kata Sandi</label>
                    <input
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="input-base w-full font-bold text-slate-700"
                        placeholder="Masukkan sandi Anda"
                        @keyup.enter="deleteUser"
                    />
                    <p v-if="form.errors.password" class="mt-2 text-[10px] font-black text-rose-500 uppercase tracking-widest">{{ form.errors.password }}</p>
                </div>

                <div class="mt-8 flex justify-end gap-3">
                    <button @click="closeModal" class="px-6 py-3 rounded-xl text-slate-400 hover:text-slate-600 font-black text-xs uppercase tracking-widest transition active:scale-95">
                        Batal
                    </button>

                    <button
                        :disabled="form.processing"
                        @click="deleteUser"
                        class="bg-rose-600 hover:bg-rose-700 text-white px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition shadow-xl shadow-rose-500/25 flex items-center gap-2 active:scale-95 disabled:opacity-50"
                    >
                        <PhTrash :size="18" weight="bold" />
                        <span>Hapus Sekarang</span>
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
