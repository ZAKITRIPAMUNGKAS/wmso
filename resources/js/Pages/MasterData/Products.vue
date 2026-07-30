<script setup>
import MasterDataLayout from '@/Layouts/MasterDataLayout.vue';
import ResponsiveTable from '@/Components/ResponsiveTable.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { useForm, router, Head, Link } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { PhNotePencil, PhTrash, PhTag, PhCube, PhEye } from "@phosphor-icons/vue";

const props = defineProps({
    products: {
        type: Object,
        required: true,
        default: () => ({ data: [], links: [] })
    },
    filters: {
        type: Object,
        default: () => ({ search: '' })
    },
    next_code: String
});

const showModal = ref(false);
const editingProduct = ref(null);
const search = ref(props.filters?.search || '');

const form = useForm({
    kode_barang: '',
    nama: '',
    merk: '',
    tipe: '',
    satuan: 'Roll',
    harga: '',
    stok_minimum: 10
});

const openModal = (product = null) => {
    editingProduct.value = product;
    if (product) {
        form.kode_barang = product.kode_barang;
        form.nama = product.nama;
        form.merk = product.merk;
        form.tipe = product.tipe;
        form.satuan = product.satuan;
        form.harga = product.harga;
        form.stok_minimum = product.stok_minimum;
    } else {
        form.reset();
        form.kode_barang = '';
    }
    showModal.value = true;
};

