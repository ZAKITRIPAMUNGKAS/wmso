<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { 
    PhArrowLeft, 
    PhTruck, 
    PhListDashes, 
    PhPlus, 
    PhTrash, 
    PhWarningCircle, 
    PhFilePdf,
    PhPackage,
    PhCalendar,
    PhUser,
    PhWarehouse,
    PhReceipt,
    PhShieldCheck
} from "@phosphor-icons/vue";
import { computed, watch } from 'vue';

const props = defineProps({
    customers: Array,
    warehouses: Array,
    products: Array
});

const form = useForm({
    customer_id: '',
    warehouse_id: '',
    po_number: '',
    tanggal: new Date().toISOString().split('T')[0],
    payment_term: 'cash',
    jenis_pembayaran: 'cash',
    tempo_hari: 30,
    keterangan: '',
    items: [],
    due_date: ''
});

const addItem = () => {
    form.items.push({
        product_id: '',
        quantity: 1,
        harga: 0,
        subtotal: 0,
        current_stock: 0
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const onProductChange = (index) => {
    const item = form.items[index];
    const product = props.products.find(p => p.id === item.product_id);
    if (product) {
        item.harga = product.harga;
        // Use a more robust way to get stock
        item.current_stock = product.stocks[form.warehouse_id] || product.stocks[String(form.warehouse_id)] || 0;
        updateSubtotal(index);
    }
};

// Also update stock if warehouse changes
watch(() => form.warehouse_id, (newWarehouseId) => {
    form.items.forEach((item) => {
        if (item.product_id) {
            const product = props.products.find(p => p.id === item.product_id);
            if (product && product.stocks) {
                item.current_stock = product.stocks[newWarehouseId] || product.stocks[String(newWarehouseId)] || 0;
            }
        }
    });
});

const updateSubtotal = (index) => {
    const item = form.items[index];
    item.subtotal = item.quantity * item.harga;
};

const grandTotal = computed(() => {
    return form.items.reduce((acc, item) => acc + (parseFloat(item.subtotal) || 0), 0);
});

const stockShortage = computed(() => {
    return form.items.some(item => item.quantity > item.current_stock);
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        total: grandTotal.value
    })).post(route('barang-keluar.store'));
};
</script>

<template>
    <Head title="Barang Keluar - Baru" />

    <AuthenticatedLayout title="Barang Keluar">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 font-sans">
            <div class="flex items-center gap-4">
                <Link :href="route('barang-keluar.index')" class="p-2.5 bg-white border border-slate-200 rounded-xl text-slate-400 hover:text-indigo-600 transition active:scale-90">
                    <PhArrowLeft :size="20" weight="bold" />
                </Link>
                <div>
                    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Pengiriman Baru</h2>
                    <p class="text-[11px] text-slate-500 font-bold mt-1 uppercase tracking-tight">Input Surat Jalan & Distribusi</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8 font-sans">
            <div class="lg:col-span-8 space-y-8">
                <!-- Header Info -->
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 md:p-10">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
                            <PhTruck :size="20" weight="fill" />
                        </div>
                        <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Informasi Pengiriman</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">No. Surat Jalan</label>
                            <div class="input-base !bg-slate-50 !border-slate-100 text-slate-400 font-black italic">AUTO-GENERATE</div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Tanggal</label>
                            <input type="date" v-model="form.tanggal" class="input-base font-black">
                            <InputError :message="form.errors.tanggal" />
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Gudang Asal</label>
                            <select v-model="form.warehouse_id" class="input-base font-black">
                                <option value="">Pilih Gudang...</option>
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.nama }}</option>
                            </select>
                            <InputError :message="form.errors.warehouse_id" />
                        </div>
                        <div class="md:col-span-2 space-y-2 text-indigo-600">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Customer</label>
                            <select v-model="form.customer_id" class="input-base font-black">
                                <option value="">Pilih Customer...</option>
                                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.nama }}</option>
                            </select>
                            <InputError :message="form.errors.customer_id" />
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nomor PO / Referensi</label>
                            <input type="text" v-model="form.po_number" placeholder="Contoh: PO-2026-0042" class="input-base font-black placeholder:text-slate-300">
                            <InputError :message="form.errors.po_number" />
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Jenis Pembayaran <span class="text-rose-400">*</span></label>
                            <select v-model="form.jenis_pembayaran" class="input-base font-black">
                                <option value="cash">💵 Cash (Langsung Lunas)</option>
                                <option value="tempo">🗓️ Tempo (Kredit)</option>
                            </select>
                            <InputError :message="form.errors.jenis_pembayaran" />
                        </div>
                        <div v-if="form.jenis_pembayaran === 'tempo'" class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Tempo (Hari)</label>
                            <div class="flex gap-2 items-center">
                                <input type="number" v-model="form.tempo_hari" min="1" max="180" class="input-base font-black w-24 text-center">
                                <div class="flex gap-1">
                                    <button type="button" @click="form.tempo_hari = 14" class="px-2.5 py-2 text-[9px] font-black bg-slate-100 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition">14</button>
                                    <button type="button" @click="form.tempo_hari = 30" class="px-2.5 py-2 text-[9px] font-black bg-slate-100 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition">30</button>
                                    <button type="button" @click="form.tempo_hari = 45" class="px-2.5 py-2 text-[9px] font-black bg-slate-100 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition">45</button>
                                    <button type="button" @click="form.tempo_hari = 60" class="px-2.5 py-2 text-[9px] font-black bg-slate-100 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition">60</button>
                                </div>
                            </div>
                            <p class="text-[9px] text-slate-400 font-bold ml-1">Jatuh tempo: {{ form.tanggal ? new Date(new Date(form.tanggal).getTime() + form.tempo_hari * 86400000).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'}) : '-' }}</p>
                            <InputError :message="form.errors.tempo_hari" />
                        </div>
                        <div v-else class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Status Bayar</label>
                            <div class="input-base !bg-emerald-50 !border-emerald-100 font-black text-emerald-700 flex items-center gap-2">
                                ✓ Lunas hari ini
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Section -->
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 md:p-10">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
                                <PhListDashes :size="20" weight="fill" />
                            </div>
                            <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Detail Produk</h2>
                        </div>
                        <button @click="addItem" class="hidden sm:flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-widest px-4 py-2 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                            <PhPlus weight="bold" /> Tambah Baris
                        </button>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="block md:hidden space-y-4">
                        <div v-for="(item, index) in form.items" :key="index" :class="['p-6 rounded-2xl border-2 transition-all', item.quantity > item.current_stock ? 'border-rose-100 bg-rose-50/30' : 'border-slate-50 bg-white']">
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-full">
                                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Produk #{{ index + 1 }}</label>
                                    <select v-model="item.product_id" @change="onProductChange(index)" class="input-base !py-2 text-xs font-black">
                                        <option value="">Pilih Produk...</option>
                                        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.nama }}</option>
                                    </select>
                                </div>
                                <button @click="removeItem(index)" class="ml-4 p-2 text-rose-400 hover:text-rose-600">
                                    <PhTrash :size="18" weight="bold" />
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Qty</label>
                                    <input type="number" v-model="item.quantity" @input="updateSubtotal(index)" class="input-base !py-2 text-xs font-black" :class="item.quantity > item.current_stock ? 'border-rose-300' : ''">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Harga Satuan</label>
                                    <input type="number" v-model="item.harga" @input="updateSubtotal(index)" class="input-base !py-2 text-xs font-black">
                                </div>
                            </div>
                            <div class="flex justify-between items-center pt-4 border-t border-slate-100">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Subtotal</span>
                                <span class="text-sm font-black text-slate-900 tracking-tight">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.subtotal) }}</span>
                            </div>
                        </div>
                        <button @click="addItem" class="w-full py-4 border-2 border-dashed border-indigo-100 text-indigo-600 font-black text-xs uppercase tracking-[0.2em] rounded-2xl hover:bg-indigo-50 transition flex items-center justify-center gap-2 active:scale-95">
                            <PhPlus weight="bold" /> Tambah Baris Produk
                        </button>
                    </div>

                    <!-- Desktop Table -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-50">
                                    <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest">Produk</th>
                                    <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-24">Stok</th>
                                    <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-24">Qty</th>
                                    <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-40 text-right">Harga</th>
                                    <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-40 text-right">Subtotal</th>
                                    <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-12 text-center"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="(item, index) in form.items" :key="index" :class="['group transition-all', item.quantity > item.current_stock ? 'bg-rose-50/20' : 'hover:bg-slate-50/50']">
                                    <td class="py-4 pr-4">
                                        <select v-model="item.product_id" @change="onProductChange(index)" class="input-base !py-2 text-xs font-black">
                                            <option value="">Pilih Produk...</option>
                                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.nama }}</option>
                                        </select>
                                    </td>
                                    <td class="py-4 pr-4">
                                        <div v-if="!form.warehouse_id" class="flex items-center gap-1 text-[9px] font-black text-amber-500 uppercase italic">
                                            <PhWarningCircle :size="12" /> Pilih Gudang
                                        </div>
                                        <span v-else :class="[
                                            'text-[10px] font-black uppercase tracking-tighter px-2 py-1 rounded-lg',
                                            item.quantity > item.current_stock ? 'bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-500'
                                        ]">
                                            {{ item.current_stock }} Pcs
                                        </span>
                                    </td>
                                    <td class="py-4 pr-4">
                                        <input type="number" v-model="item.quantity" @input="updateSubtotal(index)" class="input-base !py-2 text-xs font-black text-center" :class="item.quantity > item.current_stock ? 'border-rose-300 !text-rose-600' : ''">
                                    </td>
                                    <td class="py-4 pr-4">
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400">Rp</span>
                                            <input type="number" v-model="item.harga" @input="updateSubtotal(index)" class="input-base !py-2 !pl-8 text-xs font-black text-right">
                                        </div>
                                    </td>
                                    <td class="py-4 pr-4 font-black text-slate-900 text-sm text-right">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.subtotal) }}</td>
                                    <td class="py-4 text-center">
                                        <button @click="removeItem(index)" class="text-slate-300 hover:text-rose-500 transition-all active:scale-90"><PhTrash :size="18" weight="bold" /></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <button @click="addItem" class="w-full mt-6 py-4 border-2 border-dashed border-indigo-50 text-indigo-400 font-black text-xs uppercase tracking-[0.2em] rounded-2xl hover:border-indigo-100 hover:text-indigo-600 transition flex items-center justify-center gap-2">
                            <PhPlus weight="bold" /> Tambah Baris
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="lg:col-span-4">
                <div class="sticky top-8 space-y-6">
                    <div class="bg-slate-900 text-white p-8 rounded-[2.5rem] shadow-2xl shadow-slate-200">
                        <div class="flex items-center gap-2 mb-6">
                            <PhReceipt :size="18" class="text-indigo-400" />
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Tagihan</p>
                        </div>
                        <h2 class="text-4xl font-black text-white mb-8 tracking-tighter">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(grandTotal) }}</h2>
                        
                        <div v-if="stockShortage" class="bg-rose-500/10 border border-rose-500/30 rounded-2xl p-4 flex gap-3 items-start mb-8">
                            <PhWarningCircle :size="20" weight="fill" class="text-rose-500 mt-0.5 shrink-0" />
                            <p class="text-[10px] text-rose-200 font-bold leading-relaxed uppercase tracking-wider">Peringatan: Stok tidak mencukupi untuk beberapa item terpilih.</p>
                        </div>

                        <div class="space-y-4 mb-8">
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">Catatan Internal</label>
                            <textarea v-model="form.keterangan" rows="3" class="w-full bg-slate-800 border border-slate-700 text-white rounded-2xl px-5 py-4 text-xs font-bold outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all resize-none italic" placeholder="Contoh: Titipan pengiriman..."></textarea>
                        </div>

                        <button @click="submit" :disabled="form.processing || stockShortage || form.items.length === 0" class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-black text-xs uppercase tracking-[0.2em] py-5 rounded-2xl shadow-xl shadow-indigo-500/20 transition-all active:scale-95 disabled:opacity-20 disabled:cursor-not-allowed">
                            {{ form.processing ? 'Sedang Memproses...' : 'Proses Surat Jalan' }}
                        </button>
                    </div>
                    
                    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 flex items-center gap-4">
                        <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600">
                            <PhShieldCheck :size="20" weight="fill" />
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Validasi Otomatis</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-tight mt-0.5">Stok & Piutang Diverifikasi</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
