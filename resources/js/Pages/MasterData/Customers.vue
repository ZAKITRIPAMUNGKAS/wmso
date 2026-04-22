<script setup>
import MasterDataLayout from '@/Layouts/MasterDataLayout.vue';
import ResponsiveTable from '@/Components/ResponsiveTable.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, router, Head, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    PhNotePencil, 
    PhTrash, 
    PhUser, 
    PhPhone, 
    PhEnvelope, 
    PhMapPin,
    PhPlus
} from "@phosphor-icons/vue";

const props = defineProps({
    customers: {
        type: Object,
        required: true,
        default: () => ({ data: [], links: [], meta: {} })
    },
    filters: {
        type: Object,
        default: () => ({ search: '' })
    }
});

const showModal = ref(false);
const editingItem = ref(null);
const search = ref(props.filters?.search || '');

const form = useForm({
    nama: '',
    alamat: '',
    kontak: '',
    email: '',
});

const openModal = (item = null) => {
    editingItem.value = item;
    if (item) {
        form.nama = item.nama;
        form.alamat = item.alamat;
        form.kontak = item.kontak;
        form.email = item.email;
    } else {
        form.reset();
    }
    showModal.value = true;
};

const submit = () => {
    if (editingItem.value) {
        form.put(route('customers.update', editingItem.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('customers.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteItem = (id) => {
    if (confirm('Yakin ingin menghapus customer ini?')) {
        router.delete(route('customers.destroy', id), {
            preserveScroll: true
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    editingItem.value = null;
};

// Debounce implementation to avoid lodash dependency issues
const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        if (timeoutId) clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            fn(...args);
        }, delay);
    };
};

const performSearch = debounce(() => {
    router.get(route('customers.index'), 
        { search: search.value }, 
        { 
            preserveState: true, 
            preserveScroll: true, 
            replace: true 
        }
    );
}, 300);

const handleSearch = (val) => {
    search.value = val;
    performSearch();
};

watch(search, (newVal) => {
    if (newVal === '') {
        handleSearch('');
    }
});
</script>

<template>
    <div>
        <Head title="Master Data Customer" />

        <MasterDataLayout 
            title="Master Data" 
            active-tab="Customer" 
            :search="search"
            add-button-label="Tambah Customer"
            @add="openModal()"
            @search="handleSearch"
        >
            <ResponsiveTable :headers="['Nama Customer', 'Kontak', 'Email', 'Alamat']" :items="customers?.data || []">
                <template #row="{ item }">
                    <td class="px-8 py-5 font-black text-slate-800 tracking-tight uppercase">{{ item.nama }}</td>
                    <td class="px-8 py-5 text-sm font-bold text-slate-600 tracking-tight uppercase">{{ item.kontak }}</td>
                    <td class="px-8 py-5 text-sm font-bold text-slate-600 tracking-tight uppercase">{{ item.email }}</td>
                    <td class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-widest truncate max-w-xs uppercase">{{ item.alamat }}</td>
                    <td class="px-8 py-5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button @click="openModal(item)" class="p-2.5 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all active:scale-90 uppercase">
                                <PhNotePencil :size="20" weight="bold" />
                            </button>
                            <button @click="deleteItem(item.id)" class="p-2.5 text-rose-500 hover:bg-rose-50 rounded-xl transition-all active:scale-90 uppercase">
                                <PhTrash :size="20" weight="bold" />
                            </button>
                        </div>
                    </td>
                </template>

                <template #mobile-card="{ item }">
                    <div class="flex justify-between items-start mb-4 uppercase">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 uppercase">
                                <PhUser :size="20" weight="fill" />
                            </div>
                            <div>
                                <div class="font-black text-slate-800 tracking-tight uppercase">{{ item.nama }}</div>
                                <div class="flex items-center gap-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest uppercase">
                                    <PhPhone :size="10" /> {{ item.kontak }}
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-1 uppercase">
                            <button @click="openModal(item)" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors uppercase">
                                <PhNotePencil :size="20" weight="bold" />
                            </button>
                            <button @click="deleteItem(item.id)" class="p-2 text-slate-400 hover:text-rose-500 transition-colors uppercase">
                                <PhTrash :size="20" weight="bold" />
                            </button>
                        </div>
                    </div>
                    <div class="space-y-2 pt-4 border-t border-slate-50 uppercase uppercase">
                        <div class="flex items-start gap-2 uppercase">
                            <PhEnvelope :size="14" class="text-slate-300 mt-0.5 uppercase" />
                            <p class="text-xs font-bold text-slate-600 uppercase">{{ item.email }}</p>
                        </div>
                        <div class="flex items-start gap-2 uppercase">
                            <PhMapPin :size="14" class="text-slate-300 mt-0.5 uppercase" />
                            <p class="text-xs font-bold text-slate-500 italic uppercase">{{ item.alamat }}</p>
                        </div>
                    </div>
                </template>

                <template #pagination>
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 w-full uppercase">
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest uppercase">Showing {{ customers?.from || 0 }}-{{ customers?.to || 0 }} of {{ customers?.total || 0 }}</p>
                        <div class="flex gap-1 overflow-x-auto pb-2 sm:pb-0 scrollbar-hide uppercase">
                            <template v-for="(link, k) in customers?.links || []" :key="k">
                                <Link v-if="link.url" 
                                      :href="link.url" 
                                      v-html="link.label"
                                      class="px-4 py-2 text-xs font-black rounded-xl transition-all active:scale-95 whitespace-nowrap uppercase"
                                      :class="[link.active ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white text-slate-500 border border-slate-100 hover:bg-slate-50']" />
                                <div v-else 
                                     v-html="link.label"
                                     class="px-4 py-2 text-xs font-bold text-slate-300 bg-slate-50/50 border border-slate-50 rounded-xl whitespace-nowrap uppercase" />
                            </template>
                        </div>
                    </div>
                </template>
            </ResponsiveTable>

            <Modal :show="showModal" :title="editingItem ? 'Edit Customer' : 'Tambah Customer Baru'" @close="closeModal">
                <form @submit.prevent="submit" class="flex flex-col h-full md:h-auto uppercase">
                    <div class="p-6 md:p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">
                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1 uppercase">Nama Customer</label>
                                <input type="text" v-model="form.nama" required class="input-base font-bold uppercase" placeholder="Nama lengkap customer...">
                                <InputError :message="form.errors.nama" />
                            </div>
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1 uppercase">Kontak Person</label>
                                <input type="text" v-model="form.kontak" required class="input-base font-bold uppercase" placeholder="0812...">
                            </div>
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1 uppercase">Email</label>
                                <input type="email" v-model="form.email" class="input-base font-bold uppercase" placeholder="customer@email.com">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1 uppercase">Alamat</label>
                                <textarea v-model="form.alamat" rows="3" class="input-base font-bold py-4 uppercase" placeholder="Alamat lengkap..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="sticky bottom-0 bg-slate-50/80 backdrop-blur-md p-6 md:p-8 border-t border-slate-100 flex flex-col sm:flex-row justify-end gap-3 mt-auto shrink-0">
                        <button type="button" @click="closeModal" class="btn-secondary w-full sm:w-auto uppercase font-black text-[10px]">Batal</button>
                        <button type="submit" :disabled="form.processing" class="btn-primary w-full sm:w-auto uppercase font-black text-[10px]">
                            {{ editingItem ? 'Simpan Perubahan' : 'Simpan Data' }}
                        </button>
                    </div>
                </form>
            </Modal>
        </MasterDataLayout>
    </div>
</template>