const submit = () => {
    if (editingProduct.value) {
        form.put(route('products.update', editingProduct.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('products.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteProduct = (id) => {
    if (confirm('Yakin ingin menghapus produk ini?')) {
        router.delete(route('products.destroy', id), {
            preserveScroll: true
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    editingProduct.value = null;
};

const formatNumber = (num) => {
    if (!num && num !== 0) return '';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
};

const parseNumber = (str) => {
    return parseInt(str.replace(/\./g, '')) || 0;
};

const formattedHarga = computed({
    get: () => formatNumber(form.harga),
    set: (val) => {
        form.harga = parseNumber(val);
    }
});

const formattedStokMin = computed({
    get: () => formatNumber(form.stok_minimum),
    set: (val) => {
        form.stok_minimum = parseNumber(val);
    }
});

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
    router.get(route('products.index'), 
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
        <Head title="Master Data Produk" />

        <MasterDataLayout 
            title="Master Data" 
            active-tab="Produk" 
            :search="search"
            :add-button-label="$page.props.auth.user.role !== 'viewer' ? 'Tambah Produk' : null"
            @add="openModal()"
            @search="handleSearch"
        >
            <ResponsiveTable :headers="['Kode', 'Nama & Tipe', 'Satuan', 'Stok', 'Harga']" :items="products?.data || []">
                <template #row="{ item }">
                    <td class="px-8 py-5">
                        <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-100 uppercase tracking-wider">
                            {{ item.kode_barang }}
                        </span>
                    </td>
                    <td class="px-8 py-5">
                        <div class="font-black text-slate-800 tracking-tight uppercase">{{ item.nama }}</div>
                        <div class="text-[11px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">{{ item.merk }} / {{ item.tipe }}</div>
                    </td>
                    <td class="px-8 py-5 text-sm font-bold text-slate-600 tracking-tight uppercase">{{ item.satuan }}</td>
                    <td class="px-8 py-5">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-black text-slate-800 tracking-tight">{{ item.total_stock || 0 }}</span>
                            <span v-if="(item.total_stock || 0) < item.stok_minimum" class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                        </div>
                    </td>
                    <td class="px-8 py-5">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-black text-slate-800 tracking-tight uppercase">Rp {{ Number(item.harga).toLocaleString('id-ID') }}</span>
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">/ {{ item.satuan }}</span>
                        </div>
                    </td>
                    <td class="px-8 py-5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <Link :href="route('products.show', item.id)" class="p-2.5 text-slate-500 hover:bg-slate-50 hover:text-indigo-600 rounded-xl transition-all active:scale-90 uppercase">
                                <PhEye :size="20" weight="bold" />
                            </Link>
                            <button v-if="$page.props.auth.user.role !== 'viewer'" @click="openModal(item)" class="p-2.5 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all active:scale-90 uppercase">
                                <PhNotePencil :size="20" weight="bold" />
                            </button>
                            <button v-if="$page.props.auth.user.role !== 'viewer'" @click="deleteProduct(item.id)" class="p-2.5 text-rose-500 hover:bg-rose-50 rounded-xl transition-all active:scale-90 uppercase">
                                <PhTrash :size="20" weight="bold" />
                            </button>
                        </div>
                    </td>
                </template>

                <template #mobile-card="{ item }">
                    <div class="flex justify-between items-start mb-4 uppercase">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 uppercase">
                                <PhCube :size="20" weight="fill" />
                            </div>
                            <div>
                                <div class="font-black text-slate-800 tracking-tight uppercase">{{ item.nama }}</div>
                                <div class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest uppercase">{{ item.kode_barang }}</div>
                            </div>
                        </div>
                        <div class="flex gap-1 uppercase">
                            <Link :href="route('products.show', item.id)" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors uppercase">
                                <PhEye :size="20" weight="bold" />
                            </Link>
                            <button v-if="$page.props.auth.user.role !== 'viewer'" @click="openModal(item)" class="p-2 text-slate-400 hover:text-indigo-600 transition-colors uppercase">
                                <PhNotePencil :size="20" weight="bold" />
                            </button>
                            <button v-if="$page.props.auth.user.role !== 'viewer'" @click="deleteProduct(item.id)" class="p-2 text-slate-400 hover:text-rose-500 transition-colors uppercase">
                                <PhTrash :size="20" weight="bold" />
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 pt-4 border-t border-slate-50 uppercase uppercase">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Merk/Tipe</p>
                            <p class="text-sm font-bold text-slate-700 tracking-tight line-clamp-1 uppercase">{{ item.merk }} / {{ item.tipe }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest uppercase">Stok</p>
                            <div class="flex items-center gap-2 uppercase">
                                <p class="text-sm font-black text-slate-900 tracking-tight uppercase">{{ item.total_stock || 0 }}</p>
                                <span v-if="(item.total_stock || 0) < item.stok_minimum" class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest uppercase">Harga</p>
                            <p class="text-sm font-black text-slate-900 tracking-tight uppercase uppercase">Rp {{ Number(item.harga).toLocaleString('id-ID') }}</p>
                        </div>
                    </div>
                </template>

                <template #pagination>
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4 w-full uppercase">
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest uppercase">Showing {{ products?.from || 0 }}-{{ products?.to || 0 }} of {{ products?.total || 0 }}</p>
                        <div class="flex gap-1 overflow-x-auto pb-2 sm:pb-0 scrollbar-hide uppercase">
                            <template v-for="(link, k) in products?.links || []" :key="k">
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

            <Modal :show="showModal" :title="editingProduct ? 'Edit Produk' : 'Tambah Produk Baru'" @close="closeModal">
                <form @submit.prevent="submit" class="flex flex-col h-full md:h-auto uppercase">
                    <div class="p-6 md:p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1 uppercase">Kode</label>
                                <input type="text" v-model="form.kode_barang" class="input-base font-bold text-indigo-600 uppercase" :placeholder="editingProduct ? 'Kode barang...' : props.next_code + ' (Otomatis)'">
                                <InputError :message="form.errors.kode_barang" />
                            </div>
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1 uppercase">Nama Produk</label>
                                <input type="text" v-model="form.nama" required class="input-base font-bold uppercase" placeholder="Nama barang...">
                                <InputError :message="form.errors.nama" />
                            </div>
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1 uppercase">Merk</label>
                                <input type="text" v-model="form.merk" class="input-base font-bold uppercase" placeholder="Merk barang...">
                            </div>
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1 uppercase">Varian/Tipe</label>
                                <input type="text" v-model="form.tipe" class="input-base font-bold uppercase" placeholder="Tipe/Varian...">
                            </div>
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1 uppercase">Satuan</label>
                                <select v-model="form.satuan" class="input-base font-bold appearance-none uppercase">
                                    <option>Roll</option>
                                    <option>Pcs</option>
                                    <option>Box</option>
                                </select>
                            </div>
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1 uppercase">Harga</label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm">Rp</span>
                                    <input type="text" v-model="formattedHarga" placeholder="0" class="input-base !pl-10 font-black uppercase">
                                </div>
                                <InputError :message="form.errors.harga" />
                            </div>
                            <div class="col-span-1">
                                <label class="block text-xs font-bold text-slate-500 mb-2 uppercase tracking-widest ml-1 uppercase">Stok Minimum</label>
                                <input type="text" v-model="formattedStokMin" placeholder="0" class="input-base font-black uppercase">
                                <InputError :message="form.errors.stok_minimum" />
                            </div>
                        </div>
                    </div>

                    <div class="sticky bottom-0 bg-slate-50/80 backdrop-blur-md p-6 md:p-8 border-t border-slate-100 flex flex-col sm:flex-row justify-end gap-3 mt-auto shrink-0">
                        <button type="button" @click="closeModal" class="btn-secondary w-full sm:w-auto uppercase font-black text-[10px]">Batal</button>
                        <button type="submit" :disabled="form.processing" class="btn-primary w-full sm:w-auto uppercase font-black text-[10px]">
                            {{ editingProduct ? 'Simpan Perubahan' : 'Simpan Data' }}
                        </button>
                    </div>
                </form>
            </Modal>
        </MasterDataLayout>
    </div>
</template>
