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
    PhUploadSimple, 
    PhChatCircleDots,
    PhPackage,
    PhCalendar,
    PhWarehouse,
    PhReceipt,
    PhShieldCheck
} from "@phosphor-icons/vue";
import { computed, watch } from 'vue';

const props = defineProps({
    purchaseOrders: Array,
    suppliers: Array,
    warehouses: Array,
    products: Array,
    // Shared Props
    auth: Object,
    errors: Object,
    company: Object,
    notifications: Array,
    flash: Object
});

const form = useForm({
    purchase_order_id: '',
    supplier_id: '',
    warehouse_id: '',
    tanggal: new Date().toISOString().split('T')[0],
    catatan: '',
    bukti_penerimaan: null,
    items: []
});

// Sync supplier if PO is selected
watch(() => form.purchase_order_id, (newVal) => {
    if (newVal) {
        const selectedPo = props.purchaseOrders.find(po => po.id === newVal);
        if (selectedPo) {
            form.supplier_id = selectedPo.supplier_id;
        }
    } else {
        form.supplier_id = '';
    }
});

const addItem = () => {
    form.items.push({
        product_id: '',
        quantity: 1,
        harga: 0,
        subtotal: 0
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const updateSubtotal = (index) => {
    const item = form.items[index];
    item.subtotal = item.quantity * item.harga;
};

const totalQuantity = computed(() => {
    return form.items.reduce((acc, item) => acc + (parseInt(item.quantity) || 0), 0);
});

const grandTotal = computed(() => {
    return form.items.reduce((acc, item) => acc + (parseFloat(item.subtotal) || 0), 0);
});

const submit = () => {
    // Client-side validation for mandatory fields
    if (!form.warehouse_id) {
        alert('⚠️ Mohon pilih GUDANG TUJUAN terlebih dahulu.');
        return;
    }
    if (form.items.length === 0) {
        alert('⚠️ Daftar Produk masih kosong. Klik "Tambah Baris"!');
        return;
    }
    const emptyProduct = form.items.some(item => !item.product_id);
    if (emptyProduct) {
        alert('⚠️ Ada produk yang belum dipilih di dalam tabel.');
        return;
    }

    form.post(route('barang-masuk.store'), {
        forceFormData: true,
        onSuccess: () => {
            // Optional: handled by redirect
        },
        onError: (errors) => {
            console.error('Validation Errors:', errors);
            // Scroll to first error
            const firstError = Object.keys(errors)[0];
            const el = document.querySelector(`[name="${firstError}"]`) || document.querySelector('.text-rose-500');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
};
</script>

<template>
    <Head title="Barang Masuk - Baru" />

    <AuthenticatedLayout title="Barang Masuk">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 font-sans">
            <div class="flex items-center gap-4">
                <Link :href="route('barang-masuk.index')" class="p-2.5 bg-white border border-slate-200 rounded-xl text-slate-400 hover:text-indigo-600 transition active:scale-90">
                    <PhArrowLeft :size="20" weight="bold" />
                </Link>
                <div>
                    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Penerimaan Barang</h2>
                    <p class="text-[11px] text-slate-500 font-bold mt-1 uppercase tracking-tight">Input Barang Masuk & Stok Gudang</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8 font-sans">
            <div class="lg:col-span-8 space-y-8">
                <!-- Header Info -->
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 md:p-10">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
                            <PhFileText :size="20" weight="fill" />
                        </div>
                        <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Informasi Dokumen PO</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nomor PO</label>
                            <select v-model="form.purchase_order_id" class="input-base font-black">
                                <option value="">Input Langsung (Tanpa PO)</option>
                                <option v-for="po in purchaseOrders" :key="po.id" :value="po.id">{{ po.no_po }}</option>
                            </select>
                            <InputError :message="form.errors.purchase_order_id" />
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                Supplier
                                <span v-if="!form.purchase_order_id" class="text-rose-400 ml-1">*</span>
                            </label>
                            <select
                                v-model="form.supplier_id"
                                class="input-base font-black"
                                :disabled="!!form.purchase_order_id"
                                :class="form.purchase_order_id ? 'opacity-60 cursor-not-allowed bg-slate-100' : ''"
                            >
                                <option value="">
                                    {{ form.purchase_order_id ? '(Dari PO)' : 'Pilih Supplier...' }}
                                </option>
                                <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.nama }}</option>
                            </select>
                            <InputError :message="form.errors.supplier_id" />
                            <p v-if="form.purchase_order_id" class="text-[9px] text-indigo-500 font-bold uppercase tracking-wider ml-1">
                                ✓ Supplier diambil dari PO
                            </p>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Tanggal Terima</label>
                            <input type="date" v-model="form.tanggal" class="input-base font-black">
                            <InputError :message="form.errors.tanggal" />
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Gudang Tujuan</label>
                            <select v-model="form.warehouse_id" class="input-base font-black">
                                <option value="">Pilih Gudang...</option>
                                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.nama }}</option>
                            </select>
                            <InputError :message="form.errors.warehouse_id" />
                        </div>
                    </div>
                </div>

                <!-- Items Section -->
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 md:p-10">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
                                <PhListPlus :size="20" weight="fill" />
                            </div>
                            <div>
                                <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Daftar Produk</h2>
                                <p v-if="form.errors.items" class="text-[10px] text-rose-500 font-bold mt-1 uppercase">{{ form.errors.items }}</p>
                            </div>
                        </div>
                        <button @click="addItem" class="hidden sm:flex items-center gap-2 text-indigo-600 font-black text-[10px] uppercase tracking-widest px-4 py-2 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                            <PhPlus weight="bold" /> Tambah Baris
                        </button>
                    </div>

                    <!-- Mobile Cards -->
                    <div class="block md:hidden space-y-4">
                        <div v-for="(item, index) in form.items" :key="index" class="p-6 rounded-2xl border-2 border-slate-50 bg-white shadow-sm">
                            <div class="flex justify-between items-start mb-4">
                                <div class="w-full">
                                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Produk #{{ index + 1 }}</label>
                                    <select v-model="item.product_id" class="input-base !py-2 text-xs font-black">
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
                                    <input type="number" v-model="item.quantity" @input="updateSubtotal(index)" class="input-base !py-2 text-xs font-black text-center">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Harga Beli</label>
                                    <input type="number" v-model="item.harga" @input="updateSubtotal(index)" class="input-base !py-2 text-xs font-black">
                                </div>
                            </div>
                            <div class="flex justify-between items-center pt-4 border-t border-slate-100">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Subtotal</span>
                                <span class="text-sm font-black text-slate-900 tracking-tight">Rp {{ item.subtotal.toLocaleString('id-ID') }}</span>
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
                                    <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-24">Qty</th>
                                    <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-40 text-right">Harga Beli</th>
                                    <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-40 text-right">Subtotal</th>
                                    <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-12 text-center"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-slate-50/50 group transition-all">
                                    <td class="py-4 pr-4">
                                        <select v-model="form.items[index].product_id" class="input-base !py-2 text-xs font-black" :class="{'border-rose-500': form.errors[`items.${index}.product_id`]}">
                                            <option value="">Pilih Produk...</option>
                                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.nama }}</option>
                                        </select>
                                        <div v-if="form.errors[`items.${index}.product_id`]" class="text-[9px] text-rose-500 font-bold mt-1 uppercase">{{ form.errors[`items.${index}.product_id`] }}</div>
                                    </td>
                                    <td class="py-4 pr-4">
                                        <input type="number" v-model="form.items[index].quantity" @input="updateSubtotal(index)" class="input-base !py-2 text-xs font-black text-center" :class="{'border-rose-500': form.errors[`items.${index}.quantity`]}">
                                        <div v-if="form.errors[`items.${index}.quantity`]" class="text-[9px] text-rose-500 font-bold mt-1 uppercase text-center">{{ form.errors[`items.${index}.quantity`] }}</div>
                                    </td>
                                    <td class="py-4 pr-4 text-right">
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400">Rp</span>
                                            <input type="number" v-model="item.harga" @input="updateSubtotal(index)" class="input-base !py-2 !pl-8 text-xs font-black text-right">
                                        </div>
                                    </td>
                                    <td class="py-4 pr-4 font-black text-slate-900 text-sm text-right">Rp {{ item.subtotal.toLocaleString('id-ID') }}</td>
                                    <td class="py-4 text-center">
                                        <button @click="removeItem(index)" class="text-slate-300 hover:text-rose-500 transition-all active:scale-90"><PhTrash :size="18" weight="bold" /></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <button @click="addItem" class="w-full mt-6 py-4 border-2 border-dashed border-indigo-50 text-indigo-400 font-black text-xs uppercase tracking-[0.2em] rounded-2xl hover:border-indigo-100 hover:text-indigo-600 transition flex items-center justify-center gap-2">
                            <PhPlus weight="bold" /> Tambah Baris Baru
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="lg:col-span-4 space-y-6">
                <div class="bg-indigo-900 text-white p-6 md:p-8 rounded-[2.5rem] shadow-2xl shadow-indigo-100">
                    <div class="flex items-center gap-2 mb-6">
                        <PhReceipt :size="18" class="text-indigo-400 min-w-max" />
                        <p class="text-[10px] font-black uppercase tracking-widest text-indigo-300 break-words">Ringkasan Terima</p>
                    </div>
                    
                    <div class="space-y-4 mb-8">
                        <div class="flex justify-between items-center bg-white/5 p-4 rounded-2xl border border-white/10 gap-2">
                            <span class="text-xs font-bold text-indigo-200 uppercase tracking-wider break-words">Total Item</span>
                            <span class="text-sm font-black whitespace-nowrap">{{ form.items.length }}</span>
                        </div>
                        <div class="flex justify-between items-center bg-white/5 p-4 rounded-2xl border border-white/10 gap-2">
                            <span class="text-xs font-bold text-indigo-200 uppercase tracking-wider break-words">Total Qty</span>
                            <span class="text-sm font-black whitespace-nowrap">{{ totalQuantity }} Pcs</span>
                        </div>
                    </div>

                    <div class="mb-10 w-full overflow-hidden">
                        <p class="text-[10px] font-black uppercase tracking-widest text-indigo-400 mb-2">Grand Total</p>
                        <p class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tighter break-all sm:break-words">Rp {{ grandTotal.toLocaleString('id-ID') }}</p>
                    </div>

                    <button @click="submit" :disabled="form.processing || form.items.length === 0" class="w-full bg-white text-indigo-900 hover:bg-indigo-50 font-black text-xs uppercase tracking-widest py-4 md:py-5 px-2 rounded-2xl shadow-xl transition-all active:scale-95 disabled:opacity-20 leading-snug">
                        <template v-if="form.processing">
                            <span v-if="form.progress">MENGUNGGAH {{ form.progress.percentage }}%...</span>
                            <span v-else>MEMPROSES...</span>
                        </template>
                        <template v-else>
                            Konfirmasi Terima
                        </template>
                    </button>
                </div>

                <div class="bg-white border border-slate-100 rounded-[2rem] p-8 space-y-6 shadow-sm">
                    <div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <PhUploadSimple :size="16" weight="bold" class="text-indigo-600" /> Bukti Terima
                        </h3>
                        <div @click="$refs.fileInput.click()" class="border-2 border-dashed border-slate-100 rounded-2xl p-6 text-center hover:border-indigo-400 transition-all cursor-pointer group bg-slate-50/50 overflow-hidden">
                            <input type="file" ref="fileInput" class="hidden" @change="e => { form.bukti_penerimaan = e.target.files[0]; }">
                            
                            <div v-if="!form.bukti_penerimaan">
                                <PhUploadSimple :size="24" class="text-slate-300 group-hover:text-indigo-500 mx-auto mb-2 transition-colors" />
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Upload File</p>
                            </div>
                            <div v-else class="flex items-center justify-center gap-2">
                                <PhShieldCheck :size="20" weight="fill" class="text-emerald-500" />
                                <p class="text-xs font-bold text-slate-700 truncate max-w-[200px]">{{ form.bukti_penerimaan.name }}</p>
                                <button @click.stop="form.bukti_penerimaan = null" class="text-rose-500 font-bold text-[10px] uppercase ml-2">Hapus</button>
                            </div>
                            <!-- Progress Bar -->
                            <div v-if="form.progress" class="mt-4">
                                <div class="w-full bg-slate-200 h-1 rounded-full overflow-hidden">
                                    <div class="bg-indigo-600 h-full transition-all duration-300" :style="{ width: form.progress.percentage + '%' }"></div>
                                </div>
                                <p class="text-[9px] font-black text-indigo-600 mt-2 uppercase tracking-tighter">Mengunggah: {{ form.progress.percentage }}%</p>
                            </div>
                        </div>
                        <InputError :message="form.errors.bukti_penerimaan" class="mt-2" />
                    </div>
                    <div>
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <PhChatCircleDots :size="16" weight="bold" class="text-indigo-600" /> Catatan
                        </h3>
                        <textarea v-model="form.catatan" rows="3" class="input-base !bg-slate-50 !border-slate-100 text-xs font-bold italic py-4 resize-none" placeholder="Keterangan tambahan..."></textarea>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
