<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { 
    PhStack, 
    PhArrowDownLeft, 
    PhArrowUpRight, 
    PhWarningCircle, 
    PhClockCounterClockwise,
    PhChartPie,
    PhWarning,
    PhHourglass
} from "@phosphor-icons/vue";
import { Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler
} from 'chart.js';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Filler
);



const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 2000,
        easing: 'easeOutQuart',
    },
    animations: {
        y: {
            type: 'number',
            easing: 'easeOutQuart',
            duration: 1000,
            from: 1000,
            delay: (context) => {
                if (context.type !== 'data' || context.active) {
                    return 0;
                }
                return context.index * 150; // Efek muncul satu per satu (node-by-node)
            }
        },
        opacity: {
            from: 0,
            to: 1,
            duration: 1000,
            delay: (context) => {
                if (context.type !== 'data' || context.active) {
                    return 0;
                }
                return context.index * 150;
            }
        }
    },
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            backgroundColor: '#1E3A5F',
            titleFont: { size: 13, weight: 'bold', family: 'Inter' },
            bodyFont: { size: 12, family: 'Inter' },
            padding: 12,
            cornerRadius: 12,
            displayColors: false
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                display: true,
                color: 'rgba(241, 245, 249, 1)',
                drawBorder: false
            },
            ticks: {
                font: { size: 10, weight: 'bold' },
                color: '#94a3b8'
            }
        },
        x: {
            grid: {
                display: false
            },
            ticks: {
                font: { size: 10, weight: 'bold' },
                color: '#94a3b8'
            }
        }
    }
};

defineProps({
    stats: Object,
    chartData: Object,
    recentTransactions: Array,
    abcAnalysis: Object,
    lowStockProducts: Array,
    nearExpiry: Array
});
</script>

