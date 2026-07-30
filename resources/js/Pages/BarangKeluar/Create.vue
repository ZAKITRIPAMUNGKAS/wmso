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
    PhShieldCheck,
    PhQrCode
} from "@phosphor-icons/vue";
import { computed, watch, ref } from 'vue';

const props = defineProps({
    customers: Array,
    warehouses: Array,
    products: Array,
    racks: Array
});

const formatNumber = (num) => {
    if (!num && num !== 0) return '';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
};

const parseNumber = (str) => {
    return parseInt(str.replace(/\./g, '')) || 0;
};

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
    due_date: '',
    courier_name: '',
    tracking_number: ''
});

const addItem = () => {
    if (!form.warehouse_id) {
        alert("❌ Silakan pilih GUDANG ASAL terlebih dahulu!");
        return;
    }
    form.items.push({
        product_id: '',
        quantity: 1,
        harga: 0,
        subtotal: 0,
        current_stock: 0,
        rack_id: '',
        batch_number: '',
        expired_at: '',
        serial_number: '',
        available_racks: [],
        selected_rack_stock_id: ''
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const onProductChange = (index) => {
    const item = form.items[index];
    if (!item.product_id) {
        item.current_stock = 0;
        item.harga = 0;
        item.subtotal = 0;
        item.rack_id = '';
        item.batch_number = '';
        item.expired_at = '';
        item.serial_number = '';
        item.available_racks = [];
        item.selected_rack_stock_id = '';
        return;
    }
    if (!form.warehouse_id) {
        alert("❌ Silakan pilih GUDANG ASAL terlebih dahulu!");
        item.product_id = '';
        return;
    }
    const product = props.products.find(p => p.id === item.product_id);
    if (product) {
        const stock = product.stocks[form.warehouse_id] || product.stocks[String(form.warehouse_id)] || 0;
        if (stock < 1) {
            alert(`❌ Stok produk "${product.nama}" tidak tersedia di gudang ini (0 Pcs)!`);
            item.product_id = '';
            item.current_stock = 0;
            item.quantity = 1;
            updateSubtotal(index);
            return;
        }
        item.harga = product.harga;
        item.current_stock = stock;
        
        // Populate available detailed stocks from product.rack_stocks filtered by current warehouse
        item.available_racks = (product.rack_stocks || []).filter(rs => rs.warehouse_id === form.warehouse_id && rs.quantity > 0);

        if (item.quantity > stock) {
            item.quantity = stock;
        }
        
        // Reset selections initially
        item.rack_id = '';
        item.batch_number = '';
        item.expired_at = '';
        item.serial_number = '';
        item.selected_rack_stock_id = '';

        updateSubtotal(index);
    }
};

const onRackStockChange = (index) => {
    const item = form.items[index];
    if (!item.selected_rack_stock_id) {
        item.rack_id = '';
        item.batch_number = '';
        item.expired_at = '';
        item.serial_number = '';
        return;
    }
    const rs = item.available_racks.find(r => r.id === item.selected_rack_stock_id);
    if (rs) {
        item.rack_id = rs.rack_id;
        item.batch_number = rs.batch_number;
        item.expired_at = rs.expired_at;
        item.serial_number = rs.serial_number;
        
        // If current quantity exceeds this rack's stock, cap it
        if (item.quantity > rs.quantity) {
            item.quantity = rs.quantity;
            updateSubtotal(index);
        }
    }
};

const suggestFIFO = (index) => {
    const item = form.items[index];
    if (!item.available_racks || item.available_racks.length === 0) {
        alert("❌ Tidak ada informasi detail stok untuk barang ini.");
        return;
    }
    // Sort by expired_at asc (null expiry last) and then by created_at asc
    const sorted = [...item.available_racks].sort((a, b) => {
        if (a.expired_at && b.expired_at) {
            return new Date(a.expired_at) - new Date(b.expired_at);
        }
        if (a.expired_at) return -1;
        if (b.expired_at) return 1;
        
        // Fallback to creation date or id
        return (a.id - b.id);
    });
    
    const bestStock = sorted[0];
    if (bestStock) {
        item.selected_rack_stock_id = bestStock.id;
        onRackStockChange(index);
        showScannerFeedback(`✓ FIFO menyarankan Rak: ${bestStock.kode_rak}, Batch: ${bestStock.batch_number || '-'}`, 'success');
    }
};

// Also update stock if warehouse changes
watch(() => form.warehouse_id, (newWarehouseId) => {
    if (!newWarehouseId) {
        form.items = [];
        return;
    }
    form.items.forEach((item, index) => {
        if (item.product_id) {
            const product = props.products.find(p => p.id === item.product_id);
            if (product && product.stocks) {
                const newStock = product.stocks[newWarehouseId] || product.stocks[String(newWarehouseId)] || 0;
                item.current_stock = newStock;
                if (newStock === 0) {
                    alert(`❌ Stok produk "${product.nama}" tidak tersedia di gudang yang baru dipilih! Baris ini akan dikosongkan.`);
                    item.product_id = '';
                    item.quantity = 1;
                    item.harga = 0;
                    item.subtotal = 0;
                } else if (item.quantity > newStock) {
                    alert(`⚠️ Stok produk "${product.nama}" di gudang baru terbatas (${newStock} Pcs). Jumlah disesuaikan.`);
                    item.quantity = newStock;
                    updateSubtotal(index);
                }
            }
        }
    });
});

const updateSubtotal = (index) => {
    const item = form.items[index];
    item.subtotal = item.quantity * item.harga;
};

const onQuantityInput = (index) => {
    updateSubtotal(index);
};

const validateQuantity = (index) => {
    const item = form.items[index];
    if (!item.product_id) return;
    
    let qty = parseInt(item.quantity);
    if (isNaN(qty) || qty < 1) {
        qty = 1;
    }
    
    if (qty > item.current_stock) {
        alert(`❌ Stok tidak mencukupi! Hanya tersedia ${item.current_stock} Pcs.`);
        qty = item.current_stock;
    }
    
    // Check specific rack stock if selected
    if (item.selected_rack_stock_id) {
        const rs = item.available_racks.find(r => r.id === item.selected_rack_stock_id);
        if (rs && qty > rs.quantity) {
            alert(`❌ Stok pada lokasi/batch terpilih terbatas! Hanya tersedia ${rs.quantity} Pcs.`);
            qty = rs.quantity;
        }
    }
    
    item.quantity = qty;
    updateSubtotal(index);
};

const barcodeQuery = ref('');
const barcodeInput = ref(null);
const scannerFeedback = ref(null);

const handleBarcodeScan = () => {
    if (!form.warehouse_id) {
        showScannerFeedback('❌ Silakan pilih GUDANG ASAL terlebih dahulu!', 'error');
        return;
    }

    const code = barcodeQuery.value.trim().toUpperCase();
    if (!code) return;

    const product = props.products.find(p => 
        (p.kode_barang && p.kode_barang.toUpperCase() === code) || 
        (p.barcode && p.barcode.toUpperCase() === code)
    );

    if (product) {
        const existingIndex = form.items.findIndex(item => item.product_id === product.id);
        const currentStock = product.stocks[form.warehouse_id] || product.stocks[String(form.warehouse_id)] || 0;
        const nextQty = existingIndex !== -1 ? (parseInt(form.items[existingIndex].quantity) || 0) + 1 : 1;

        if (nextQty > currentStock) {
            showScannerFeedback(`❌ Stok tidak mencukupi! Tersedia: ${currentStock} Pcs`, 'error');
        } else {
            if (existingIndex !== -1) {
                form.items[existingIndex].quantity = nextQty;
                updateSubtotal(existingIndex);
            } else {
                const availableRacks = (product.rack_stocks || []).filter(rs => rs.warehouse_id === form.warehouse_id && rs.quantity > 0);
                form.items.push({
                    product_id: product.id,
                    quantity: 1,
                    harga: product.harga || 0,
                    subtotal: product.harga || 0,
                    current_stock: currentStock,
                    rack_id: '',
                    batch_number: '',
                    expired_at: '',
                    serial_number: '',
                    available_racks: availableRacks,
                    selected_rack_stock_id: ''
                });
                const newIdx = form.items.length - 1;
                suggestFIFO(newIdx);
            }
            showScannerFeedback(`✓ ${product.nama} berhasil ditambahkan! (Tersedia: ${currentStock - nextQty})`, 'success');
        }
    } else {
        showScannerFeedback(`❌ Kode barang atau barcode ${code} tidak ditemukan!`, 'error');
    }

    barcodeQuery.value = '';
    if (barcodeInput.value) {
        barcodeInput.value.focus();
    }
};

let feedbackTimeout = null;
const showScannerFeedback = (message, type) => {
    if (feedbackTimeout) clearTimeout(feedbackTimeout);
    scannerFeedback.value = { message, type };
    feedbackTimeout = setTimeout(() => {
        scannerFeedback.value = null;
    }, 3000);
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
                                <option value="cash">Cash (Langsung Lunas)</option>
                                <option value="tempo">Tempo (Kredit)</option>
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
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Kurir Pengiriman</label>
                            <select v-model="form.courier_name" class="input-base font-black">
                                <option value="">Tanpa Kurir (Ambil Sendiri)</option>
                                <option value="JNE">JNE Express</option>
                                <option value="POS">POS Indonesia</option>
                                <option value="TIKI">TIKI</option>
                                <option value="SICEPAT">SiCepat</option>
                                <option value="JNT">J&T Express</option>
                            </select>
                            <InputError :message="form.errors.courier_name" />
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nomor Resi / Tracking</label>
                            <input type="text" v-model="form.tracking_number" placeholder="Contoh: JT123456789" class="input-base font-black placeholder:text-slate-300">
                            <InputError :message="form.errors.tracking_number" />
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

                    <!-- Barcode Scanner Input -->
                    <div class="mb-6 flex flex-col sm:flex-row gap-4 items-center">
                        <div class="relative w-full">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                                <PhQrCode :size="20" />
                            </span>
                            <input 
                                type="text" 
                                v-model="barcodeQuery" 
                                @keydown.enter.prevent="handleBarcodeScan" 
                                placeholder="Scan Barcode / QR Code..." 
                                class="input-base !pl-12 font-black uppercase tracking-wider"
                                ref="barcodeInput"
                                autofocus
                            >
                        </div>
                        <div v-if="scannerFeedback" :class="scannerFeedback.type === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100'" class="w-full sm:w-auto px-4 py-2.5 rounded-xl border text-xs font-bold whitespace-nowrap flex items-center gap-1.5 transition-all">
                            {{ scannerFeedback.message }}
                        </div>
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
                                    <!-- Stock indicator on mobile -->
                                    <div class="mt-2 flex items-center justify-between">
                                        <div v-if="!form.warehouse_id" class="flex items-center gap-1 text-[9px] font-black text-amber-500 uppercase italic">
                                            <PhWarningCircle :size="12" /> Pilih Gudang
                                        </div>
                                        <div v-else-if="item.product_id" class="text-[10px] font-black">
                                            <span :class="[
                                                'px-2 py-0.5 rounded-lg text-[9px] uppercase tracking-tighter font-black',
                                                item.quantity > item.current_stock ? 'bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-500'
                                            ]">
                                                Tersedia: {{ item.current_stock }} Pcs
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <button @click="removeItem(index)" class="ml-4 p-2 text-rose-400 hover:text-rose-600">
                                    <PhTrash :size="18" weight="bold" />
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Qty</label>
                                    <input type="number" v-model="item.quantity" @input="onQuantityInput(index)" @change="validateQuantity(index)" class="input-base !py-2 text-xs font-black" :class="item.quantity > item.current_stock ? 'border-rose-300' : ''">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Harga Satuan</label>
                                    <input type="text" :value="formatNumber(item.harga)" @input="item.harga = parseNumber($event.target.value); updateSubtotal(index)" class="input-base !py-2 text-xs font-black">
                                </div>
                            </div>
                            <!-- Detail Fields Mobile -->
                            <div v-if="item.product_id && item.product_id !== ''" class="mt-4 border-t-2 border-dashed border-indigo-50 pt-4 space-y-3">
                                <div class="flex items-center gap-1.5">
                                    <div class="h-1.5 w-1.5 rounded-full bg-indigo-500"></div>
                                    <span class="text-[9px] font-black text-indigo-500 uppercase tracking-widest">Detail Lokasi & Batch</span>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Pilih Lokasi & Batch</label>
                                    <div class="flex gap-2">
                                        <select v-model="item.selected_rack_stock_id" @change="onRackStockChange(index)" class="input-base !py-2 text-xs font-black">
                                            <option value="">-- Pilih Lokasi / Batch --</option>
                                            <option v-for="rs in item.available_racks" :key="rs.id" :value="rs.id">
                                                Rak: {{ rs.kode_rak }} | Batch: {{ rs.batch_number || '-' }} | Qty: {{ rs.quantity }}
                                            </option>
                                        </select>
                                        <button type="button" @click="suggestFIFO(index)" class="px-3 py-2 bg-indigo-600 text-white text-[10px] font-black uppercase tracking-wider rounded-xl transition shrink-0">
                                            FIFO
                                        </button>
                                    </div>
                                </div>
                                <div v-if="item.rack_id" class="flex flex-wrap gap-2 text-[9px] font-bold text-indigo-700 uppercase bg-indigo-50 p-2.5 rounded-xl border border-indigo-100">
                                    <span>Rak: {{ props.racks ? props.racks.find(r => r.id === item.rack_id)?.kode_rak : '' }}</span>
                                    <span v-if="item.batch_number">Batch: {{ item.batch_number }}</span>
                                    <span v-if="item.expired_at">Exp: {{ item.expired_at }}</span>
                                    <span v-if="item.serial_number">Serial: {{ item.serial_number }}</span>
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
                                <template v-for="(item, index) in form.items" :key="index">
                                    <!-- Main Product Row -->
                                    <tr class="hover:bg-slate-50/30 transition-all" :class="item.quantity > item.current_stock ? 'bg-rose-50/10' : ''">
                                        <td class="py-4 pr-4 align-top">
                                            <select v-model="item.product_id" @change="onProductChange(index)" class="input-base !py-2 text-xs font-black">
                                                <option value="">Pilih Produk...</option>
                                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.nama }}</option>
                                            </select>
                                        </td>
                                        <td class="py-4 pr-4 align-top pt-6">
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
                                        <td class="py-4 pr-4 align-top w-24">
                                            <input type="number" v-model="item.quantity" @input="onQuantityInput(index)" @change="validateQuantity(index)" class="input-base !py-2 text-xs font-black text-center" :class="item.quantity > item.current_stock ? 'border-rose-300 !text-rose-600' : ''">
                                        </td>
                                        <td class="py-4 pr-4 align-top w-40">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400">Rp</span>
                                                <input type="text" :value="formatNumber(item.harga)" @input="item.harga = parseNumber($event.target.value); updateSubtotal(index)" class="input-base !py-2 !pl-8 text-xs font-black text-right">
                                            </div>
                                        </td>
                                        <td class="py-4 pr-4 font-black text-slate-900 text-sm text-right align-top pt-6 w-40">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.subtotal) }}</td>
                                        <td class="py-4 text-center align-top w-12 pt-5">
                                            <button @click="removeItem(index)" class="text-slate-300 hover:text-rose-500 transition-all active:scale-90"><PhTrash :size="18" weight="bold" /></button>
                                        </td>
                                    </tr>
                                    <!-- Detailed Storage Row (Visible only when product selected) -->
                                    <tr v-if="item.product_id && item.product_id !== ''" class="">
                                        <td colspan="6" class="px-1 pb-4">
                                            <div class="bg-indigo-50/60 rounded-2xl border border-indigo-100/80 px-5 py-4">
                                                <div class="flex items-center gap-2 mb-4">
                                                    <div class="h-2 w-2 rounded-full bg-indigo-500"></div>
                                                    <span class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">Detail Lokasi & Batch Pengambilan</span>
                                                    <span class="text-[10px] font-bold text-slate-400 truncate ml-1">({{ products.find(p => p.id === item.product_id)?.nama }})</span>
                                                </div>
                                                <div class="flex flex-col gap-4">
                                                    <div class="flex gap-3 items-end">
                                                        <div class="flex-1 space-y-1.5">
                                                            <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest">Pilih Lokasi & Batch Terkini</label>
                                                            <select v-model="item.selected_rack_stock_id" @change="onRackStockChange(index)" class="w-full bg-white border border-indigo-100 text-slate-800 rounded-xl h-10 px-3 pr-8 text-xs font-bold outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-400 focus:bg-white transition-all">
                                                                <option value="">-- Pilih Lokasi / Batch --</option>
                                                                <option v-for="rs in item.available_racks" :key="rs.id" :value="rs.id">
                                                                    Rak: {{ rs.kode_rak }} | Batch: {{ rs.batch_number || '-' }} | Exp: {{ rs.expired_at || '-' }} | Qty: {{ rs.quantity }} Pcs
                                                                </option>
                                                            </select>
                                                        </div>
                                                        <button type="button" @click="suggestFIFO(index)" class="h-10 px-4 bg-indigo-600 hover:bg-indigo-700 text-white text-[10px] font-black uppercase tracking-wider rounded-xl transition flex items-center justify-center shrink-0 shadow-sm">
                                                            FIFO
                                                        </button>
                                                    </div>
                                                    <!-- Detailed Selection Read-Only Tag List -->
                                                    <div v-if="item.rack_id" class="flex flex-wrap gap-3 text-[9px] font-black text-indigo-700 uppercase tracking-wider bg-white px-4 py-3 rounded-xl border border-indigo-100">
                                                        <span class="flex items-center gap-1"><span class="text-slate-400">Rak:</span> {{ props.racks ? props.racks.find(r => r.id === item.rack_id)?.kode_rak : '' }}</span>
                                                        <span v-if="item.batch_number" class="flex items-center gap-1"><span class="text-slate-400">Batch:</span> {{ item.batch_number }}</span>
                                                        <span v-if="item.expired_at" class="flex items-center gap-1"><span class="text-slate-400">Exp:</span> {{ item.expired_at }}</span>
                                                        <span v-if="item.serial_number" class="flex items-center gap-1"><span class="text-slate-400">Serial:</span> {{ item.serial_number }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
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
