<script setup>
import MasterDataLayout from '@/Layouts/MasterDataLayout.vue';
import ResponsiveTable from '@/Components/ResponsiveTable.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, router, Head, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { PhNotePencil, PhTrash, PhWarehouse, PhMapPin } from "@phosphor-icons/vue";

const props = defineProps({
    warehouses: Object,
    filters: Object
});

const showModal = ref(false);
const editingItem = ref(null);
const search = ref(props.filters?.search || '');

const form = useForm({
    kode_gudang: '',
    nama: '',
    alamat: '',
});

const openModal = (item = null) => {
    editingItem.value = item;
    if (item) {
        form.kode_gudang = item.kode_gudang;
        form.nama = item.nama;
        form.alamat = item.alamat;
    } else {
        form.reset();
        form.kode_gudang = 'GDN-' + Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    }
    showModal.value = true;
};

const submit = () => {
    if (editingItem.value) {
        form.put(route('warehouses.update', editingItem.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('warehouses.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteItem = (id) => {
    if (confirm('Yakin ingin menghapus gudang ini?')) {
        router.delete(route('warehouses.destroy', id), {
            preserveScroll: true
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    editingItem.value = null;
};

// Debounce implementation
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
    router.get(route('warehouses.index'), 
        { search: search.value }, 
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300);

const handleSearch = (val) => {
    search.value = val;
    performSearch();
};

watch(search, (newVal) => {
    if (newVal === '') handleSearch('');
});
</script>

<template>
    <div>
        <Head title="Master Data Gudang" />

        <MasterDataLayout 
            title="Master Data" 
            active-tab="Gudang" 
            :search="search"
            add-button-label="Tambah Gudang"
            @add="openModal()"
            @search="handleSearch"
        >
            <ResponsiveTable :headers="['Kode', 'Nama Gudang', 'Alamat']" :items="warehouses?.data || []">
                <template #row="{ item }">
                    <td class="px-8 py-5 uppercase">
                        <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-100 uppercase tracking-wider uppercase">
                            {{ item.kode_gudang }}
                        </span>
                    </td>
                    <td class="px-8 py-5 font-black text-slate-800 tracking-tight uppercase">{{ item.nama }}</td>
                    <td class="px-8 py-5 text-[11px] font-bold text-slate-400 uppercase tracking-widest truncate max-w-xs uppercase">{{ item.alamat }}</td>
                    <td class="px-8 py-5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button @click="openModal(item)" class="p-2.5 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all active:scale-90">
                                <PhNotePencil :size="20" weight="bold" />
                            </button>
                            <button @click="deleteItem(item.id)" class="p-2.5 text-rose-500 hover:bg-rose-50 rounded-xl transition-all active:scale-90">
                                <PhTrash :size="20" weight="bold" />
                            </button>
                        </div>
                    </td>
                </template>

                <template #mobile-card="{ item }">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                <PhWarehouse :size="20" weight="fill" />
                            </div>
                            <div>
                                <div class="font-black text-slate-800 tracking-tight uppercase">{{ item.nama }}</div>
                                <div class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest uppercase">{{ item.kode_gudang }}</div>
                            </div>
                        </div>
                        <div class="flex gap-1">
                            <button @click="openModal(item)" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors">
                                <PhNotePencil :size="20" weight="bold" />
                            </button>
                            <button @click="deleteItem(item.id)" class="p-2 text-slate-400 hover:text-rose-500 transition-colors">
                                <PhTrash :size="20" weight="bold" />
                            </button>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-slate-50 uppercase">
                        <div class="flex items-start gap-2">
                            <PhMapPin :size="14" class="text-slate-300 mt-0.5" />
                            <p class="text-xs font-bold text-slate-500 italic uppercase">{{ item.alamat }}</p>
                        </div>
                    </div>
                </template>

                <template #pagination>
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 w-full">
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Showing {{ warehouses?.from || 0 }}-{{ warehouses?.to || 0 }} of {{ warehouses?.total || 0 }}</p>
                        <div class="flex gap-1 overflow-x-auto pb-2 sm:pb-0 scrollbar-hide">
                            <template v-for="(link, k) in warehouses?.links || []" :key="k">
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

            <Modal :show="showModal" :title="editingItem ? 'Edit Gudang' : 'Tambah Gudang Baru'" @close="closeModal">
                <form @submit.prevent="submit" class="flex flex-col h-full md:h-auto">
                    <div class="p-6 md:p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1">Kode Gudang</label>
                                <input type="text" v-model="form.kode_gudang" disabled class="input-base !bg-slate-50 !border-slate-100 font-bold text-indigo-600 uppercase">
                                <InputError :message="form.errors.kode_gudang" />
                            </div>
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1">Nama Gudang</label>
                                <input type="text" v-model="form.nama" required class="input-base font-bold uppercase" placeholder="Nama gudang...">
                                <InputError :message="form.errors.nama" />
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1">Alamat</label>
                                <textarea v-model="form.alamat" rows="3" class="input-base font-bold py-4 uppercase" placeholder="Lokasi gudang..."></textarea>
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