<template>
    <div>
        <Head title="Dashboard" />

        <AuthenticatedLayout title="Dashboard">
            <!-- Stat Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
                <StatCard 
                    title="Total Stok" 
                    :value="stats.total_stock || 0" 
                    :icon="PhStack" 
                    trend="+0%" 
                    trend-label="dari bulan lalu" 
                />
                <StatCard 
                    title="Masuk Hari Ini" 
                    :value="stats.today_receipts || 0" 
                    :icon="PhArrowDownLeft" 
                    icon-color="text-emerald-600"
                    icon-bg="bg-emerald-50"
                    icon-bg-hover="group-hover:bg-emerald-600"
                    :footer-label="`${stats.total_suppliers} Supplier terverifikasi`"
                />
                <StatCard 
                    title="Keluar Hari Ini" 
                    :value="stats.today_orders || 0" 
                    :icon="PhArrowUpRight" 
                    icon-color="text-amber-600"
                    icon-bg="bg-amber-50"
                    icon-bg-hover="group-hover:bg-amber-600"
                    :footer-label="`Menuju ${stats.total_customers} Customer`"
                />
                <StatCard 
                    title="Invoice Pending" 
                    :value="Number(stats.pending_invoices_amount).toLocaleString('id-ID')" 
                    unit="Rp"
                    :icon="PhWarningCircle" 
                    icon-color="text-rose-600"
                    icon-bg="bg-rose-50"
                    icon-bg-hover="group-hover:bg-rose-600"
                    footer-label="Butuh perhatian segera"
                    :footer-pulse="true"
                />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8 mb-8">
                <!-- Chart Section -->
                <div class="lg:col-span-2 bg-white border border-slate-200 rounded-[2rem] shadow-sm p-6 md:p-8">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
                        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-[0.2em] hover-glow cursor-default">Aktivitas Mingguan</h2>
                        <div class="flex gap-4">
                            <div class="flex items-center gap-2 hover-scale cursor-pointer group">
                                <span class="w-2.5 h-2.5 bg-indigo-600 rounded-full group-hover:scale-125 transition-transform"></span>
                                <span class="text-xs font-bold text-slate-500 tracking-tight uppercase group-hover:text-indigo-600 transition-colors">Masuk</span>
                            </div>
                            <div class="flex items-center gap-2 hover-scale cursor-pointer group">
                                <span class="w-2.5 h-2.5 bg-amber-500 rounded-full group-hover:scale-125 transition-transform"></span>
                                <span class="text-xs font-bold text-slate-500 tracking-tight uppercase group-hover:text-amber-500 transition-colors">Keluar</span>
                            </div>
                        </div>
                    </div>

                    <div class="h-60 md:h-80">
                        <Line :data="chartData" :options="chartOptions" />
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white border border-slate-200 rounded-[2rem] shadow-sm overflow-hidden flex flex-col">
                    <div class="px-6 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/30">
                        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-[0.2em] flex items-center gap-3">
                            <PhClockCounterClockwise :size="20" weight="bold" class="text-indigo-600" />
                            Aktivitas
                        </h2>
                    </div>
                    <div class="flex-1">
                        <div class="divide-y divide-slate-100 italic">
                            <div v-for="tx in recentTransactions" :key="tx.id" class="p-5 hover:bg-slate-50 transition duration-300 flex items-center justify-between gap-4">
                                <div class="truncate">
                                    <p class="font-black text-slate-900 text-sm tracking-tight truncate">{{ tx.product?.nama }}</p>
                                    <p class="text-[11px] text-slate-400 font-bold uppercase tracking-widest mt-0.5 truncate">{{ new Date(tx.created_at).toLocaleDateString('id-ID') }} - Qty: {{ tx.quantity }}</p>
                                </div>
                                <span :class="['shrink-0 inline-flex items-center px-3 py-1 rounded-full text-[10px] font-black border uppercase tracking-tighter hover-scale cursor-default', tx.type === 'in' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-amber-50 text-amber-700 border-amber-100']">
                                    {{ tx.type === 'in' ? 'Masuk' : 'Keluar' }}
                                </span>

                            </div>
                            <div v-if="!recentTransactions || recentTransactions.length === 0" class="p-8 text-center text-xs font-bold text-slate-400 uppercase tracking-widest">
                                Belum ada aktivitas mutasi stok.
                            </div>
                        </div>
                    </div>
                    <div class="p-6 bg-slate-50/30 border-t border-slate-100 text-center">
                        <Link :href="route('barang-masuk.index')" class="text-[11px] text-indigo-600 font-black hover:text-indigo-700 underline underline-offset-8 decoration-2 uppercase tracking-[0.2em]">LIHAT SEMUA</Link>
                    </div>
                </div>
            </div>

            <!-- New Sections: Low Stock, Expiry Warnings & ABC Analysis -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8 mb-8">
                <!-- ABC Analysis -->
                <div class="bg-white border border-slate-200 rounded-[2rem] shadow-sm p-6 md:p-8 flex flex-col justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-[0.2em] mb-6 flex items-center gap-3">
                            <PhChartPie :size="20" weight="bold" class="text-brand-600" />
                            ABC Analysis (30 Hari)
                        </h2>
                        
                        <!-- Mini visual representation of categories -->
                        <div class="space-y-4 mb-6">
                            <div>
                                <div class="flex justify-between text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">
                                    <span>Kelas A (Fast Moving - 70%)</span>
                                    <span class="text-brand-600 font-extrabold">{{ abcAnalysis?.counts?.A || 0 }} SKU</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="bg-brand-500 h-full rounded-full" :style="{ width: `${((abcAnalysis?.counts?.A || 0) / ((abcAnalysis?.counts?.A || 0) + (abcAnalysis?.counts?.B || 0) + (abcAnalysis?.counts?.C || 0) || 1)) * 100}%` }"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">
                                    <span>Kelas B (Medium Moving - 20%)</span>
                                    <span class="text-amber-500 font-extrabold">{{ abcAnalysis?.counts?.B || 0 }} SKU</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="bg-amber-400 h-full rounded-full" :style="{ width: `${((abcAnalysis?.counts?.B || 0) / ((abcAnalysis?.counts?.A || 0) + (abcAnalysis?.counts?.B || 0) + (abcAnalysis?.counts?.C || 0) || 1)) * 100}%` }"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-xs font-bold text-slate-500 mb-1 uppercase tracking-wider">
                                    <span>Kelas C (Slow Moving - 10%)</span>
                                    <span class="text-slate-400 font-extrabold">{{ abcAnalysis?.counts?.C || 0 }} SKU</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="bg-slate-300 h-full rounded-full" :style="{ width: `${((abcAnalysis?.counts?.C || 0) / ((abcAnalysis?.counts?.A || 0) + (abcAnalysis?.counts?.B || 0) + (abcAnalysis?.counts?.C || 0) || 1)) * 100}%` }"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Top 5 products in class A -->
                        <div class="mt-6 border-t border-slate-100 pt-4">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Top Fast Moving (Kelas A)</h3>
                            <div class="space-y-3">
                                <div v-for="prod in abcAnalysis?.fast_moving || []" :key="prod.kode_barang" class="flex justify-between items-center text-xs">
                                    <div class="truncate pr-2">
                                        <p class="font-bold text-slate-700 truncate">{{ prod.nama }}</p>
                                        <p class="text-[10px] text-slate-400 font-mono">{{ prod.kode_barang }}</p>
                                    </div>
                                    <span class="shrink-0 font-bold bg-brand-50 text-brand-700 px-2 py-0.5 rounded-md border border-brand-100">
                                        {{ prod.total_out }} Out
                                    </span>
                                </div>
                                <div v-if="!abcAnalysis?.fast_moving || abcAnalysis.fast_moving.length === 0" class="text-center text-xs font-bold text-slate-400 py-2">
                                    Belum ada data analisis.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Products -->
                <div class="bg-white border border-slate-200 rounded-[2rem] shadow-sm p-6 md:p-8 flex flex-col">
                    <h2 class="text-sm font-bold text-slate-800 uppercase tracking-[0.2em] mb-6 flex items-center gap-3">
                        <PhWarning :size="20" weight="bold" class="text-rose-600" />
                        Peringatan Stok Rendah
                    </h2>
                    
                    <div class="flex-1 divide-y divide-slate-100">
                        <div v-for="prod in lowStockProducts" :key="prod.id" class="py-3 flex justify-between items-center gap-2">
                            <div class="truncate">
                                <p class="font-bold text-slate-800 text-xs truncate">{{ prod.nama }}</p>
                                <p class="text-[10px] text-slate-400 font-mono">{{ prod.kode_barang }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-100">
                                    Stok: {{ prod.total_stock }} / Min: {{ prod.stok_minimum }}
                                </span>
                            </div>
                        </div>
                        <div v-if="!lowStockProducts || lowStockProducts.length === 0" class="text-center text-xs font-bold text-slate-400 py-8">
                            Tidak ada produk di bawah stok minimum.
                        </div>
                    </div>
                </div>

                <!-- Near Expiry Items -->
                <div class="bg-white border border-slate-200 rounded-[2rem] shadow-sm p-6 md:p-8 flex flex-col">
                    <h2 class="text-sm font-bold text-slate-800 uppercase tracking-[0.2em] mb-6 flex items-center gap-3">
                        <PhHourglass :size="20" weight="bold" class="text-amber-500" />
                        Mendekati Kedaluwarsa
                    </h2>
                    
                    <div class="flex-1 divide-y divide-slate-100">
                        <div v-for="(item, idx) in nearExpiry" :key="idx" class="py-3 flex justify-between items-start gap-2">
                            <div class="truncate">
                                <p class="font-bold text-slate-800 text-xs truncate">{{ item.product_name }}</p>
                                <p class="text-[10px] text-slate-400 truncate">
                                    <span class="font-mono bg-slate-100 px-1 py-0.5 rounded text-[9px] mr-1">{{ item.rack_code }}</span>
                                    Batch: {{ item.batch_number || '-' }}
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-[11px] font-bold text-slate-700">{{ item.expired_at }}</p>
                                <span :class="['inline-block px-2 py-0.5 rounded-full text-[9px] font-bold border mt-1', item.days_left <= 7 ? 'bg-rose-50 text-rose-700 border-rose-100' : 'bg-amber-50 text-amber-700 border-amber-100']">
                                    {{ item.days_left }} hari lagi (Qty: {{ item.quantity }})
                                </span>
                            </div>
                        </div>
                        <div v-if="!nearExpiry || nearExpiry.length === 0" class="text-center text-xs font-bold text-slate-400 py-8">
                            Tidak ada produk mendekati kedaluwarsa.
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    </div>
</template>
