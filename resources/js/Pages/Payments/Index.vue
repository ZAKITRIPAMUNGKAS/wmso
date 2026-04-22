<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ResponsiveTable from '@/Components/ResponsiveTable.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { 
    PhMagnifyingGlass, 
    PhWallet, 
    PhPlus, 
    PhX, 
    PhClockCounterClockwise,
    PhFileText,
    PhUser,
    PhBank,
    PhCreditCard,
    PhImage,
    PhPaperclip,
    PhCheckCircle
} from "@phosphor-icons/vue";
import { ref, computed, watch } from 'vue';

const props = defineProps({
    invoices: Object,
    filters: Object,
});

const showPaymentModal = ref(false);
const selectedInvoice = ref(null);
const currentFilter = ref('semua');
const searchQuery = ref(props.filters?.search || '');

const handleSearch = () => {
    router.get(route('payments.index'), { search: searchQuery.value }, {
        preserveState: true,
        replace: true
    });
};

watch(searchQuery, (val) => {
    handleSearch();
});

const filterInvoices = (status) => {
    currentFilter.value = status;
};

const filteredInvoices = computed(() => {
    if (currentFilter.value === 'semua') return props.invoices.data;
    
    let statusMap = {
        'lunas': 'lunas',
        'belum': 'belum_lunas',
        'partial': 'partial'
    };
    
    return props.invoices.data.filter(inv => inv.status === statusMap[currentFilter.value]);
});

const buktiBayarPreview = ref(null);
const buktiBayarName   = ref('');

const form = useForm({
    invoice_id:  '',
    nominal:     '',
    tanggal:     new Date().toISOString().split('T')[0],
    metode:      'Transfer Bank (BCA)',
    keterangan:  '',
    bukti_bayar: null,
});

const handleBuktiBayar = (e) => {
    const file = e.target?.files?.[0] || e.dataTransfer?.files?.[0];
    if (!file) return;
    form.bukti_bayar  = file;
    buktiBayarName.value = file.name;
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (ev) => { buktiBayarPreview.value = ev.target.result; };
        reader.readAsDataURL(file);
    } else {
        buktiBayarPreview.value = null; // PDF, no preview
    }
};

const removeBukti = () => {
    form.bukti_bayar  = null;
    buktiBayarPreview.value = null;
    buktiBayarName.value = '';
};

const openPaymentModal = (invoice) => {
    selectedInvoice.value = invoice;
    form.invoice_id = invoice.id;
    form.nominal = invoice.total - invoice.paid_amount;
    showPaymentModal.value = true;
};

const submit = () => {
    form.post(route('payments.store'), {
        forceFormData: true,
        onSuccess: () => {
            showPaymentModal.value = false;
            form.reset();
            buktiBayarPreview.value = null;
            buktiBayarName.value = '';
        }
    });
};

const getProgressColor = (percent) => {
    if (percent >= 100) return 'bg-emerald-500';
    if (percent > 0) return 'bg-indigo-500';
    return 'bg-rose-500';
};
</script>

