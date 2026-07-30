<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    PhArrowLeft, 
    PhFileText, 
    PhListPlus, 
    PhPlus, 
    PhTrash, 
    PhClipboardText,
    PhCalendar,
    PhWarehouse,
    PhInfo
} from "@phosphor-icons/vue";
import { computed, ref, watch, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    adjustment: Object,
    warehouses: Array,
    products: Array,
    auth: Object,
    errors: Object
});

const form = useForm({
    warehouse_id: props.adjustment.warehouse_id,
    tanggal: props.adjustment.tanggal ? props.adjustment.tanggal.substring(0, 10) : new Date().toISOString().split('T')[0],
    status: props.adjustment.status,
    catatan: props.adjustment.catatan || '',
    items: props.adjustment.items.map(item => ({
        product_id: item.product_id,
        quantity_sistem: item.quantity_sistem,
        quantity_fisik: item.quantity_fisik,
        selisih: item.selisih
    }))
});

const currentStocksMap = ref({});
const isLoadingStocks = ref(false);

const fetchWarehouseStocks = async () => {
    if (!form.warehouse_id) {
        currentStocksMap.value = {};
        form.items.forEach(item => {
            item.quantity_sistem = 0;
            item.selisih = item.quantity_fisik - item.quantity_sistem;
        });
        return;
    }

    isLoadingStocks.value = true;
    try {
        const response = await axios.get(route('stock-adjustments.warehouse-stocks', form.warehouse_id));
        currentStocksMap.value = response.data;
        
        // Update system stock and difference for all items based on fetched current stock
        form.items.forEach(item => {
            if (item.product_id) {
                item.quantity_sistem = currentStocksMap.value[item.product_id] || 0;
                item.selisih = item.quantity_fisik - item.quantity_sistem;
            }
        });
    } catch (error) {
        console.error('Gagal mengambil data stok gudang:', error);
        alert('⚠️ Gagal memuat data stok untuk gudang yang terpilih.');
    } finally {
        isLoadingStocks.value = false;
    }
};

onMounted(() => {
    fetchWarehouseStocks();
});

watch(() => form.warehouse_id, fetchWarehouseStocks);

