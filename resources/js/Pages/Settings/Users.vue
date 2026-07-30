<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ResponsiveTable from '@/Components/ResponsiveTable.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { PhUserPlus, PhNotePencil, PhTrash, PhShield, PhUserGear, PhIdentificationCard } from "@phosphor-icons/vue";

const props = defineProps({
    users: Object,
});

const showModal = ref(false);
const editingUser = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'staff_gudang',
});

const openModal = (user = null) => {
    editingUser.value = user;
    if (user) {
        form.name = user.name;
        form.email = user.email;
        form.role = user.role;
        form.password = '';
        form.password_confirmation = '';
    } else {
        form.reset();
    }
    showModal.value = true;
};

const submit = () => {
    if (editingUser.value) {
        form.put(route('users.update', editingUser.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteUser = (id) => {
    if (confirm('Yakin ingin menghapus user ini?')) {
        router.delete(route('users.destroy', id));
    }
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    editingUser.value = null;
};

const getRoleBadgeClass = (role) => {
    switch (role) {
        case 'admin': return 'bg-rose-50 text-rose-600 border-rose-100';
        case 'staff_gudang': return 'bg-indigo-50 text-indigo-600 border-indigo-100';
        case 'viewer': return 'bg-slate-50 text-slate-500 border-slate-100';
        default: return 'bg-slate-50 text-slate-500 border-slate-100';
    }
};
</script>

<template>
    <AuthenticatedLayout title="Manajemen User">
        <Head title="Manajemen User" />

        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4 animate-fade-down">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight uppercase">Manajemen User</h1>
                <p class="text-slate-500 font-bold text-xs uppercase tracking-[0.2em] mt-1">Kelola hak akses dan akun personil gudang</p>
            </div>
            <button @click="openModal()" class="btn-primary w-full md:w-auto flex items-center justify-center gap-2 group">
                <PhUserPlus :size="20" weight="bold" class="group-hover:scale-110 transition-transform" />
                Tambah User Baru
            </button>
        </div>

        <ResponsiveTable :headers="['Nama & Email', 'Role', 'Tanggal Terdaftar']" :items="users.data">
                <template #row="{ item }">
                    <td class="px-8 py-5">
                        <div class="flex items-center gap-3">
                            <img :src="`https://ui-avatars.com/api/?name=${item.name}&background=025cca&color=fff`" class="w-10 h-10 rounded-xl shadow-sm border border-slate-100">
                            <div>
                                <div class="font-black text-slate-800 tracking-tight uppercase">{{ item.name }}</div>
                                <div class="text-[11px] text-slate-400 font-bold tracking-wider">{{ item.email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-8 py-5">
                        <span :class="getRoleBadgeClass(item.role)" class="text-[10px] font-black px-3 py-1.5 rounded-lg border uppercase tracking-widest inline-flex items-center gap-1.5">
                            <PhShield v-if="item.role === 'admin'" :size="12" weight="fill" />
                            {{ item.role?.replace('_', ' ') }}
                        </span>
                    </td>
                    <td class="px-8 py-5 text-sm font-bold text-slate-500">
                        {{ new Date(item.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                    </td>
                    <td class="px-8 py-5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button @click="openModal(item)" class="p-2.5 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all active:scale-90">
                                <PhNotePencil :size="20" weight="bold" />
                            </button>
                            <button v-if="$page.props.auth.user.id !== item.id" @click="deleteUser(item.id)" class="p-2.5 text-rose-500 hover:bg-rose-50 rounded-xl transition-all active:scale-90">
                                <PhTrash :size="20" weight="bold" />
                            </button>
                        </div>
                    </td>
                </template>
                <template #mobile-card="{ item }">
                    <div class="flex justify-between items-start mb-4 uppercase">
                        <div class="flex items-center gap-3">
                            <img :src="`https://ui-avatars.com/api/?name=${item.name}&background=025cca&color=fff`" class="w-10 h-10 rounded-xl shadow-sm">
                            <div>
                                <div class="font-black text-slate-800 tracking-tight uppercase">{{ item.name }}</div>
                                <div class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest">{{ item.email }}</div>
                            </div>
                        </div>
                        <div class="flex gap-1">
                            <button @click="openModal(item)" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                                <PhNotePencil :size="20" weight="bold" />
                            </button>
                            <button v-if="$page.props.auth.user.id !== item.id" @click="deleteUser(item.id)" class="p-2 text-slate-400 hover:text-rose-500 transition-colors">
                                <PhTrash :size="20" weight="bold" />
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-50 uppercase">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Role</p>
                            <p :class="item.role === 'admin' ? 'text-rose-600' : 'text-indigo-600'" class="text-sm font-black tracking-tight uppercase">{{ item.role?.replace('_', ' ') }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Terdaftar</p>
                            <p class="text-sm font-bold text-slate-700 tracking-tight">{{ new Date(item.created_at).toLocaleDateString('id-ID') }}</p>
                        </div>
                    </div>
                </template>

                <template #pagination>
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 w-full">
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Showing {{ users?.from || 0 }}-{{ users?.to || 0 }} of {{ users?.total || 0 }}</p>
                        <div class="flex gap-1 overflow-x-auto pb-2 sm:pb-0 scrollbar-hide">
                            <template v-for="(link, k) in users?.links || []" :key="k">
                                <Link v-if="link.url" 
                                      :href="link.url" 
                                      v-html="link.label"
                                      class="px-4 py-2 text-xs font-black rounded-xl transition-all active:scale-95 whitespace-nowrap"
                                      :class="[link.active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white text-slate-500 border border-slate-100 hover:bg-slate-50']" />
                                <div v-else 
                                     v-html="link.label"
                                     class="px-4 py-2 text-xs font-bold text-slate-300 bg-slate-50/50 border border-slate-50 rounded-xl whitespace-nowrap" />
                            </template>
                        </div>
                    </div>
                </template>
            </ResponsiveTable>

        <!-- Modal Form -->
        <Modal :show="showModal" :title="editingUser ? 'Edit User' : 'Tambah User Baru'" @close="closeModal">
            <form @submit.prevent="submit" class="relative flex flex-col h-full">
                <div class="p-8 space-y-6 pb-28">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-1">
                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1">Nama Lengkap</label>
                        <div class="relative">
                            <PhIdentificationCard class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" :size="20" />
                            <input type="text" v-model="form.name" required class="input-base !pl-12 font-bold uppercase" placeholder="Nama personil...">
                        </div>
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="col-span-1">
                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1">Email</label>
                        <input type="email" v-model="form.email" required autocomplete="username" class="input-base font-bold" placeholder="email@contoh.com">
                        <InputError :message="form.errors.email" />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1">Role / Hak Akses</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <label v-for="r in ['admin', 'staff_gudang', 'viewer']" :key="r" 
                                   :class="[form.role === r ? 'border-indigo-600 bg-indigo-50 text-indigo-700 shadow-lg shadow-indigo-100/50' : 'border-slate-200 text-slate-500 hover:border-slate-300']"
                                   class="relative flex items-center p-4 rounded-2xl border-2 cursor-pointer transition-all duration-300">
                                <input type="radio" v-model="form.role" :value="r" class="hidden">
                                <div class="flex flex-col">
                                    <span class="font-black text-sm uppercase tracking-tight">{{ r.replace('_', ' ') }}</span>
                                    <span class="text-[10px] font-bold opacity-60">{{ r === 'admin' ? 'Akses Penuh' : (r === 'viewer' ? 'Lihat Saja' : 'Operator') }}</span>
                                </div>
                                <PhShield v-if="form.role === r" class="absolute right-4 text-indigo-600" :size="20" weight="fill" />
                            </label>
                        </div>
                        <InputError :message="form.errors.role" />
                    </div>
                    <div class="col-span-1">
                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1">Password {{ editingUser ? '(Kosongi jika tidak diubah)' : '' }}</label>
                        <input type="password" v-model="form.password" :required="!editingUser" autocomplete="new-password" class="input-base font-bold" placeholder="••••••••">
                        <InputError :message="form.errors.password" />
                    </div>
                    <div class="col-span-1">
                        <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1">Konfirmasi Password</label>
                        <input type="password" v-model="form.password_confirmation" :required="!editingUser" autocomplete="new-password" class="input-base font-bold" placeholder="••••••••">
                    </div>
                </div>

                </div>
                <div class="sticky bottom-0 bg-white/90 backdrop-blur-md p-6 md:p-8 border-t border-slate-100 flex justify-end gap-3 mt-auto shrink-0 z-10">
                    <button type="button" @click="closeModal" class="btn-secondary font-black text-xs uppercase px-6">Batal</button>
                    <button type="submit" :disabled="form.processing" class="btn-primary font-black text-xs uppercase px-6">
                        {{ editingUser ? 'Simpan Perubahan' : 'Daftarkan User' }}
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
