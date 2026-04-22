<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, nextTick } from 'vue';
import {
    PhPrinter, PhFileXls, PhTrendUp, PhArrowCircleUp,
    PhPackage, PhCalendar, PhChartBar, PhUsers,
    PhReceipt, PhArrowUp, PhArrowDown, PhMinus
} from "@phosphor-icons/vue";
import { formatDate, formatRupiah, formatNumber, formatRupiahShort } from '@/utils/report.js';
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

const props = defineProps({
    reportData:  { type: Array,  default: () => [] },
    summary:     { type: Object, default: () => ({}) },
    chartData:   { type: Object, default: () => ({}) },
    top5Pihak:   { type: Array,  default: () => [] },
    top5Produk:  { type: Array,  default: () => [] },
    filters:     { type: Object, default: () => ({}) },
});

const reportType = ref(props.filters?.type || 'masuk');
const startDate  = ref(props.filters?.start_date || new Date().toISOString().split('T')[0]);
const endDate    = ref(props.filters?.end_date   || new Date().toISOString().split('T')[0]);

watch([reportType, startDate, endDate], ([t, s, e]) => {
    router.get(route('reports.index'), { type: t, start_date: s, end_date: e }, { preserveState: true, replace: true });
});

const typeLabel = computed(() => ({
    masuk:  'Laporan Barang Masuk',
    keluar: 'Laporan Barang Keluar',
    stok:   'Laporan Stok Gudang',
}[reportType.value] || 'Laporan'));

const top5PihakMax  = computed(() => Math.max(...(props.top5Pihak?.map(x => x.total) || [1]), 1));
const top5ProdukMax = computed(() => Math.max(...(props.top5Produk?.map(x => x.total) || [1]), 1));

// ─── Chart ───
const chartCanvas = ref(null);
let chartInstance  = null;

const renderChart = async () => {
    await nextTick();
    if (!chartCanvas.value || !props.chartData?.labels?.length) return;
    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(chartCanvas.value, {
        type: 'bar',
        data: {
            labels: props.chartData.labels,
            datasets: [{
                label: 'Nilai (Rp)',
                data: props.chartData.values,
                backgroundColor: 'rgba(99, 102, 241, 0.15)',
                borderColor: '#6366f1',
                borderWidth: 2,
                borderRadius: 4,
                hoverBackgroundColor: 'rgba(99, 102, 241, 0.35)',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => formatRupiah(ctx.parsed.y)
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8' } },
                y: {
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { size: 10 }, color: '#94a3b8',
                        callback: v => formatRupiahShort(v)
                    }
                }
            }
        }
    });
};

onMounted(renderChart);
watch(() => props.chartData, renderChart, { deep: true });

// ─── Actions ───
const printReport  = () => {
    const p = new URLSearchParams({ type: reportType.value, start_date: startDate.value, end_date: endDate.value });
    window.open(route('reports.print') + '?' + p.toString(), '_blank');
};
const exportExcel  = () => {
    const p = new URLSearchParams({ type: reportType.value, start_date: startDate.value, end_date: endDate.value });
    window.location.href = route('reports.export') + '?' + p.toString();
};
</script>