const addItem = () => {
    form.items.push({
        product_id: '',
        quantity_sistem: 0,
        quantity_fisik: 0,
        selisih: 0
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const handleProductChange = (index) => {
    const item = form.items[index];
    if (item.product_id) {
        item.quantity_sistem = currentStocksMap.value[item.product_id] || 0;
    } else {
        item.quantity_sistem = 0;
    }
    item.selisih = item.quantity_fisik - item.quantity_sistem;
};

const handleQuantityFisikChange = (index) => {
    const item = form.items[index];
    const fisik = parseInt(item.quantity_fisik) || 0;
    item.quantity_fisik = fisik;
    item.selisih = fisik - item.quantity_sistem;
};

const totalFisik = computed(() => {
    return form.items.reduce((acc, item) => acc + (parseInt(item.quantity_fisik) || 0), 0);
});

const totalSistem = computed(() => {
    return form.items.reduce((acc, item) => acc + (parseInt(item.quantity_sistem) || 0), 0);
});

const totalSelisih = computed(() => {
    return form.items.reduce((acc, item) => acc + (parseInt(item.selisih) || 0), 0);
});

const submit = () => {
    if (!form.warehouse_id) {
        alert('⚠️ Mohon pilih GUDANG terlebih dahulu.');
        return;
    }
    if (form.items.length === 0) {
        alert('⚠️ Daftar Produk masih kosong. Silakan tambah minimal satu produk.');
        return;
    }
    const emptyProduct = form.items.some(item => !item.product_id);
    if (emptyProduct) {
        alert('⚠️ Ada produk yang belum dipilih di dalam daftar.');
        return;
    }

    form.put(route('stock-adjustments.update', props.adjustment.id), {
        onError: (errors) => {
            console.error('Validation Errors:', errors);
            const firstError = Object.keys(errors)[0];
            const el = document.querySelector(`[name="${firstError}"]`) || document.querySelector('.text-rose-500');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
};
</script>

<template>
    <div>
        <Head title="Edit Stock Opname" />

        <AuthenticatedLayout title="Stock Opname">
            <!-- Back & Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 font-sans">
                <div class="flex items-center gap-4">
                    <Link :href="route('stock-adjustments.index')" class="p-2.5 bg-white border border-slate-200 rounded-xl text-slate-400 hover:text-indigo-600 transition active:scale-90">
                        <PhArrowLeft :size="20" weight="bold" />
                    </Link>
                    <div>
                        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Stock Opname</h2>
                        <p class="text-[11px] text-slate-500 font-bold mt-1 uppercase tracking-tight">Edit Stock Opname: {{ adjustment.no_adjustment }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8 font-sans">
                <!-- Left Column: Form Details & Items -->
                <div class="lg:col-span-8 space-y-8">
                    <!-- Adjustment Details Card -->
                    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 md:p-10">
                        <div class="flex items-center gap-3 mb-8">
                            <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
                                <PhFileText :size="20" weight="fill" />
                            </div>
                            <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Informasi Stock Opname</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Warehouse -->
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                    Gudang Terpilih <span class="text-rose-400">*</span>
                                </label>
                                <select v-model="form.warehouse_id" class="input-base font-black">
                                    <option value="">Pilih Gudang...</option>
                                    <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.nama }}</option>
                                </select>
                                <InputError :message="form.errors.warehouse_id" />
                            </div>

                            <!-- Tanggal -->
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                    Tanggal Opname <span class="text-rose-400">*</span>
                                </label>
                                <input type="date" v-model="form.tanggal" class="input-base font-black">
                                <InputError :message="form.errors.tanggal" />
                            </div>

                            <!-- Status -->
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                    Status Dokumen <span class="text-rose-400">*</span>
                                </label>
                                <select v-model="form.status" class="input-base font-black">
                                    <option value="draft">Draf (Stok Belum Berubah)</option>
                                    <option value="completed">Selesai (Stok Langsung Berubah)</option>
                                </select>
                                <InputError :message="form.errors.status" />
                            </div>

                            <!-- Catatan -->
                            <div class="col-span-1 md:col-span-2 space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Catatan</label>
                                <textarea v-model="form.catatan" rows="3" class="input-base font-bold py-3" placeholder="Deskripsi/alasan penyesuaian stok..."></textarea>
                                <InputError :message="form.errors.catatan" />
                            </div>
                        </div>
                    </div>

                    <!-- Items Card -->
                    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 md:p-10">
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
                                    <PhListPlus :size="20" weight="fill" />
                                </div>
                                <div>
                                    <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Daftar Item Penyesuaian</h2>
                                    <p v-if="form.errors.items" class="text-[10px] text-rose-500 font-bold mt-1 uppercase">{{ form.errors.items }}</p>
                                </div>
                            </div>
                            <button @click="addItem" type="button" class="hidden sm:flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-widest px-4 py-2 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                <PhPlus weight="bold" /> Tambah Baris
                            </button>
                        </div>

                        <!-- Warehouse Warning when Empty -->
                        <div v-if="!form.warehouse_id" class="p-6 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 font-bold text-xs uppercase tracking-tight text-center">
                            ⚠️ Silakan pilih Gudang terlebih dahulu untuk memuat data stok sistem.
                        </div>

                        <div v-else-if="isLoadingStocks" class="p-8 text-center text-slate-400 font-bold text-xs uppercase tracking-widest">
                            Memuat data stok sistem gudang...
                        </div>

                        <template v-else>
                            <!-- Mobile Items View -->
                            <div class="block md:hidden space-y-4">
                                <div v-for="(item, index) in form.items" :key="index" class="p-6 rounded-2xl border-2 border-slate-50 bg-white shadow-sm space-y-3">
                                    <div class="flex justify-between items-start">
                                        <div class="w-full">
                                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Produk #{{ index + 1 }}</label>
                                            <select v-model="item.product_id" @change="handleProductChange(index)" class="input-base !py-2 text-xs font-black">
                                                <option value="">Pilih Produk...</option>
                                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.nama }} ({{ p.satuan }})</option>
                                            </select>
                                        </div>
                                        <button @click="removeItem(index)" type="button" class="ml-4 p-2 text-rose-400 hover:text-rose-600">
                                            <PhTrash :size="18" weight="bold" />
                                        </button>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Sistem</label>
                                            <input type="number" v-model="item.quantity_sistem" disabled class="input-base !py-2 text-xs font-black text-center bg-slate-50">
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Fisik</label>
                                            <input type="number" v-model="item.quantity_fisik" @input="handleQuantityFisikChange(index)" min="0" class="input-base !py-2 text-xs font-black text-center">
                                        </div>
                                        <div>
                                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Selisih</label>
                                            <input type="text" :value="item.selisih > 0 ? '+' + item.selisih : item.selisih" disabled :class="[
                                                'input-base !py-2 text-xs font-black text-center border-none rounded-lg',
                                                item.selisih > 0 ? 'bg-emerald-50 text-emerald-700' : (item.selisih < 0 ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-600')
                                            ]">
                                        </div>
                                    </div>
                                </div>
                                
                                <button @click="addItem" type="button" class="w-full py-4 border-2 border-dashed border-indigo-100 text-indigo-600 font-black text-xs uppercase tracking-[0.2em] rounded-2xl hover:bg-indigo-50 transition flex items-center justify-center gap-2 active:scale-95">
                                    <PhPlus weight="bold" /> Tambah Baris
                                </button>
                            </div>

                            <!-- Desktop Table View -->
                            <div class="hidden md:block overflow-x-auto">
                                <table class="w-full text-left border-collapse font-sans">
                                    <thead>
                                        <tr class="border-b border-slate-50">
                                            <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest">Produk</th>
                                            <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-32 text-center">Stok Sistem</th>
                                            <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-32 text-center">Stok Fisik</th>
                                            <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-32 text-center">Selisih</th>
                                            <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-12 text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-slate-50/50 group transition-all">
                                            <td class="py-4 pr-4">
                                                <select v-model="form.items[index].product_id" @change="handleProductChange(index)" class="input-base !py-2 text-xs font-black" :class="{'border-rose-500': form.errors[`items.${index}.product_id`]}">
                                                    <option value="">Pilih Produk...</option>
                                                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.nama }} ({{ p.satuan }})</option>
                                                </select>
                                                <div v-if="form.errors[`items.${index}.product_id`]" class="text-[9px] text-rose-500 font-bold mt-1 uppercase">{{ form.errors[`items.${index}.product_id`] }}</div>
                                            </td>
                                            <td class="py-4 px-2">
                                                <input type="number" v-model="form.items[index].quantity_sistem" disabled class="input-base !py-2 text-xs font-black text-center bg-slate-50">
                                            </td>
                                            <td class="py-4 px-2">
                                                <input type="number" v-model="form.items[index].quantity_fisik" @input="handleQuantityFisikChange(index)" min="0" class="input-base !py-2 text-xs font-black text-center" :class="{'border-rose-500': form.errors[`items.${index}.quantity_fisik`]}">
                                                <div v-if="form.errors[`items.${index}.quantity_fisik`]" class="text-[9px] text-rose-500 font-bold mt-1 uppercase text-center">{{ form.errors[`items.${index}.quantity_fisik`] }}</div>
                                            </td>
                                            <td class="py-4 px-2 text-center">
                                                <span :class="[
                                                    'inline-block px-3 py-1.5 rounded-lg text-xs font-black text-center min-w-[70px]',
                                                    form.items[index].selisih > 0 ? 'bg-emerald-50 text-emerald-700' : (form.items[index].selisih < 0 ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-600')
                                                ]">
                                                    {{ form.items[index].selisih > 0 ? '+' + form.items[index].selisih : form.items[index].selisih }}
                                                </span>
                                            </td>
                                            <td class="py-4 text-center">
                                                <button @click="removeItem(index)" type="button" class="text-slate-300 hover:text-rose-500 transition-all active:scale-90">
                                                    <PhTrash :size="18" weight="bold" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <button @click="addItem" type="button" class="w-full mt-6 py-4 border-2 border-dashed border-indigo-50 text-indigo-400 font-black text-xs uppercase tracking-[0.2em] rounded-2xl hover:border-indigo-100 hover:text-indigo-600 transition flex items-center justify-center gap-2">
                                    <PhPlus weight="bold" /> Tambah Baris Baru
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Right Column: Summary Card -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-indigo-900 text-white p-6 md:p-8 rounded-[2.5rem] shadow-2xl shadow-indigo-100">
                        <div class="flex items-center gap-2 mb-6">
                            <PhClipboardText :size="18" class="text-indigo-400 min-w-max" />
                            <p class="text-[10px] font-black uppercase tracking-widest text-indigo-300">Ringkasan Opname</p>
                        </div>

                        <div class="space-y-4 mb-8">
                            <div class="flex justify-between items-center bg-white/5 p-4 rounded-2xl border border-white/10 gap-2">
                                <span class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Total Item Barang</span>
                                <span class="text-sm font-black">{{ form.items.length }}</span>
                            </div>
                            <div class="flex justify-between items-center bg-white/5 p-4 rounded-2xl border border-white/10 gap-2">
                                <span class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Total Qty Sistem</span>
                                <span class="text-sm font-black">{{ totalSistem }} Pcs</span>
                            </div>
                            <div class="flex justify-between items-center bg-white/5 p-4 rounded-2xl border border-white/10 gap-2">
                                <span class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Total Qty Fisik</span>
                                <span class="text-sm font-black">{{ totalFisik }} Pcs</span>
                            </div>
                            <div class="flex justify-between items-center bg-white/5 p-4 rounded-2xl border border-white/10 gap-2">
                                <span class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Akumulasi Selisih</span>
                                <span :class="[
                                    'text-sm font-black px-2 py-0.5 rounded',
                                    totalSelisih > 0 ? 'text-emerald-300' : (totalSelisih < 0 ? 'text-rose-300' : 'text-slate-300')
                                ]">
                                    {{ totalSelisih > 0 ? '+' + totalSelisih : totalSelisih }} Pcs
                                </span>
                            </div>
                        </div>

                        <!-- Info box about stock execution -->
                        <div class="bg-indigo-950/60 p-4 rounded-2xl border border-indigo-800/40 text-[11px] leading-relaxed text-indigo-200 font-bold mb-8 flex gap-3">
                            <PhInfo :size="20" class="text-indigo-400 shrink-0" />
                            <p>
                                <span class="text-white block mb-0.5">Catatan Eksekusi:</span>
                                Jika status diset ke <strong>Selesai</strong>, sistem akan merekonsiliasi stok fisik dan sistem via modul <code>adjustStock</code>.
                            </p>
                        </div>

                        <button 
                            @click="submit" 
                            :disabled="form.processing || form.items.length === 0" 
                            class="w-full bg-white text-indigo-900 hover:bg-indigo-50 font-black text-xs uppercase tracking-widest py-4 md:py-5 px-2 rounded-2xl shadow-xl transition-all active:scale-95 disabled:opacity-20 leading-snug"
                        >
                            <span v-if="form.processing">MEMPROSES...</span>
                            <span v-else>Simpan Perubahan</span>
                        </button>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    </div>
</template>