<template>
    <AuthenticatedLayout title="Payment">
        <Head title="Payment" />
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 font-sans">
            <div>
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Payment Tracking</h2>
                <p class="text-[11px] text-slate-500 font-bold mt-1">Status Penagihan & Arus Kas</p>
            </div>
            <button @click="showPaymentModal = true" class="btn-primary flex items-center justify-center gap-2">
                <PhPlus weight="bold" /> <span class="tracking-tight">Input Pembayaran</span>
            </button>
        </div>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-8 font-sans">
            <div class="overflow-x-auto pb-4 sm:pb-0 scrollbar-hide">
                <div class="flex space-x-1.5 p-1 bg-white border border-slate-100 rounded-2xl shadow-sm w-max">
                    <button @click="filterInvoices('semua')" :class="['px-5 py-2.5 text-xs font-black uppercase tracking-widest rounded-xl transition-all active:scale-95', currentFilter === 'semua' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'text-slate-400 hover:bg-slate-50']">Semua</button>
                    <button @click="filterInvoices('belum')" :class="['px-5 py-2.5 text-xs font-black uppercase tracking-widest rounded-xl transition-all active:scale-95', currentFilter === 'belum' ? 'bg-rose-500 text-white shadow-lg shadow-rose-100' : 'text-slate-400 hover:bg-slate-50']">Belum Lunas</button>
                    <button @click="filterInvoices('partial')" :class="['px-5 py-2.5 text-xs font-black uppercase tracking-widest rounded-xl transition-all active:scale-95', currentFilter === 'partial' ? 'bg-amber-500 text-white shadow-lg shadow-amber-100' : 'text-slate-400 hover:bg-slate-50']">Partial</button>
                    <button @click="filterInvoices('lunas')" :class="['px-5 py-2.5 text-xs font-black uppercase tracking-widest rounded-xl transition-all active:scale-95', currentFilter === 'lunas' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-100' : 'text-slate-400 hover:bg-slate-50']">Lunas</button>
                </div>
            </div>
            <div class="relative w-full lg:w-72">
                <PhMagnifyingGlass :size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                <input type="text" v-model="searchQuery" placeholder="Cari invoice..." class="input-base !pl-10 !py-2.5 text-xs">
            </div>
        </div>

        <ResponsiveTable :headers="['No. Invoice & Cust', 'Total Invoice', 'Telah Dibayar', 'Sisa Tagihan', 'Progress']" :items="filteredInvoices">
            <template #row="{ item }">
                <td class="px-8 py-5">
                    <div class="font-black text-slate-800 tracking-tight">{{ item.no_invoice }}</div>
                    <div class="text-[10px] text-slate-400 font-bold mt-0.5 uppercase tracking-widest">{{ item.delivery_order?.customer?.nama }}</div>
                </td>
                <td class="px-8 py-5 font-black text-slate-800 text-right tracking-tight">Rp {{ Number(item.total).toLocaleString('id-ID') }}</td>
                <td class="px-8 py-5 font-black text-emerald-600 text-right tracking-tight">Rp {{ Number(item.paid_amount).toLocaleString('id-ID') }}</td>
                <td class="px-8 py-5 font-black text-rose-500 text-right tracking-tight">Rp {{ (item.total - item.paid_amount).toLocaleString('id-ID') }}</td>
                <td class="px-8 py-5 min-w-[140px]">
                    <div class="flex items-center justify-between text-[10px] font-black uppercase tracking-wider mb-2">
                        <span class="text-indigo-600">{{ Math.round((item.paid_amount / item.total) * 100) }}%</span>
                        <span class="text-slate-400 italic">{{ item.status }}</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div :class="['h-full rounded-full transition-all duration-700', getProgressColor((item.paid_amount / item.total) * 100)]" :style="{ width: (item.paid_amount / item.total) * 100 + '%' }"></div>
                    </div>
                </td>
                <td class="px-8 py-5 text-center">
                    <button v-if="item.status !== 'lunas'" @click="openPaymentModal(item)" class="p-2.5 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all active:scale-90 font-black text-[10px] uppercase tracking-widest">Bayar</button>
                    <button v-else class="p-2.5 text-slate-400 hover:bg-slate-50 rounded-xl transition-all font-black text-[10px] uppercase tracking-widest"><PhClockCounterClockwise :size="16" /></button>
                </td>
            </template>

            <template #mobile-card="{ item }">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                            <PhWallet :size="20" weight="fill" />
                        </div>
                        <div>
                            <div class="font-black text-slate-800 tracking-tight">{{ item.no_invoice }}</div>
                            <div class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest">{{ item.delivery_order?.customer?.nama }}</div>
                        </div>
                    </div>
                    <span :class="['px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-tighter', item.status === 'lunas' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-rose-50 text-rose-600 border border-rose-100']">
                        {{ item.status }}
                    </span>
                </div>
                
                <div class="w-full bg-slate-100 rounded-full h-1 mb-4 overflow-hidden">
                    <div :class="['h-full rounded-full transition-all duration-700', getProgressColor((item.paid_amount / item.total) * 100)]" :style="{ width: (item.paid_amount / item.total) * 100 + '%' }"></div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-50">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Telah Dibayar</p>
                        <p class="text-sm font-black text-emerald-600 tracking-tight">Rp {{ Number(item.paid_amount).toLocaleString('id-ID') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Sisa Tagihan</p>
                        <p class="text-sm font-black text-rose-500 tracking-tight">Rp {{ (item.total - item.paid_amount).toLocaleString('id-ID') }}</p>
                    </div>
                </div>
                <div v-if="item.status !== 'lunas'" class="mt-4 pt-4 border-t border-slate-50 flex justify-end">
                    <button @click="openPaymentModal(item)" class="text-indigo-600 font-black text-[10px] uppercase tracking-[0.2em] px-5 py-2.5 bg-indigo-50 rounded-xl active:scale-95 transition">Input Pembayaran</button>
                </div>
            </template>

            <template #pagination>
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 w-full">
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Showing {{ invoices.from || 0 }}-{{ invoices.to || 0 }} of {{ invoices.total }}</p>
                    <div class="flex gap-1 overflow-x-auto pb-2 sm:pb-0 scrollbar-hide">
                        <template v-for="(link, k) in invoices.links" :key="k">
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

        <Modal :show="showPaymentModal" title="Konfirmasi Pembayaran" @close="showPaymentModal = false">
            <form @submit.prevent="submit" class="flex flex-col h-full md:h-auto font-sans">
                <div class="p-6 md:p-8 space-y-6">
                    <div class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100/50 mb-2">
                        <div class="flex items-center gap-3 mb-2">
                            <PhFileText :size="18" class="text-indigo-600" />
                            <p class="text-xs font-black text-indigo-900 uppercase tracking-widest">Pilih Invoice</p>
                        </div>
                        <select v-model="form.invoice_id" class="input-base !bg-white !border-indigo-100 font-bold text-slate-700">
                            <option value="">Pilih Invoice...</option>
                            <option v-for="inv in invoices.data" :key="inv.id" :value="inv.id">
                                {{ inv.no_invoice }} - {{ inv.delivery_order?.customer?.nama }}
                            </option>
                        </select>
                        <InputError :message="form.errors.invoice_id" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">
                        <div class="col-span-1">
                            <label class="block text-xs font-black text-slate-500 mb-2 uppercase tracking-widest ml-1">Nominal</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-black text-sm">Rp</span>
                                <input type="number" v-model="form.nominal" class="input-base !pl-10 font-black" placeholder="0">
                            </div>
                            <InputError :message="form.errors.nominal" />
                        </div>
                        <div class="col-span-1">
                            <label class="block text-xs font-black text-slate-500 mb-2 uppercase tracking-widest ml-1">Tanggal</label>
                            <input type="date" v-model="form.tanggal" class="input-base font-black">
                            <InputError :message="form.errors.tanggal" />
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-black text-slate-500 mb-2 uppercase tracking-widest ml-1">Metode</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <button type="button" @click="form.metode = form.metode.includes('Transfer') ? form.metode : 'Transfer Bank (BCA)'" :class="['p-4 rounded-2xl border-2 transition-all flex flex-col items-center gap-2 group active:scale-95', form.metode.includes('Transfer') ? 'border-indigo-600 bg-indigo-50/50' : 'border-slate-100 hover:border-slate-200']">
                                    <PhBank :size="24" :weight="form.metode.includes('Transfer') ? 'fill' : 'bold'" :class="form.metode.includes('Transfer') ? 'text-indigo-600' : 'text-slate-300'" />
                                    <span :class="['text-[10px] font-black uppercase tracking-tighter text-center', form.metode.includes('Transfer') ? 'text-indigo-900' : 'text-slate-400']">Transfer</span>
                                </button>
                                <button type="button" @click="form.metode = 'Cash / Tunai'" :class="['p-4 rounded-2xl border-2 transition-all flex flex-col items-center gap-2 group active:scale-95', form.metode === 'Cash / Tunai' ? 'border-indigo-600 bg-indigo-50/50' : 'border-slate-100 hover:border-slate-200']">
                                    <PhWallet :size="24" :weight="form.metode === 'Cash / Tunai' ? 'fill' : 'bold'" :class="form.metode === 'Cash / Tunai' ? 'text-indigo-600' : 'text-slate-300'" />
                                    <span :class="['text-[10px] font-black uppercase tracking-tighter text-center', form.metode === 'Cash / Tunai' ? 'text-indigo-900' : 'text-slate-400']">Tunai</span>
                                </button>
                                <button type="button" @click="form.metode = 'Giro / Cek'" :class="['p-4 rounded-2xl border-2 transition-all flex flex-col items-center gap-2 group active:scale-95', form.metode === 'Giro / Cek' ? 'border-indigo-600 bg-indigo-50/50' : 'border-slate-100 hover:border-slate-200']">
                                    <PhCreditCard :size="24" :weight="form.metode === 'Giro / Cek' ? 'fill' : 'bold'" :class="form.metode === 'Giro / Cek' ? 'text-indigo-600' : 'text-slate-300'" />
                                    <span :class="['text-[10px] font-black uppercase tracking-tighter text-center', form.metode === 'Giro / Cek' ? 'text-indigo-900' : 'text-slate-400']">Cek / Giro</span>
                                </button>
                            </div>
                            <!-- Pilihan Bank (Jika Transfer) -->
                            <div v-if="form.metode.includes('Transfer')" class="mt-3 flex gap-2">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" v-model="form.metode" value="Transfer Bank (BCA)" class="hidden peer">
                                    <div class="px-4 py-2 border-2 border-slate-100 rounded-xl peer-checked:border-indigo-600 peer-checked:bg-indigo-50/50 peer-checked:ring-1 peer-checked:ring-indigo-600 text-center transition-all">
                                        <span class="text-[10px] font-black text-slate-400 peer-checked:text-indigo-700 uppercase">Bank BCA</span>
                                    </div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" v-model="form.metode" value="Transfer Bank (Mandiri)" class="hidden peer">
                                    <div class="px-4 py-2 border-2 border-slate-100 rounded-xl peer-checked:border-indigo-600 peer-checked:bg-indigo-50/50 peer-checked:ring-1 peer-checked:ring-indigo-600 text-center transition-all">
                                        <span class="text-[10px] font-black text-slate-400 peer-checked:text-indigo-700 uppercase">Bank Mandiri</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-black text-slate-500 mb-2 uppercase tracking-widest ml-1">Keterangan</label>
                            <textarea v-model="form.keterangan" rows="2" class="input-base font-bold py-4" placeholder="Tambahkan catatan jika perlu..."></textarea>
                        </div>

                        <!-- Upload Bukti Pembayaran -->
                        <div class="col-span-2">
                            <label class="block text-xs font-black text-slate-500 mb-2 uppercase tracking-widest ml-1">
                                Bukti Pembayaran <span class="text-slate-300 font-bold normal-case tracking-normal">— JPG, PNG, atau PDF, maks 5MB</span>
                            </label>

                            <!-- Sudah ada file -->
                            <div v-if="form.bukti_bayar" class="border-2 border-indigo-200 bg-indigo-50/40 rounded-2xl p-4 flex items-center gap-4">
                                <!-- Preview gambar -->
                                <div v-if="buktiBayarPreview" class="w-16 h-16 rounded-xl overflow-hidden border border-indigo-100 shrink-0">
                                    <img :src="buktiBayarPreview" class="w-full h-full object-cover" alt="Preview">
                                </div>
                                <!-- Icon PDF -->
                                <div v-else class="w-16 h-16 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center shrink-0">
                                    <PhFileText :size="28" class="text-rose-400" weight="fill" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-black text-slate-800 truncate">{{ buktiBayarName }}</p>
                                    <p class="text-[10px] text-indigo-500 font-bold mt-0.5">Siap diupload</p>
                                </div>
                                <button type="button" @click="removeBukti"
                                    class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-lg transition shrink-0">
                                    <PhX :size="16" weight="bold" />
                                </button>
                            </div>

                            <!-- Belum ada file — area drag & drop -->
                            <label v-else
                                @dragover.prevent
                                @drop.prevent="handleBuktiBayar"
                                class="flex flex-col items-center justify-center gap-3 border-2 border-dashed border-slate-200 rounded-2xl p-8 cursor-pointer hover:border-indigo-300 hover:bg-indigo-50/30 transition group">
                                <div class="w-12 h-12 rounded-xl bg-slate-100 group-hover:bg-indigo-100 flex items-center justify-center transition">
                                    <PhPaperclip :size="22" class="text-slate-400 group-hover:text-indigo-500 transition" weight="bold" />
                                </div>
                                <div class="text-center">
                                    <p class="text-xs font-black text-slate-600">Klik atau seret file ke sini</p>
                                    <p class="text-[10px] text-slate-400 font-bold mt-0.5">Screenshot transfer, foto struk, atau file PDF</p>
                                </div>
                                <input type="file" accept="image/*,.pdf" class="hidden" @change="handleBuktiBayar">
                            </label>
                            <InputError :message="form.errors.bukti_bayar" class="mt-1" />
                        </div>

                    </div><!-- /grid -->
                </div><!-- /p-6 -->

                <div class="sticky bottom-0 bg-slate-50/80 backdrop-blur-md p-6 md:p-8 border-t border-slate-100 flex flex-col sm:flex-row justify-end gap-3 mt-auto shrink-0">
                    <button type="button" @click="showPaymentModal = false" class="btn-secondary w-full sm:w-auto">Batal</button>
                    <button type="submit" :disabled="form.processing" class="btn-primary w-full sm:w-auto">
                        Konfirmasi Bayar
                    </button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
