<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { PhArrowLeft, PhPrinter } from "@phosphor-icons/vue";
import { formatRupiah, formatNumber } from '@/utils/report.js';
import { computed } from 'vue';

const props = defineProps({
    reportData:  { type: Array,  default: () => [] },
    summary:     { type: Object, default: () => ({}) },
    typeLabel:   { type: String, default: 'LAPORAN' },
    filters:     { type: Object, default: () => ({}) },
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const [year, month, day] = String(dateStr).split('T')[0].split('-').map(Number);
    return `${day} ${months[month - 1]} ${year}`;
};

const formatDateShort = (dateStr) => {
    if (!dateStr) return '-';
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const [year, month, day] = String(dateStr).split('T')[0].split('-').map(Number);
    return `${day} ${months[month - 1]} ${year}`;
};

const printDoc = () => window.print();

const nomor = computed(() => {
    const d = new Date();
    const ymd = d.toISOString().split('T')[0].replace(/-/g, '');
    const t   = props.filters?.type === 'masuk' ? 'RPT-IN' : props.filters?.type === 'keluar' ? 'RPT-OUT' : 'RPT-STK';
    return `${t}-${ymd}`;
});
</script>

<template>
    <Head :title="typeLabel" />
    <AuthenticatedLayout :title="typeLabel">

        <!-- ── TOMBOL UI (disembunyikan saat print) ── -->
        <div class="mb-6 flex items-center justify-between no-print font-sans">
            <Link :href="route('reports.index', { type: filters.type, start_date: filters.start_date, end_date: filters.end_date })"
                  class="text-[11px] font-bold text-slate-500 hover:text-[#1E3A5F] flex items-center gap-2 uppercase tracking-wider transition">
                <PhArrowLeft weight="bold" /> Kembali ke Laporan
            </Link>
            <button @click="printDoc"
                    class="bg-[#1E3A5F] text-white px-6 py-2 text-[11px] font-bold uppercase tracking-widest hover:bg-[#162a45] transition shadow-sm active:scale-95 flex items-center gap-2">
                <PhPrinter weight="fill" /> Cetak Dokumen Resmi
            </button>
        </div>

        <!-- ══════════════════════════════════════════
             DOKUMEN RESMI — gaya sama seperti invoice
             ══════════════════════════════════════════ -->
        <div class="print-container bg-white border border-[#D1D5DB] shadow-sm relative font-business text-[#1F2937] leading-tight mx-auto">

            <!-- Watermark sudut -->
            <div class="absolute top-4 right-8 text-[8px] italic font-black text-slate-300 uppercase tracking-[0.2em]">
                Lembar 1 - ASLI (Arsip)
            </div>

            <!-- ── 1. KOP SURAT ── -->
            <div class="p-6 pb-4 border-b-[3px] border-double border-[#1F2937]">
                <div class="flex justify-between items-start">
                    <!-- Logo + Identitas -->
                    <div class="flex items-center gap-5">
                        <div v-if="$page.props.company?.logo"
                             class="w-16 h-16 bg-white border border-slate-100 flex items-center justify-center p-1.5">
                            <img :src="'/storage/' + $page.props.company.logo" class="w-full h-full object-contain" alt="Logo">
                        </div>
                        <div v-else class="w-16 h-16 bg-[#1E3A5F] flex items-center justify-center text-white font-serif font-black text-2xl">
                            LJE
                        </div>
                        <div>
                            <h1 class="text-[15px] font-serif font-black uppercase tracking-tight text-[#1E3A5F] mb-1">
                                {{ $page.props.company?.name || 'CV. LISTRINDO JAYA ELEKTRIK' }}
                            </h1>
                            <p class="text-[8.5px] font-sans font-medium text-[#4B5563] leading-relaxed">
                                {{ $page.props.company?.address || 'Jl. Tebet Raya No. 11G, Tebet Barat, Jakarta Selatan 12810' }}<br>
                                Telp: {{ $page.props.company?.phone_primary || '-' }}
                                | Email: {{ $page.props.company?.email || 'info@listrindojaya.co.id' }}<br>
                                NPWP: {{ $page.props.company?.npwp || '01.234.567.8-901.000' }}
                            </p>
                        </div>
                    </div>

                    <!-- Judul Dokumen -->
                    <div class="text-right border border-[#1F2937] p-3 min-w-[200px]">
                        <h2 class="text-[12px] font-serif font-black uppercase tracking-widest border-b border-[#D1D5DB] pb-1 mb-1.5">
                            {{ typeLabel }}
                        </h2>
                        <p class="text-[9px] font-sans font-bold text-[#4B5563]">No: {{ nomor }}</p>
                        <p class="text-[9px] font-sans text-[#4B5563] mt-0.5">
                            Dicetak: {{ formatDate(new Date().toISOString()) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- ── 2. INFO PERIHAL ── -->
            <div class="px-8 pt-5 pb-3 flex justify-between items-start text-[10px]">
                <div class="space-y-1">
                    <p><span class="font-black w-28 inline-block">PERIHAL</span>: {{ typeLabel }}</p>
                    <p><span class="font-black w-28 inline-block">PERIODE</span>: {{ formatDate(filters.start_date) }} — {{ formatDate(filters.end_date) }}</p>
                    <p><span class="font-black w-28 inline-block">TOTAL TRANSAKSI</span>: {{ formatNumber(summary.total_transaksi) }} transaksi</p>
                    <p><span class="font-black w-28 inline-block">DIBUAT OLEH</span>: {{ $page.props.auth?.user?.name?.toUpperCase() || '-' }}</p>
                </div>
                <div class="text-right space-y-1">
                    <p class="text-[9px] font-sans">Jakarta, {{ formatDate(new Date().toISOString()) }}</p>
                    <div class="inline-block border-2 border-double border-[#1E3A5F] px-4 py-1.5 mt-2">
                        <span class="text-[10px] font-serif font-black text-[#1E3A5F] uppercase tracking-widest">DOKUMEN RESMI</span>
                    </div>
                </div>
            </div>

            <!-- ── 3. RINGKASAN KPI ── -->
            <div class="mx-8 mb-4 grid grid-cols-4 border border-[#D1D5DB] text-center text-[9px]">
                <div class="p-3 border-r border-[#D1D5DB]">
                    <p class="font-black uppercase tracking-wide text-[#6B7280] mb-1">Total Transaksi</p>
                    <p class="text-[18px] font-serif font-black text-[#1E3A5F]">{{ formatNumber(summary.total_transaksi) }}</p>
                    <p class="text-[8px] text-[#6B7280]">Dokumen</p>
                </div>
                <div class="p-3 border-r border-[#D1D5DB]">
                    <p class="font-black uppercase tracking-wide text-[#6B7280] mb-1">{{ summary.label_qty }}</p>
                    <p class="text-[18px] font-serif font-black text-[#1E3A5F]">{{ formatNumber(summary.total_qty) }}</p>
                    <p class="text-[8px] text-[#6B7280]">Unit / Pcs / Roll</p>
                </div>
                <div class="p-3 border-r border-[#D1D5DB] bg-[#1E3A5F] text-white">
                    <p class="font-black uppercase tracking-wide text-blue-200 mb-1">{{ summary.label_nilai }}</p>
                    <p class="text-[15px] font-serif font-black">{{ formatRupiah(summary.total_nilai) }}</p>
                    <p class="text-[8px] text-blue-300">Grand Total</p>
                </div>
                <div class="p-3">
                    <p class="font-black uppercase tracking-wide text-[#6B7280] mb-1">Rata-rata/Transaksi</p>
                    <p class="text-[13px] font-serif font-black text-[#1E3A5F]">
                        {{ summary.total_transaksi > 0 ? formatRupiah(Math.round(summary.total_nilai / summary.total_transaksi)) : 'Rp 0' }}
                    </p>
                    <p class="text-[8px] text-[#6B7280]">Per Dokumen</p>
                </div>
            </div>

            <!-- ── 4. TABEL RINCIAN ── -->
            <div class="px-8 pb-2">
                <table class="w-full text-[9px] border-collapse">
                    <thead>
                        <tr class="bg-[#1E3A5F] text-white">
                            <th class="px-3 py-2 text-left font-black uppercase tracking-wide border border-[#1E3A5F] w-6">No</th>
                            <th class="px-3 py-2 text-left font-black uppercase tracking-wide border border-[#1E3A5F]">Tanggal</th>
                            <th class="px-3 py-2 text-left font-black uppercase tracking-wide border border-[#1E3A5F]">No. Referensi</th>
                            <th class="px-3 py-2 text-left font-black uppercase tracking-wide border border-[#1E3A5F]">
                                {{ filters.type === 'masuk' ? 'Supplier' : filters.type === 'keluar' ? 'Customer' : 'Gudang' }}
                            </th>
                            <th class="px-3 py-2 text-left font-black uppercase tracking-wide border border-[#1E3A5F]">Produk</th>
                            <th class="px-3 py-2 text-right font-black uppercase tracking-wide border border-[#1E3A5F]">Qty</th>
                            <th class="px-3 py-2 text-center font-black uppercase tracking-wide border border-[#1E3A5F]">Sat.</th>
                            <th class="px-3 py-2 text-right font-black uppercase tracking-wide border border-[#1E3A5F]">Harga Satuan</th>
                            <th class="px-3 py-2 text-right font-black uppercase tracking-wide border border-[#1E3A5F]">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!reportData?.length">
                            <td colspan="9" class="px-3 py-6 text-center text-[#9CA3AF] italic border border-[#E5E7EB]">
                                Tidak ada data untuk periode yang dipilih.
                            </td>
                        </tr>
                        <tr v-for="(item, i) in reportData" :key="i"
                            :class="i % 2 === 0 ? 'bg-white' : 'bg-[#F8FAFC]'">
                            <td class="px-3 py-1.5 text-center text-[#9CA3AF] border border-[#E5E7EB]">{{ i + 1 }}</td>
                            <td class="px-3 py-1.5 text-[#4B5563] border border-[#E5E7EB] whitespace-nowrap">{{ formatDateShort(item.tgl) }}</td>
                            <td class="px-3 py-1.5 font-bold border border-[#E5E7EB] whitespace-nowrap">{{ item.ref }}</td>
                            <td class="px-3 py-1.5 border border-[#E5E7EB]" style="max-width: 130px; word-break: break-word;">{{ item.pihak }}</td>
                            <td class="px-3 py-1.5 border border-[#E5E7EB]" style="max-width: 150px; word-break: break-word;">{{ item.product }}</td>
                            <td class="px-3 py-1.5 text-right font-bold border border-[#E5E7EB] tabular-nums">{{ formatNumber(item.qty) }}</td>
                            <td class="px-3 py-1.5 text-center text-[#6B7280] border border-[#E5E7EB]">{{ item.satuan || 'pcs' }}</td>
                            <td class="px-3 py-1.5 text-right border border-[#E5E7EB] tabular-nums">{{ formatRupiah(item.harga_satuan) }}</td>
                            <td class="px-3 py-1.5 text-right font-bold border border-[#E5E7EB] tabular-nums">{{ formatRupiah(item.total) }}</td>
                        </tr>
                    </tbody>
                    <!-- Subtotal row -->
                    <tfoot>
                        <tr class="bg-[#F1F5F9] border-t-2 border-[#1E3A5F]">
                            <td colspan="5" class="px-3 py-2 font-black text-right text-[#1E3A5F] uppercase tracking-wide border border-[#D1D5DB]">
                                GRAND TOTAL
                            </td>
                            <td class="px-3 py-2 text-right font-black text-[#1E3A5F] border border-[#D1D5DB] tabular-nums">
                                {{ formatNumber(summary.total_qty) }}
                            </td>
                            <td class="border border-[#D1D5DB]"></td>
                            <td class="border border-[#D1D5DB]"></td>
                            <td class="px-3 py-2 text-right font-black text-[#1E3A5F] border border-[#D1D5DB] tabular-nums">
                                {{ formatRupiah(summary.total_nilai) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- ── 5. CATATAN ── -->
            <div class="mx-8 mt-3 mb-5 p-3 border border-[#E5E7EB] bg-[#FFFBEB] text-[8.5px] text-[#78350F]">
                <p class="font-black uppercase tracking-wide mb-1">Keterangan:</p>
                <p>• Dokumen ini merupakan laporan resmi yang dihasilkan oleh Sistem Manajemen Gudang (WMS) CV. Listrindo Jaya Elektrik.</p>
                <p>• Nilai yang tercantum merupakan estimasi berdasarkan harga jual produk yang terdaftar di sistem.</p>
                <p>• Laporan ini sah tanpa tanda tangan apabila dicetak langsung dari sistem.</p>
            </div>

            <!-- ── 6. SLOT TANDA TANGAN ── -->
            <div class="mx-8 mb-8 grid grid-cols-3 gap-8 text-[9px] text-center">
                <div>
                    <p class="font-black uppercase tracking-wide text-[#6B7280] mb-16">Dibuat Oleh,</p>
                    <div class="border-t border-[#1F2937] pt-1">
                        <p class="font-black">( {{ $page.props.auth?.user?.name || '.................................' }} )</p>
                        <p class="text-[#6B7280]">Admin Gudang</p>
                    </div>
                </div>
                <div>
                    <p class="font-black uppercase tracking-wide text-[#6B7280] mb-16">Diperiksa Oleh,</p>
                    <div class="border-t border-[#1F2937] pt-1">
                        <p class="font-black">( ................................. )</p>
                        <p class="text-[#6B7280]">Supervisor Gudang</p>
                    </div>
                </div>
                <div>
                    <p class="font-black uppercase tracking-wide text-[#6B7280] mb-16">Disetujui Oleh,</p>
                    <div class="border-t border-[#1F2937] pt-1">
                        <p class="font-black">( ................................. )</p>
                        <p class="text-[#6B7280]">Direktur / Manager</p>
                    </div>
                </div>
            </div>

            <!-- ── 7. FOOTER DOKUMEN ── -->
            <div class="border-t border-[#D1D5DB] px-8 py-3 flex justify-between items-center text-[7.5px] text-[#9CA3AF]">
                <p>Dicetak dari WMS — {{ $page.props.company?.name }}</p>
                <p class="italic">{{ typeLabel }} | Periode {{ filters.start_date }} s/d {{ filters.end_date }}</p>
                <p>Halaman 1</p>
            </div>

        </div><!-- /print-container -->

    </AuthenticatedLayout>
</template>

<style scoped>
.print-container {
    max-width: 960px;
    font-family: 'Times New Roman', 'Georgia', serif;
}

.font-business {
    font-family: 'Plus Jakarta Sans', Arial, sans-serif;
}

@media print {
    @page { margin: 10mm; size: A4 portrait; }

    .no-print { display: none !important; }

    .print-container {
        max-width: 100% !important;
        width: 100% !important;
        border: none !important;
        box-shadow: none !important;
    }

    /* Repeat tabel header tiap halaman */
    thead { display: table-header-group; }
    tfoot { display: table-footer-group; }
    tr    { page-break-inside: avoid; }

    /* Slot TTD jangan terpotong */
    .grid.grid-cols-3 { page-break-inside: avoid; }

    /* Paksa warna background muncul di print */
    .bg-\[#1E3A5F\] {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        background-color: #1E3A5F !important;
        color: white !important;
    }
    .bg-\[#F8FAFC\] {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        background-color: #F8FAFC !important;
    }
    .bg-\[#F1F5F9\] {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        background-color: #F1F5F9 !important;
    }
    .bg-\[#FFFBEB\] {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        background-color: #FFFBEB !important;
    }
}
</style>
