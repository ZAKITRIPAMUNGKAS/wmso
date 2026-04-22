<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ResponsiveTable from '@/Components/ResponsiveTable.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    PhMagnifyingGlass, 
    PhEye
} from "@phosphor-icons/vue";
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    invoices: Object,
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');

const handleSearch = () => {
    router.get(route('invoices.index'), { search: searchQuery.value }, {
        preserveState: true,
        replace: true
    });
};

watch(searchQuery, (val) => {
    handleSearch();
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
};

const getStatusClass = (status) => {
    switch (status) {
        case 'lunas': return 'bg-emerald-50 text-emerald-700 border-emerald-100';
        default: return 'bg-rose-50 text-rose-700 border-rose-100';
    }
};

const getOfficialNumber = (invoice) => {
    if (!invoice) return "-";
    const date = new Date(invoice.tanggal);
    const romanMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
    const id = String(invoice.id).padStart(3, '0');
    return `INV/${id}/LJE/${romanMonths[date.getMonth()]}/${date.getFullYear()}`;
};

const formatDate = (dateString) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return "-";
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};
const getPaymentLabel = (item) => {
    if (!item.jenis_pembayaran || item.jenis_pembayaran === 'cash') return 'Cash';
    const hari = item.tempo_hari || 30;
    return `Tempo ${hari}H`;
};

const getPaymentClass = (item) => {
    if (!item.jenis_pembayaran || item.jenis_pembayaran === 'cash')
        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    return 'bg-amber-50 text-amber-700 border-amber-200';
};

const formatDueDate = (item) => {
    if (!item.due_date) return '-';
    const due  = new Date(item.due_date);
    const today = new Date();
    today.setHours(0,0,0,0);
    const diff = Math.ceil((due - today) / 86400000);
    if (diff < 0) return `Jatuh tempo ${Math.abs(diff)}h lalu`;
    if (diff === 0) return 'Jatuh tempo hari ini';
    return `${formatDate(item.due_date)} (${diff}h lagi)`;
};
</script>

<template>
    <AuthenticatedLayout title="Invoice">
        <Head title="Invoice" />
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 font-sans">
            <div>
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Manajemen Invoice</h2>
                <p class="text-[11px] text-slate-500 font-bold mt-1">Tagihan Penjualan & Status Pembayaran</p>
            </div>
            <div class="flex gap-2">
                <div class="relative flex-1 sm:w-64">
                    <PhMagnifyingGlass :size="16" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input type="text" v-model="searchQuery" placeholder="Cari invoice..." class="input-base !pl-10 !py-2 text-xs">
                </div>
            </div>
        </div>

        <ResponsiveTable :headers="['No. Invoice', 'Customer', 'Tanggal', 'Pembayaran', 'Total Tagihan', 'Status']" :items="invoices.data">
            <template #row="{ item }">
                <td class="px-6 py-5 text-xs text-left">
                    <div class="font-black text-slate-800 uppercase leading-none">{{ getOfficialNumber(item) }}</div>
                    <div class="text-[9px] text-slate-400 font-bold mt-1 uppercase tracking-widest">ID: {{ item.no_invoice }}</div>
                </td>
                <td class="px-6 py-5 text-xs font-black text-slate-700 text-left">
                    {{ item.delivery_order?.customer?.nama?.toUpperCase() || '-' }}
                </td>
                <td class="px-6 py-5 text-xs font-bold text-slate-600 text-left">
                    {{ formatDate(item.tanggal) }}
                </td>
                <td class="px-6 py-5 text-left">
                    <span :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[9px] font-black border uppercase tracking-tight', getPaymentClass(item)]">
                        {{ getPaymentLabel(item) }}
                    </span>
                    <p v-if="item.jenis_pembayaran === 'tempo'" class="text-[9px] text-slate-400 font-bold mt-1">
                        {{ formatDueDate(item) }}
                    </p>
                </td>
                <td class="px-6 py-5 font-black text-slate-800 text-right text-xs">
                    {{ formatCurrency((item.total || 0) * 1.11) }}
                </td>
                <td class="px-6 py-5 text-center">
                    <span :class="['inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-black border uppercase tracking-tighter', getStatusClass(item.status)]">
                        {{ item.status }}
                    </span>
                </td>
                <td class="px-6 py-5 text-right">
                    <div class="flex justify-end gap-2">
                        <!-- KLIK MATA LANGSUNG KE DETAIL (SYNCED WITH BARANG KELUAR) -->
                        <Link :href="route('invoices.show', item.id)" class="p-2 bg-indigo-50 text-indigo-600 rounded-lg hover:bg-indigo-100 transition active:scale-95 shadow-sm group relative">
                            <PhEye :size="18" weight="bold" />
                            <span class="absolute -top-8 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[8px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none uppercase font-black">Buka Detail</span>
                        </Link>
                    </div>
                </td>
            </template>

            <template #mobile-card="{ item }">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <div class="font-black text-slate-800 text-xs">{{ getOfficialNumber(item) }}</div>
                        <div class="text-[10px] font-bold text-indigo-600 uppercase">{{ item.delivery_order?.customer?.nama || '-' }}</div>
                    </div>
                    <span :class="['px-2 py-0.5 rounded-full text-[9px] font-black uppercase border', getStatusClass(item.status)]">
                        {{ item.status }}
                    </span>
                </div>
                <div class="flex justify-between items-center pt-4 border-t border-slate-50">
                    <p class="text-sm font-black text-slate-900">{{ formatCurrency((item.total || 0) * 1.11) }}</p>
                    <Link :href="route('invoices.show', item.id)" class="text-indigo-600 font-black text-[10px] uppercase tracking-widest bg-indigo-50 px-3 py-1.5 rounded-lg">Buka Detail</Link>
                </div>
            </template>

            <template #pagination>
                <div class="flex justify-between items-center w-full px-6 py-4 bg-slate-50/50">
                    <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest">Total: {{ invoices.total }} Invoices</p>
                    <div class="flex gap-1">
                        <template v-for="(link, k) in invoices.links" :key="k">
                            <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1.5 text-[10px] font-black rounded-lg transition" :class="[link.active ? 'bg-indigo-600 text-white' : 'bg-white text-slate-500 border border-slate-100']" />
                        </template>
                    </div>
                </div>
            </template>
        </ResponsiveTable>
    </AuthenticatedLayout>
</template>
