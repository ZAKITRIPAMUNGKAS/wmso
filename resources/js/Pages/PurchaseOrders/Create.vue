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
    PhShoppingCart,
    PhCalendar,
    PhUser,
    PhReceipt
} from "@phosphor-icons/vue";
import { computed } from 'vue';

const props = defineProps({
    suppliers: Array,
    products: Array,
    // Shared Props
    auth: Object,
    errors: Object,
    company: Object
});

const formatNumber = (num) => {
    if (!num && num !== 0) return '';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
};

const parseNumber = (str) => {
    return parseInt(str.toString().replace(/\./g, '')) || 0;
};

const form = useForm({
    supplier_id: '',
    tanggal: new Date().toISOString().split('T')[0],
    items: []
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

const onProductChange = (index) => {
    const item = form.items[index];
    const selectedProduct = props.products.find(p => p.id === item.product_id);
    if (selectedProduct) {
        item.harga = parseFloat(selectedProduct.harga) || 0;
    } else {
        item.harga = 0;
    }
    updateSubtotal(index);
};

const totalQuantity = computed(() => {
    return form.items.reduce((acc, item) => acc + (parseInt(item.quantity) || 0), 0);
});

const grandTotal = computed(() => {
    return form.items.reduce((acc, item) => acc + (parseFloat(item.subtotal) || 0), 0);
});

const submit = () => {
    if (!form.supplier_id) {
        alert('⚠️ Mohon pilih SUPPLIER terlebih dahulu.');
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

    form.post(route('purchase-orders.store'), {
        onSuccess: () => {
            // Handled by controller redirect
        },
        onError: (errors) => {
            console.error('Validation Errors:', errors);
            // Scroll to the first error message
            const firstError = Object.keys(errors)[0];
            const el = document.querySelector(`[name="${firstError}"]`) || document.querySelector('.text-rose-500');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
};
</script>

<template>
    <div>
        <Head title="Purchase Order Baru" />

        <AuthenticatedLayout title="Purchase Order">
            <!-- Back & Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 font-sans">
                <div class="flex items-center gap-4">
                    <Link :href="route('purchase-orders.index')" class="p-2.5 bg-white border border-slate-200 rounded-xl text-slate-400 hover:text-indigo-600 transition active:scale-90">
                        <PhArrowLeft :size="20" weight="bold" />
                    </Link>
                    <div>
                        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Purchase Order</h2>
                        <p class="text-[11px] text-slate-500 font-bold mt-1 uppercase tracking-tight">Buat Purchase Order Baru</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8 font-sans">
                <!-- Left Column: Form Details & Items -->
                <div class="lg:col-span-8 space-y-8">
                    <!-- PO Details Card -->
                    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 md:p-10">
                        <div class="flex items-center gap-3 mb-8">
                            <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
                                <PhFileText :size="20" weight="fill" />
                            </div>
                            <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Informasi Purchase Order</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Supplier -->
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                    Supplier <span class="text-rose-400">*</span>
                                </label>
                                <select v-model="form.supplier_id" class="input-base font-black">
                                    <option value="">Pilih Supplier...</option>
                                    <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.nama }}</option>
                                </select>
                                <InputError :message="form.errors.supplier_id" />
                            </div>

                            <!-- Tanggal PO -->
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">
                                    Tanggal PO <span class="text-rose-400">*</span>
                                </label>
                                <input type="date" v-model="form.tanggal" class="input-base font-black">
                                <InputError :message="form.errors.tanggal" />
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
                                        <select v-model="item.product_id" @change="onProductChange(index)" class="input-base !py-2 text-xs font-black">
                                            <option value="">Pilih Produk...</option>
                                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.nama }} ({{ p.satuan }})</option>
                                        </select>
                                    </div>
                                    <button @click="removeItem(index)" type="button" class="ml-4 p-2 text-rose-400 hover:text-rose-600">
                                        <PhTrash :size="18" weight="bold" />
                                    </button>
                                </div>
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Jumlah (Qty)</label>
                                        <input type="number" v-model="item.quantity" @input="updateSubtotal(index)" min="1" class="input-base !py-2 text-xs font-black text-center">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Harga Satuan</label>
                                        <input type="text" :value="formatNumber(item.harga)" @input="item.harga = parseNumber($event.target.value); updateSubtotal(index)" class="input-base !py-2 text-xs font-black">
                                    </div>
                                </div>
                                <div class="flex justify-between items-center pt-4 border-t border-slate-100">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Subtotal</span>
                                    <span class="text-sm font-black text-slate-900 tracking-tight">Rp {{ item.subtotal.toLocaleString('id-ID') }}</span>
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
                                        <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-24">Qty</th>
                                        <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-40 text-right">Harga Satuan</th>
                                        <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-48 text-right">Subtotal</th>
                                        <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest w-12 text-center"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-slate-50/50 group transition-all">
                                        <td class="py-4 pr-4">
                                            <select v-model="form.items[index].product_id" @change="onProductChange(index)" class="input-base !py-2 text-xs font-black" :class="{'border-rose-500': form.errors[`items.${index}.product_id`]}">
                                                <option value="">Pilih Produk...</option>
                                                <option v-for="p in products" :key="p.id" :value="p.id">{{ p.nama }} ({{ p.satuan }})</option>
                                            </select>
                                            <div v-if="form.errors[`items.${index}.product_id`]" class="text-[9px] text-rose-500 font-bold mt-1 uppercase">{{ form.errors[`items.${index}.product_id`] }}</div>
                                        </td>
                                        <td class="py-4 pr-4">
                                            <input type="number" v-model="form.items[index].quantity" @input="updateSubtotal(index)" min="1" class="input-base !py-2 text-xs font-black text-center" :class="{'border-rose-500': form.errors[`items.${index}.quantity`]}">
                                            <div v-if="form.errors[`items.${index}.quantity`]" class="text-[9px] text-rose-500 font-bold mt-1 uppercase text-center">{{ form.errors[`items.${index}.quantity`] }}</div>
                                        </td>
                                        <td class="py-4 pr-4 text-right">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400">Rp</span>
                                                <input type="text" :value="formatNumber(item.harga)" @input="item.harga = parseNumber($event.target.value); updateSubtotal(index)" class="input-base !py-2 !pl-8 text-xs font-black text-right">
                                            </div>
                                            <div v-if="form.errors[`items.${index}.harga`]" class="text-[9px] text-rose-500 font-bold mt-1 uppercase text-right">{{ form.errors[`items.${index}.harga`] }}</div>
                                        </td>
                                        <td class="py-4 pr-4 font-black text-slate-900 text-sm text-right">
                                            Rp {{ item.subtotal.toLocaleString('id-ID') }}
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
                            <PhReceipt :size="18" class="text-indigo-400 min-w-max" />
                            <p class="text-[10px] font-black uppercase tracking-widest text-indigo-300 break-words">Ringkasan PO</p>
                        </div>

                        <div class="space-y-4 mb-8">
                            <div class="flex justify-between items-center bg-white/5 p-4 rounded-2xl border border-white/10 gap-2">
                                <span class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Total Jenis Item</span>
                                <span class="text-sm font-black">{{ form.items.length }}</span>
                            </div>
                            <div class="flex justify-between items-center bg-white/5 p-4 rounded-2xl border border-white/10 gap-2">
                                <span class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Total Quantity</span>
                                <span class="text-sm font-black">{{ totalQuantity }} Pcs</span>
                            </div>
                        </div>

                        <div class="mb-10 w-full overflow-hidden">
                            <p class="text-[10px] font-black uppercase tracking-widest text-indigo-400 mb-2">Grand Total</p>
                            <p class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tighter break-all sm:break-words">
                                Rp {{ grandTotal.toLocaleString('id-ID') }}
                            </p>
                        </div>

                        <button 
                            @click="submit" 
                            :disabled="form.processing || form.items.length === 0" 
                            class="w-full bg-white text-indigo-900 hover:bg-indigo-50 font-black text-xs uppercase tracking-widest py-4 md:py-5 px-2 rounded-2xl shadow-xl transition-all active:scale-95 disabled:opacity-20 leading-snug"
                        >
                            <span v-if="form.processing">MEMPROSES...</span>
                            <span v-else>Simpan Purchase Order</span>
                        </button>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    </div>
</template>