<template>
    <AuthenticatedLayout title="Laporan">
        <Head title="Laporan" />

        <!-- ── PRINT HEADER (hanya muncul saat print) ── -->
        <div class="print-header hidden">
            <div class="flex justify-between items-start border-b-2 border-slate-800 pb-4 mb-4">
                <div>
                    <h1 class="text-lg font-black uppercase">{{ $page.props.company?.name || 'CV. Listrindo Jaya Elektrik' }}</h1>
                    <p class="text-xs text-slate-500">{{ $page.props.company?.address || 'Jl. Tebet Raya No. 11G, Jakarta Selatan' }}</p>
                </div>
                <div class="text-right text-xs">
                    <p class="font-black uppercase">{{ typeLabel }}</p>
                    <p class="text-slate-500 mt-1">Periode: {{ startDate }} s/d {{ endDate }}</p>
                    <p class="text-slate-400">Dicetak: {{ formatDate(new Date().toISOString()) }}</p>
                </div>
            </div>
        </div>

        <!-- ── PAGE HEADER ── -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 font-sans no-print">
            <div>
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wide">Reporting & Analytics</h2>
                <p class="text-[11px] text-slate-500 font-bold mt-1">Rekapitulasi Data Operasional</p>
            </div>
            <div class="flex gap-2">
                <button @click="printReport" class="btn-secondary !py-2.5 flex items-center gap-2 text-xs">
                    <PhPrinter :size="16" weight="bold" /> <span class="hidden sm:inline">Cetak</span>
                </button>
                <button @click="exportExcel" class="bg-[#107c41] hover:bg-[#0c6132] text-white px-4 py-2.5 rounded-xl font-black text-xs uppercase tracking-wide transition flex items-center gap-2 active:scale-95">
                    <PhFileXls :size="16" weight="bold" /> <span class="hidden sm:inline">Excel</span>
                </button>
            </div>
        </div>

        <!-- ── FILTER ROW ── -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6 font-sans no-print">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wide mb-1.5">Jenis Laporan</label>
                    <select v-model="reportType" class="input-base font-black text-indigo-600 text-sm">
                        <option value="masuk">Barang Masuk (Goods Receipt)</option>
                        <option value="keluar">Barang Keluar (Delivery Order)</option>
                        <option value="stok">Stok Gudang (Inventory)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wide mb-1.5">Dari</label>
                    <input type="date" v-model="startDate" class="input-base font-bold text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-wide mb-1.5">Sampai</label>
                    <input type="date" v-model="endDate" class="input-base font-bold text-sm">
                </div>
            </div>
        </div>

        <!-- ── 4 KPI CARDS ── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 font-sans">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Total Transaksi</p>
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                        <PhReceipt :size="16" class="text-indigo-600" weight="fill" />
                    </div>
                </div>
                <p class="text-2xl font-black text-slate-900 tracking-tight">{{ formatNumber(summary?.total_transaksi) }}</p>
                <p class="text-[10px] text-slate-400 font-bold mt-1">transaksi</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">{{ summary?.label_qty || 'Total Qty' }}</p>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <PhPackage :size="16" class="text-emerald-600" weight="fill" />
                    </div>
                </div>
                <p class="text-2xl font-black text-slate-900 tracking-tight">{{ formatNumber(summary?.total_qty) }}</p>
                <p class="text-[10px] text-slate-400 font-bold mt-1">unit/pcs/roll</p>
            </div>

            <div class="bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-100 p-5 text-white">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-[10px] font-black uppercase tracking-wide text-indigo-200">{{ summary?.label_nilai || 'Total Nilai' }}</p>
                    <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center">
                        <PhTrendUp :size="16" class="text-white" weight="fill" />
                    </div>
                </div>
                <p class="text-xl font-black tracking-tight leading-tight">{{ formatRupiahShort(summary?.total_nilai) }}</p>
                <p class="text-[10px] text-indigo-200 font-bold mt-1 truncate">{{ formatRupiah(summary?.total_nilai) }}</p>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Rata-rata/Transaksi</p>
                    <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
                        <PhChartBar :size="16" class="text-amber-600" weight="fill" />
                    </div>
                </div>
                <p class="text-xl font-black text-slate-900 tracking-tight">{{ formatRupiahShort(summary?.avg_per_transaksi) }}</p>
                <p class="text-[10px] text-slate-400 font-bold mt-1 truncate">{{ formatRupiah(summary?.avg_per_transaksi) }}</p>
            </div>
        </div>

        <!-- ── CHART + TOP 5 ── -->
        <div v-show="reportType !== 'stok'" class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6 font-sans no-print">
            <!-- Chart -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-1 h-5 bg-indigo-600 rounded-full"></div>
                    <p class="text-[10px] font-black text-slate-700 uppercase tracking-wide">Distribusi Harian</p>
                    <span class="ml-auto text-[10px] text-slate-400 font-bold">{{ startDate }} — {{ endDate }}</span>
                </div>
                <div class="h-44">
                    <canvas ref="chartCanvas"></canvas>
                </div>
            </div>

            <!-- Top 5 Panel -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-1 h-5 bg-amber-500 rounded-full"></div>
                        <p class="text-[10px] font-black text-slate-700 uppercase tracking-wide">
                            {{ reportType === 'masuk' ? 'Top Supplier' : 'Top Customer' }}
                        </p>
                    </div>
                    <div v-for="(item, i) in top5Pihak" :key="i" class="mb-2">
                        <div class="flex justify-between text-[10px] font-bold mb-1">
                            <span class="text-slate-600 truncate max-w-[140px]">{{ item.nama }}</span>
                            <span class="text-slate-900 ml-2 whitespace-nowrap">{{ formatRupiahShort(item.total) }}</span>
                        </div>
                        <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-amber-400 rounded-full transition-all" :style="{ width: (item.total / top5PihakMax * 100) + '%' }"></div>
                        </div>
                    </div>
                </div>
                <div class="border-t border-slate-50 pt-4">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-1 h-5 bg-indigo-500 rounded-full"></div>
                        <p class="text-[10px] font-black text-slate-700 uppercase tracking-wide">Top Produk (Qty)</p>
                    </div>
                    <div v-for="(item, i) in top5Produk" :key="i" class="mb-2">
                        <div class="flex justify-between text-[10px] font-bold mb-1">
                            <span class="text-slate-600 truncate max-w-[140px]">{{ item.nama }}</span>
                            <span class="text-slate-900 ml-2">{{ formatNumber(item.total) }}</span>
                        </div>
                        <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-indigo-400 rounded-full transition-all" :style="{ width: (item.total / top5ProdukMax * 100) + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── TABEL DETAIL ── -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden font-sans">
            <div class="px-6 py-4 border-b border-slate-50 flex items-center justify-between no-print">
                <div class="flex items-center gap-2">
                    <div class="w-1 h-5 bg-indigo-600 rounded-full"></div>
                    <h3 class="font-black text-slate-800 text-sm">Rincian Transaksi</h3>
                </div>
                <span class="text-[10px] text-slate-400 font-bold">{{ reportData?.length || 0 }} baris</span>
            </div>

            <!-- DESKTOP TABLE -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-5 py-3 text-[9px] font-black text-slate-400 uppercase tracking-wide">Tanggal</th>
                            <th class="px-5 py-3 text-[9px] font-black text-slate-400 uppercase tracking-wide">Referensi</th>
                            <th class="px-5 py-3 text-[9px] font-black text-slate-400 uppercase tracking-wide">
                                {{ reportType === 'masuk' ? 'Supplier' : reportType === 'keluar' ? 'Customer' : 'Gudang' }}
                            </th>
                            <th class="px-5 py-3 text-[9px] font-black text-slate-400 uppercase tracking-wide">Produk</th>
                            <th class="px-5 py-3 text-[9px] font-black text-slate-400 uppercase tracking-wide text-right">Qty</th>
                            <th class="px-5 py-3 text-[9px] font-black text-slate-400 uppercase tracking-wide">Sat.</th>
                            <th class="px-5 py-3 text-[9px] font-black text-slate-400 uppercase tracking-wide text-right">Harga Satuan</th>
                            <th class="px-5 py-3 text-[9px] font-black text-slate-400 uppercase tracking-wide text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-if="!reportData?.length">
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-slate-400 font-bold">
                                Tidak ada data untuk periode dan filter yang dipilih.
                            </td>
                        </tr>
                        <tr
                            v-for="(item, idx) in reportData"
                            :key="idx"
                            class="transition-colors"
                            :class="idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/60'"
                        >
                            <td class="px-5 py-3.5 text-[11px] font-bold text-slate-500 whitespace-nowrap">
                                {{ formatDate(item.tgl) }}
                            </td>
                            <td class="px-5 py-3.5 font-black text-slate-800 text-xs whitespace-nowrap">
                                {{ item.ref }}
                            </td>
                            <td class="px-5 py-3.5 text-xs font-bold text-slate-700 max-w-[160px] truncate">
                                {{ item.pihak }}
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-600 max-w-[180px]" style="word-break: break-word; white-space: normal;">
                                {{ item.product }}
                            </td>
                            <td class="px-5 py-3.5 text-xs font-black text-slate-900 text-right tabular-nums">
                                {{ formatNumber(item.qty) }}
                            </td>
                            <td class="px-5 py-3.5 text-[10px] font-bold text-slate-400 uppercase">
                                {{ item.satuan || 'pcs' }}
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-600 text-right tabular-nums">
                                {{ formatRupiah(item.harga_satuan) }}
                            </td>
                            <td class="px-5 py-3.5 text-xs font-black text-slate-900 text-right tabular-nums whitespace-nowrap">
                                {{ formatRupiah(item.total) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- MOBILE CARDS -->
            <div class="block md:hidden divide-y divide-slate-50">
                <div v-if="!reportData?.length" class="p-8 text-center text-sm text-slate-400 font-bold">
                    Tidak ada data.
                </div>
                <div v-for="(item, idx) in reportData" :key="idx" class="p-5" :class="idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/60'">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <p class="font-black text-slate-800 text-sm">{{ item.ref }}</p>
                            <p class="text-[10px] text-slate-400 font-bold mt-0.5">{{ formatDate(item.tgl) }}</p>
                        </div>
                        <p class="font-black text-indigo-600 text-sm">{{ formatRupiahShort(item.total) }}</p>
                    </div>
                    <p class="text-xs font-bold text-slate-700 mb-1">{{ item.pihak }}</p>
                    <p class="text-xs text-slate-500 italic mb-2">{{ item.product }}</p>
                    <div class="flex gap-4 text-[10px] font-bold text-slate-400">
                        <span>Qty: <span class="text-slate-700">{{ formatNumber(item.qty) }} {{ item.satuan }}</span></span>
                        <span>@ <span class="text-slate-700">{{ formatRupiah(item.harga_satuan) }}</span></span>
                    </div>
                </div>
            </div>

            <!-- FOOTER GRAND TOTAL -->
            <div class="bg-slate-900 text-white p-6 font-sans">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 flex-wrap">
                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400 italic">
                        Grand Total — {{ typeLabel }} — Periode {{ startDate }} s/d {{ endDate }}
                    </p>
                    <div class="flex items-center gap-8 md:gap-12 flex-wrap justify-end">
                        <div class="text-right">
                            <p class="text-[9px] font-black uppercase tracking-wide text-slate-400 mb-1">Total Transaksi</p>
                            <p class="text-2xl font-black tracking-tight tabular-nums">{{ formatNumber(summary?.total_transaksi) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] font-black uppercase tracking-wide text-indigo-400 mb-1">{{ summary?.label_qty }}</p>
                            <p class="text-2xl font-black tracking-tight tabular-nums">{{ formatNumber(summary?.total_qty) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[9px] font-black uppercase tracking-wide text-indigo-400 mb-1">{{ summary?.label_nilai }}</p>
                            <p class="text-2xl font-black tracking-tight tabular-nums">{{ formatRupiah(summary?.total_nilai) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
