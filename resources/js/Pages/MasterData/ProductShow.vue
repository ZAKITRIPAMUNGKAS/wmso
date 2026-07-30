<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    PhArrowLeft, 
    PhCube, 
    PhWarehouse, 
    PhClockCounterClockwise, 
    PhLink, 
    PhArrowCircleDown, 
    PhArrowCircleUp 
} from "@phosphor-icons/vue";

const props = defineProps({
    product: Object,
    stocks: Array,
    totalStock: Number,
    movements: Object,
});

const formatNumber = (num) => {
    if (!num && num !== 0) return '';
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <div>
        <Head :title="'Kartu Stok - ' + product.nama" />

        <AuthenticatedLayout title="Kartu Stok">
            <!-- Header Actions -->
            <div class="mb-8 flex items-center justify-between no-print font-sans">
                <div class="flex items-center gap-4">
                    <Link :href="route('products.index')" class="p-2.5 bg-white border border-slate-200 rounded-xl text-slate-400 hover:text-indigo-600 transition active:scale-90">
                        <PhArrowLeft :size="20" weight="bold" />
                    </Link>
                    <div>
                        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Kartu Stok</h2>
                        <p class="text-[11px] text-slate-500 font-bold mt-1 uppercase tracking-tight">Detail & Riwayat Mutasi Barang</p>
                    </div>
                </div>
            </div>

            <!-- Product Info Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-8 font-sans">
                <!-- Product Metadata -->
                <div class="lg:col-span-7 bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 md:p-10 space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600">
                            <PhCube :size="24" weight="fill" />
                        </div>
                        <div>
                            <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-100 uppercase tracking-wider">
                                {{ product.kode_barang }}
                            </span>
                            <h3 class="text-lg font-black text-slate-800 tracking-tight uppercase mt-1.5">{{ product.nama }}</h3>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-6 pt-6 border-t border-slate-50 uppercase">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Merk</p>
                            <p class="text-sm font-bold text-slate-700 tracking-tight mt-0.5">{{ product.merk }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tipe/Varian</p>
                            <p class="text-sm font-bold text-slate-700 tracking-tight mt-0.5">{{ product.tipe }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Satuan</p>
                            <p class="text-sm font-bold text-slate-700 tracking-tight mt-0.5">{{ product.satuan }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Harga</p>
                            <p class="text-sm font-black text-slate-800 tracking-tight mt-0.5">Rp {{ formatNumber(product.harga) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Stok Minimum</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-sm font-black text-slate-800 tracking-tight">{{ product.stok_minimum }}</span>
                                <span class="text-[10px] text-slate-400 font-bold">Unit</span>
                            </div>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Stok Saat Ini</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-sm font-black tracking-tight mt-0.5" :class="totalStock < product.stok_minimum ? 'text-rose-600' : 'text-slate-800'">
                                    {{ totalStock }}
                                </span>
                                <span v-if="totalStock < product.stok_minimum" class="text-[9px] font-black text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md uppercase tracking-wider animate-pulse">Menipis</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Warehouse Stocks distribution -->
                <div class="lg:col-span-5 bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 md:p-10">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
                            <PhWarehouse :size="20" weight="fill" />
                        </div>
                        <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Penyebaran Stok Gudang</h2>
                    </div>

                    <div class="space-y-3">
                        <div v-if="stocks.length === 0" class="text-xs italic text-slate-400 text-center py-4 bg-slate-50 rounded-2xl">
                            Belum ada stok tercatat di gudang manapun
                        </div>
                        <div v-for="stock in stocks" :key="stock.id" class="flex justify-between items-center bg-slate-50/50 hover:bg-slate-50 p-4 rounded-2xl border border-slate-100 gap-2 transition-all">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">{{ stock.warehouse.nama }}</span>
                                <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold mt-0.5">{{ stock.warehouse.kode_gudang }}</span>
                            </div>
                            <span class="text-sm font-black text-slate-850 whitespace-nowrap">{{ stock.quantity }} {{ product.satuan }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stock Movements Table (Stock Card) -->
            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 md:p-10 font-sans">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600">
                        <PhClockCounterClockwise :size="20" weight="bold" />
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Riwayat Pergerakan Stok (Kartu Stok)</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase mt-1">Dicatat secara kronologis terbaru ke terlama</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest pl-4">Tanggal</th>
                                <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest">Gudang</th>
                                <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest text-center">Tipe</th>
                                <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest text-right">Qty</th>
                                <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest text-right">Saldo Awal</th>
                                <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest text-right">Saldo Akhir</th>
                                <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest">Referensi</th>
                                <th class="pb-4 font-black text-slate-400 text-[10px] uppercase tracking-widest pr-4">Oleh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-if="movements.data.length === 0">
                                <td colspan="8" class="text-center py-12 text-sm text-slate-400 italic">Belum ada mutasi stok untuk barang ini.</td>
                            </tr>
                            <tr v-for="m in movements.data" :key="m.id" class="hover:bg-slate-50/50 transition-all uppercase">
                                <td class="py-4 pl-4 text-xs font-bold text-slate-500 whitespace-nowrap">{{ formatDate(m.created_at) }}</td>
                                <td class="py-4 text-xs font-bold text-slate-700 tracking-tight">{{ m.warehouse.nama }}</td>
                                <td class="py-4 text-center">
                                    <span v-if="m.type === 'in'" class="inline-flex items-center gap-1 text-[10px] font-black text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded-lg border border-emerald-100 uppercase">
                                        <PhArrowCircleDown weight="fill" /> Masuk
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 text-[10px] font-black text-rose-600 bg-rose-50 px-2.5 py-0.5 rounded-lg border border-rose-100 uppercase">
                                        <PhArrowCircleUp weight="fill" /> Keluar
                                    </span>
                                </td>
                                <td class="py-4 text-sm font-black text-right" :class="m.type === 'in' ? 'text-emerald-600' : 'text-rose-600'">
                                    {{ m.type === 'in' ? '+' : '-' }}{{ m.quantity }}
                                </td>
                                <td class="py-4 text-xs font-bold text-slate-500 text-right">{{ m.saldo_sebelum }}</td>
                                <td class="py-4 text-sm font-black text-slate-700 text-right">{{ m.saldo_sesudah }}</td>
                                <td class="py-4 text-xs font-bold text-indigo-600">
                                    <div class="flex items-center gap-1.5">
                                        <Link v-if="m.reference_route" :href="m.reference_route" class="hover:underline flex items-center gap-1">
                                            <PhLink /> {{ m.reference_code }}
                                        </Link>
                                        <span v-else class="text-slate-400">{{ m.reference_code }}</span>
                                    </div>
                                </td>
                                <td class="py-4 pr-4 text-xs font-bold text-slate-500 whitespace-nowrap">{{ m.user ? m.user.name : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="movements.links.length > 3" class="mt-8 flex flex-col sm:flex-row justify-between items-center gap-4 border-t border-slate-50 pt-6">
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Showing {{ movements.from }}-{{ movements.to }} of {{ movements.total }}</p>
                    <div class="flex gap-1 overflow-x-auto pb-2 sm:pb-0 scrollbar-hide">
                        <template v-for="(link, k) in movements.links" :key="k">
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
            </div>
        </AuthenticatedLayout>
    </div>
</template>
