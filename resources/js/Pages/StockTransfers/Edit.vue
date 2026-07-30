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
    PhArrowsLeftRight,
    PhCalendar,
    PhWarehouse,
    PhInfo
} from "@phosphor-icons/vue";
import { computed, watch } from 'vue';

const props = defineProps({
    transfer: Object,
    warehouses: Array,
    products: Array,
    auth: Object,
    errors: Object
});

const form = useForm({
    source_warehouse_id: props.transfer.source_warehouse_id,
    destination_warehouse_id: props.transfer.destination_warehouse_id,
    tanggal: props.transfer.tanggal ? props.transfer.tanggal.substring(0, 10) : new Date().toISOString().split('T')[0],
    status: props.transfer.status,
    catatan: props.transfer.catatan || '',
    items: props.transfer.items.map(item => ({
        product_id: item.product_id,
        quantity: item.quantity
    }))
});

const addItem = () => {
    form.items.push({
        product_id: '',
        quantity: 1
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

// Check if source and destination warehouses are the same
watch(() => [form.source_warehouse_id, form.destination_warehouse_id], ([source, dest]) => {
    if (source && dest && source === dest) {
        alert('⚠️ Gudang asal dan gudang tujuan tidak boleh sama.');
        form.destination_warehouse_id = '';
    }
});

const totalQuantity = computed(() => {
    return form.items.reduce((acc, item) => acc + (parseInt(item.quantity) || 0), 0);
});

const submit = () => {
    if (!form.source_warehouse_id) {
        alert('⚠️ Mohon pilih GUDANG ASAL terlebih dahulu.');
        return;
    }
    if (!form.destination_warehouse_id) {
        alert('⚠️ Mohon pilih GUDANG TUJUAN terlebih dahulu.');
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

    form.put(route('stock-transfers.update', props.transfer.id), {
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
        <Head title="Edit Transfer Stok" />

        <AuthenticatedLayout title="Transfer Stok">
            <!-- Back & Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 font-sans">
                <div class="flex items-center gap-4">
                    <Link :href="route('stock-transfers.index')" class="p-2.5 bg-white border border-slate-200 rounded-xl text-slate-400 hover:text-indigo-600 transition active:scale-90">
                        <PhArrowLeft :size="20" weight="bold" />
                    </Link>
                    <div>
                        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Transfer Stok</h2>
                        <p class="text-[11px] text-slate-500 font-bold mt-1 uppercase tracking-tight">Edit Transfer Stok: {{ transfer.no_transfer }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8 font-sans">
                <!-- Left Column: Form Details & Items -->
                <div class="lg:col-span-8 space-y-8">
                    <!-- Transfer Details Card -->
                    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 md:p-10">
                        <div class="flex items-center gap-3 mb-8">
                            <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
                                <PhFileText :size="20" weight="fill" />
                            </div>
                            <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Informasi Transfer Stok</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Source Warehouse -->
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                    Gudang Asal <span class="text-rose-400">*</span>
                                </label>
                                <select v-model="form.source_warehouse_id" class="input-base font-black">
                                    <option value="">Pilih Gudang Asal...</option>
                                    <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.nama }}</option>
                                </select>
                                <InputError :message="form.errors.source_warehouse_id" />
                            </div>

                            <!-- Destination Warehouse -->
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                    Gudang Tujuan <span class="text-rose-400">*</span>
                                </label>
                                <select v-model="form.destination_warehouse_id" class="input-base font-black">
                                    <option value="">Pilih Gudang Tujuan...</option>
                                    <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.nama }}</option>
                                </select>
                                <InputError :message="form.errors.destination_warehouse_id" />
                            </div>

                            <!-- Tanggal -->
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                    Tanggal Transfer <span class="text-rose-400">*</span>
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
                                <textarea v-model="form.catatan" rows="3" class="input-base font-bold py-3" placeholder="Deskripsi/catatan tambahan terkait mutasi stok..."></textarea>
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
                                    <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Daftar Item Produk</h2>
                                    <p v-if="form.errors.items" class="text-[10px] text-rose-500 font-bold mt-1 uppercase">{{ form.errors.items }}</p>
                                </div>
                            </div>
                            <button @click="addItem" type="button" class="hidden sm:flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-widest px-4 py-2 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                                <PhPlus weight="bold" /> Tambah Baris
                            </button>
                        </div>

                        <!-- Mobile Items View -->
                        <div class="block md:hidden space-y-4">
                            <div v-for="(item, index) in form.items" :key="index" class="p-6 rounded-2xl border-2 border-slate-50 bg-white shadow-sm">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="w-full">
                                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Produk #{{ index + 1 }}</label>
                                        <select v-model="item.product_id" class="input-base !py-2 text-xs font-black">
                                            <option value="">Pilih Produk...</option>
                                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.nama }} ({{ p.satuan }})</option>
                                        </select>
                                    </div>
                                    <button @click="removeItem(index)" type="button" class="ml-4 p-2 text-rose-400 hover:text-rose-600">
                                        <PhTrash :size="18" weight="bold" />
                                    </button>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Jumlah (Qty)</label>
                                    <input type="number" v-model="item.quantity" min="1" class="input-base !py-2 text-xs font-black text-center">
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
                                        <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-40 text-center">Jumlah (Qty)</th>
                                        <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-12 text-center"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-slate-50/50 group transition-all">
                                        <td class="py-4 pr-4">
                                            <select v-model="form.items[index].product_id" class="input-base !py-2 text-xs font-black" :class="{'border-rose-500': form.errors[`items.${index}.product_id`]}">
                                                <option value="">Pilih Produk...</option>
                                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.nama }} ({{ p.satuan }})</option>
                                            </select>
                                            <div v-if="form.errors[`items.${index}.product_id`]" class="text-[9px] text-rose-500 font-bold mt-1 uppercase">{{ form.errors[`items.${index}.product_id`] }}</div>
                                        </td>
                                        <td class="py-4 px-4">
                                            <input type="number" v-model="form.items[index].quantity" min="1" class="input-base !py-2 text-xs font-black text-center mx-auto max-w-[150px]" :class="{'border-rose-500': form.errors[`items.${index}.quantity`]}">
                                            <div v-if="form.errors[`items.${index}.quantity`]" class="text-[9px] text-rose-500 font-bold mt-1 uppercase text-center">{{ form.errors[`items.${index}.quantity`] }}</div>
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
                    </div>
                </div>

                <!-- Right Column: Summary Card -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-indigo-900 text-white p-6 md:p-8 rounded-[2.5rem] shadow-2xl shadow-indigo-100">
                        <div class="flex items-center gap-2 mb-6">
                            <PhArrowsLeftRight :size="18" class="text-indigo-400 min-w-max" />
                            <p class="text-[10px] font-black uppercase tracking-widest text-indigo-300">Ringkasan Mutasi</p>
                        </div>

                        <div class="space-y-4 mb-8">
                            <div class="flex justify-between items-center bg-white/5 p-4 rounded-2xl border border-white/10 gap-2">
                                <span class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Total Jenis Barang</span>
                                <span class="text-sm font-black">{{ form.items.length }}</span>
                            </div>
                            <div class="flex justify-between items-center bg-white/5 p-4 rounded-2xl border border-white/10 gap-2">
                                <span class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Total Qty Transfer</span>
                                <span class="text-sm font-black">{{ totalQuantity }} Pcs</span>
                            </div>
                        </div>

                        <!-- Info box about stock execution -->
                        <div class="bg-indigo-950/60 p-4 rounded-2xl border border-indigo-800/40 text-[11px] leading-relaxed text-indigo-200 font-bold mb-8 flex gap-3">
                            <PhInfo :size="20" class="text-indigo-400 shrink-0" />
                            <p>
                                <span class="text-white block mb-0.5">Catatan Eksekusi:</span>
                                Jika status diset ke <strong>Selesai</strong>, sistem akan secara otomatis mengurangi stok di gudang asal dan menambahkannya di gudang tujuan. Pastikan stok mencukupi.
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
