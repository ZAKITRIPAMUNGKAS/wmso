<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ResponsiveTable from '@/Components/ResponsiveTable.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    PhArrowsClockwise, 
    PhArrowDownLeft, 
    PhArrowUpRight, 
    PhArrowsLeftRight, 
    PhSliders,
    PhMagnifyingGlass,
    PhFunnel,
    PhWarehouse,
    PhPackage
} from "@phosphor-icons/vue";

const props = defineProps({
    movements: Object,
    products: Array,
    warehouses: Array,
    racks: Array,
    filters: Object,
});

const search = ref(props.filters.search || '');
const type = ref(props.filters.type || '');
const warehouse_id = ref(props.filters.warehouse_id || '');
const product_id = ref(props.filters.product_id || '');

const applyFilters = () => {
    router.get(route('stock-movements.index'), {
        search: search.value,
        type: type.value,
        warehouse_id: warehouse_id.value,
        product_id: product_id.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    search.value = '';
    type.value = '';
    warehouse_id.value = '';
    product_id.value = '';
    applyFilters();
};

const getTypeBadgeClass = (type) => {
    switch (type) {
        case 'in':
            return 'bg-emerald-50 text-emerald-600 border-emerald-100';
        case 'out':
            return 'bg-rose-50 text-rose-600 border-rose-100';
        case 'transfer':
            return 'bg-indigo-50 text-indigo-600 border-indigo-100';
        case 'adjustment':
            return 'bg-amber-50 text-amber-600 border-amber-100';
        default:
            return 'bg-slate-50 text-slate-600 border-slate-100';
    }
};

const getTypeLabel = (type) => {
    switch (type) {
        case 'in': return 'Barang Masuk';
        case 'out': return 'Barang Keluar';
        case 'transfer': return 'Transfer Stok';
        case 'adjustment': return 'Penyesuaian';
        default: return type.toUpperCase();
    }
};
</script>

<template>
    <div>
        <Head title="Kartu Stok & Mutasi" />

        <AuthenticatedLayout title="Kartu Stok & Mutasi">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 font-sans">
                <div>
                    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Audit Trail & Log Mutasi</h2>
                    <p class="text-[11px] text-slate-500 font-bold mt-1 uppercase tracking-tight">Kartu Stok Pergerakan Barang Gudang</p>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <PhMagnifyingGlass class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" :size="18" />
                        <input 
                            v-model="search" 
                            @keyup.enter="applyFilters"
                            type="text" 
                            placeholder="Cari produk / SKU / ref..." 
                            class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-xs font-bold rounded-xl pl-10 pr-4 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400"
                        />
                    </div>

                    <!-- Type Dropdown -->
                    <select 
                        v-model="type" 
                        @change="applyFilters"
                        class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-xs font-bold rounded-xl px-4 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                    >
                        <option value="">-- Semua Jenis Mutasi --</option>
                        <option value="in">Barang Masuk (Inbound)</option>
                        <option value="out">Barang Keluar (Outbound)</option>
                        <option value="transfer">Transfer Stok</option>
                        <option value="adjustment">Opname / Penyesuaian</option>
                    </select>

                    <!-- Warehouse Dropdown -->
                    <select 
                        v-model="warehouse_id" 
                        @change="applyFilters"
                        class="w-full bg-slate-50 border border-slate-200 text-slate-800 text-xs font-bold rounded-xl px-4 py-2.5 outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                    >
                        <option value="">-- Semua Gudang --</option>
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.nama }}</option>
                    </select>

                    <!-- Reset & Filter Button Group -->
                    <div class="flex gap-2">
                        <button @click="applyFilters" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs uppercase tracking-wider py-2.5 px-4 rounded-xl shadow-sm transition-all flex items-center justify-center gap-2">
                            <PhFunnel weight="bold" :size="16" /> Filter
                        </button>
                        <button @click="resetFilters" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs py-2.5 px-3 rounded-xl transition-all">
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Section -->
            <ResponsiveTable :headers="['Waktu', 'Produk / SKU', 'Gudang & Rak', 'Jenis Mutasi', 'Jumlah Qty', 'Petugas', 'Keterangan']" :items="movements.data">
                <template #row="{ item }">
                    <td class="px-6 py-4 text-xs font-bold text-slate-500 whitespace-nowrap">
                        {{ new Date(item.created_at).toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }) }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-black text-slate-900 text-xs uppercase tracking-tight">
                            {{ item.product?.nama || '-' }}
                        </div>
                        <div class="text-[10px] font-mono font-bold text-slate-400 mt-0.5">
                            SKU: {{ item.product?.sku || '-' }}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-xs">
                        <div class="font-bold text-slate-700 uppercase flex items-center gap-1.5">
                            <PhWarehouse :size="14" class="text-slate-400" />
                            {{ item.warehouse?.nama || '-' }}
                        </div>
                        <div v-if="item.rack" class="text-[10px] text-slate-500 font-medium mt-0.5">
                            Rak: {{ item.rack.nama_rak }}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-[10px] font-black px-2.5 py-1 rounded-lg border uppercase tracking-wider inline-flex items-center gap-1.5" :class="getTypeBadgeClass(item.type)">
                            <component :is="item.type === 'in' ? PhArrowDownLeft : (item.type === 'out' ? PhArrowUpRight : PhArrowsLeftRight)" :size="12" weight="bold" />
                            {{ getTypeLabel(item.type) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 font-mono font-black text-sm whitespace-nowrap" :class="item.type === 'in' ? 'text-emerald-600' : (item.type === 'out' ? 'text-rose-600' : 'text-slate-800')">
                        {{ item.type === 'in' ? '+' : (item.type === 'out' ? '-' : '') }}{{ item.quantity }}
                    </td>
                    <td class="px-6 py-4 text-xs font-bold text-slate-600 uppercase">
                        {{ item.user?.name || 'Sistem' }}
                    </td>
                    <td class="px-6 py-4 text-xs text-slate-500 font-medium max-w-xs truncate">
                        {{ item.description || '-' }}
                    </td>
                </template>
            </ResponsiveTable>
        </AuthenticatedLayout>
    </div>
</template>
