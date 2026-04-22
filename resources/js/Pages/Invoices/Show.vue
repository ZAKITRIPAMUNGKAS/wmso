<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { 
    PhPrinter, 
    PhArrowLeft, 
    PhBank,
    PhSealCheck,
    PhWarning,
    PhCircleWavyCheck,
    PhCircleWavyWarning
} from "@phosphor-icons/vue";
import { computed } from 'vue';

const props = defineProps({
    invoice: {
        type: Object,
        default: () => null
    },
    company: {
        type: Object,
        default: () => ({})
    }
});

// 6. KONSISTEN FORMAT RUPIAH (Rp 1.000.000)
const formatRupiah = (angka) => {
    if (angka === undefined || angka === null) return "Rp 0";
    const num = Number(angka);
    return 'Rp ' + num.toLocaleString('id-ID');
};

// 9. TANGGAL FORMAT RAPI (Title Case)
const formatDateRapi = (dateString) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return "-";
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
};

const getOfficialNumber = (invoice) => {
    if (!invoice || !invoice.id) return "-";
    const date = new Date(invoice.tanggal);
    const romanMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
    const id = String(invoice.id).padStart(3, '0');
    return `INV/${id}/LJE/${romanMonths[date.getMonth()]}/${date.getFullYear()}`;
};

// 1. TERBILANG DEFENSIVE (MATCH GRAND TOTAL)
const terbilang = (angka) => {
    if (angka === undefined || angka === null || angka === '' || isNaN(angka)) return 'Nol Rupiah';
    const num = Math.floor(typeof angka === 'number' ? angka : parseFloat(angka));
    if (num <= 0) return 'Nol Rupiah';

    const satuan = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    const konversi = (n) => {
        if (n < 12) return satuan[n];
        else if (n < 20) return konversi(n - 10) + " Belas";
        else if (n < 100) return konversi(Math.floor(n / 10)) + " Puluh " + konversi(n % 10);
        else if (n < 200) return "Seratus " + konversi(n - 100);
        else if (n < 1000) return konversi(Math.floor(n / 100)) + " Ratus " + konversi(n % 100);
        else if (n < 2000) return "Seribu " + konversi(n - 1000);
        else if (n < 1000000) return konversi(Math.floor(n / 1000)) + " Ribu " + konversi(n % 1000);
        else if (n < 1000000000) return konversi(Math.floor(n / 1000000)) + " Juta " + konversi(n % 1000000);
        return "";
    };
    return konversi(num).trim().replace(/\s+/g, ' ') + " Rupiah";
};

const grandTotal = computed(() => {
    const subtotal = Number(props.invoice?.total || 0);
    return subtotal * 1.11; // Include PPN 11%
});

const terbilangGrandTotal = computed(() => {
    return terbilang(grandTotal.value).toUpperCase();
});

const dueDateText = computed(() => {
    if (!props.invoice?.tanggal) return "-";
    const date = new Date(props.invoice.tanggal);
    date.setDate(date.getDate() + 30);
    return formatDateRapi(date);
});

const statusConfig = computed(() => {
    const isLunas = props.invoice?.status === 'lunas';
    return {
        label: isLunas ? 'PAID' : 'UNPAID',
        class: isLunas ? 'border-emerald-500 text-emerald-600 bg-emerald-50/30' : 'border-rose-500 text-rose-600 bg-rose-50/30',
        icon: isLunas ? PhCircleWavyCheck : PhCircleWavyWarning
    };
});

const print = () => window.print();
</script>

<template>
    <Head title="Invoice Detail" />

    <!-- LOADING STATE -->
    <div v-if="!invoice" class="min-h-screen bg-slate-100 flex items-center justify-center font-sans">
        <div class="text-center">
            <div class="w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Sinkronisasi Data...</p>
        </div>
    </div>

    <!-- DOCUMENT PAGE -->
    <div v-else class="min-h-screen bg-slate-50 py-10 font-sans print:p-0 print:bg-white">
        
        <!-- ACTION BAR -->
        <div class="max-w-[210mm] mx-auto mb-6 flex justify-between items-center px-4 md:px-0 no-print">
            <div class="flex items-center gap-4">
                <Link :href="route('invoices.index')" class="p-2.5 bg-white border border-slate-200 rounded-xl text-slate-400 hover:text-indigo-600 transition shadow-sm active:scale-90">
                    <PhArrowLeft :size="20" weight="bold" />
                </Link>
                <div>
                    <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Invoice Explorer</h2>
                    <p class="text-[10px] text-slate-800 font-black uppercase">{{ getOfficialNumber(invoice) }}</p>
                </div>
            </div>
            <button @click="print" class="flex items-center gap-2 bg-[#1E3A5F] text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-900/20 active:scale-95 transition">
                <PhPrinter weight="bold" /> Cetak Dokumen
            </button>
        </div>

        <!-- 3 & 5. THE DOCUMENT (A4 CONSTRAINTS & SOFT WATERMARK) -->
        <div class="invoice-container bg-white mx-auto relative overflow-hidden print:shadow-none shadow-2xl border border-slate-200 print:border-none">
            
            <!-- 5. SOFT WATERMARK -->
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none select-none z-0">
                <p :class="[invoice.status === 'lunas' ? 'text-emerald-500' : 'text-rose-500']" 
                   class="text-[100px] font-black uppercase -rotate-[35deg] opacity-[0.04] tracking-[0.4em] whitespace-nowrap">
                    {{ statusConfig.label }}
                </p>
            </div>

            <div class="relative z-10 flex flex-col h-full min-h-[267mm]">
                <!-- KOP SURAT -->
                <div class="flex justify-between items-start border-b-[3px] border-[#1E3A5F] pb-6 mb-8">
                    <div class="flex gap-5">
                        <div v-if="company?.logo" class="w-16 h-16 bg-white border border-slate-100 flex items-center justify-center p-1 shadow-sm">
                            <img :src="'/storage/' + company.logo" class="w-full h-full object-contain" alt="Logo">
                        </div>
                        <div v-else class="w-16 h-16 bg-[#1E3A5F] flex items-center justify-center text-white font-serif font-black text-2xl">LJE</div>
                        <div>
                            <h1 class="text-xl font-serif font-black text-[#1E3A5F] uppercase leading-none mb-2">CV. LISTRINDO JAYA ELEKTRIK</h1>
                            <p class="text-[10px] font-medium text-slate-500 leading-relaxed">
                                Jl. Tebet Raya No. 11G, Tebet Barat, Jakarta Selatan 12810<br>
                                Telp: {{ company?.phone_primary || '-' }} | Email: {{ company?.email || 'info@listrindojaya.co.id' }}<br>
                                NPWP: 01.234.567.8-901.000
                            </p>
                        </div>
                    </div>
                    <div class="text-right flex flex-col items-end gap-2">
                        <p class="text-[11px] font-bold text-slate-800">Jakarta, {{ formatDateRapi(invoice?.tanggal) }}</p>
                        <!-- 8. STATUS BADGE SMALL -->
                        <div :class="['flex items-center gap-1.5 px-3 py-1.5 border-2 rounded-lg text-[10px] font-black tracking-widest uppercase', statusConfig.class]">
                            <component :is="statusConfig.icon" :size="14" weight="bold" />
                            {{ statusConfig.label }}
                        </div>
                    </div>
                </div>

                <!-- DOCUMENT TITLE -->
                <h2 class="text-2xl font-serif font-black text-[#1E3A5F] uppercase text-center tracking-[0.4em] mb-8 border-b border-slate-50 pb-4">INVOICE</h2>

                <!-- 2. HEADER INFO SYMMETRICAL (4x4 Rows) -->
                <div class="grid grid-cols-2 gap-12 mb-8 text-[11px]">
                    <div class="space-y-1.5">
                        <p class="flex"><span class="font-black min-w-[120px] text-slate-400">KEPADA YTH.</span> <span class="mr-2">:</span> <span class="font-black text-slate-800 uppercase">{{ invoice?.delivery_order?.customer?.nama || '-' }}</span></p>
                        <p class="flex"><span class="font-black min-w-[120px] text-slate-400">ALAMAT</span> <span class="mr-2">:</span> <span class="font-bold text-slate-600 leading-tight">{{ invoice?.delivery_order?.customer?.alamat || '-' }}</span></p>
                        <p class="flex"><span class="font-black min-w-[120px] text-slate-400">PO NUMBER</span> <span class="mr-2">:</span> <span class="font-black text-slate-800">{{ invoice?.delivery_order?.po_number || '-' }}</span></p>
                        <p class="flex"><span class="font-black min-w-[120px] text-slate-400">PERIHAL</span> <span class="mr-2">:</span> <span class="font-bold text-slate-600">Penagihan Pembayaran (Invoice)</span></p>
                    </div>
                    <div class="space-y-1.5 ml-auto">
                        <p class="flex"><span class="font-black min-w-[120px] text-slate-400">NO. INVOICE</span> <span class="mr-2">:</span> <span class="font-black text-slate-800">{{ getOfficialNumber(invoice) }}</span></p>
                        <p class="flex"><span class="font-black min-w-[120px] text-slate-400">NO. SURAT JALAN</span> <span class="mr-2">:</span> <span class="font-black text-slate-800">{{ invoice?.delivery_order?.no_sj || '-' }}</span></p>
                        <p class="flex"><span class="font-black min-w-[120px] text-slate-400">JATUH TEMPO</span> <span class="mr-2">:</span> <span class="font-black text-rose-600 uppercase">{{ dueDateText }}</span></p>
                        <p class="flex"><span class="font-black min-w-[120px] text-slate-400">STATUS</span> <span class="mr-2">:</span> <span :class="[invoice?.status === 'lunas' ? 'text-emerald-600' : 'text-rose-600']" class="font-black uppercase">{{ statusConfig.label }}</span></p>
                    </div>
                </div>

                <!-- 4. TABLE (DYNAMIC ROWS - NO EMPTY FILL) -->
                <div class="flex-grow">
                    <table class="w-full border-collapse border-2 border-[#1E3A5F]">
                        <thead>
                            <tr class="bg-[#1E3A5F] text-white">
                                <th class="border border-white/20 p-3 text-[10px] font-black uppercase w-10 text-center">NO</th>
                                <th class="border border-white/20 p-3 text-[10px] font-black uppercase text-left">DESKRIPSI BARANG / PRODUK</th>
                                <th class="border border-white/20 p-3 text-[10px] font-black uppercase text-center w-20">QTY</th>
                                <th class="border border-white/20 p-3 text-[10px] font-black uppercase text-right w-36">HARGA SATUAN</th>
                                <th class="border border-white/20 p-3 text-[10px] font-black uppercase text-right w-40 pr-4">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody class="text-[11px] text-slate-700">
                            <tr v-for="(item, idx) in invoice?.delivery_order?.items || []" :key="idx" class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                                <td class="border-x border-slate-200 p-3 text-center font-bold text-slate-400">{{ idx + 1 }}</td>
                                <td class="border-x border-slate-200 p-3 font-black text-[#1E3A5F] uppercase">{{ item.product?.nama || '-' }}</td>
                                <td class="border-x border-slate-200 p-3 text-center font-black">{{ item.quantity || 0 }} PCS</td>
                                <td class="border-x border-slate-200 p-3 text-right font-bold">{{ formatRupiah(item.harga) }}</td>
                                <td class="border-x border-slate-200 p-3 text-right pr-4 font-black text-slate-900">{{ formatRupiah(item.subtotal) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- BREAKDOWN & BANK INFO -->
                <div class="mt-8 flex justify-between items-start gap-12 no-page-break">
                    <div class="w-1/2">
                        <!-- 1. TERBILANG (MATCH GRAND TOTAL) -->
                        <div class="bg-slate-50 p-4 border-l-4 border-[#1E3A5F] mb-6">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Terbilang:</p>
                            <p class="text-[11px] font-serif font-black text-[#1E3A5F] italic leading-relaxed">
                                {{ terbilangGrandTotal }}
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-3">
                            <div class="flex items-center gap-4 p-3 border border-slate-100 rounded-xl bg-white shadow-sm">
                                <div class="w-12 h-10 flex items-center justify-center rounded-lg overflow-hidden">
                                    <img src="/images/banks/bca.png" class="w-full h-full object-contain" alt="BCA">
                                </div>
                                <div class="text-[10px]">
                                    <p class="font-black text-[#1E3A5F] tracking-widest uppercase">Bank BCA: 1234-5678-90</p>
                                    <p class="font-bold text-slate-400 uppercase">A/N CV. Listrindo Jaya Elektrik</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 p-3 border border-slate-100 rounded-xl bg-white shadow-sm">
                                <div class="w-12 h-10 flex items-center justify-center rounded-lg overflow-hidden">
                                    <img src="/images/banks/mandiri.png" class="w-full h-full object-contain" alt="Mandiri">
                                </div>
                                <div class="text-[10px]">
                                    <p class="font-black text-[#1E3A5F] tracking-widest uppercase">Bank Mandiri: 0987-6543-21</p>
                                    <p class="font-bold text-slate-400 uppercase">A/N CV. Listrindo Jaya Elektrik</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="w-1/3 text-[11px] space-y-2">
                        <div class="flex justify-between py-1.5 border-b border-slate-50">
                            <span class="font-bold text-slate-400 uppercase tracking-tighter">SUBTOTAL</span>
                            <span class="font-black text-slate-800">{{ formatRupiah(invoice?.total) }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-slate-50">
                            <span class="font-bold text-slate-400 uppercase tracking-tighter">PPN (11%)</span>
                            <span class="font-black text-slate-800">{{ formatRupiah((invoice?.total || 0) * 0.11) }}</span>
                        </div>
                        <div class="flex justify-between py-3 bg-[#1E3A5F] text-white px-4 mt-2 shadow-lg shadow-blue-900/10">
                            <span class="font-black uppercase tracking-widest">GRAND TOTAL</span>
                            <span class="font-black text-base">{{ formatRupiah(grandTotal) }}</span>
                        </div>
                    </div>
                </div>

                <!-- 3 & 7. SIGNATURE SECTION (PREVENT PAGE BREAK & NO OVERLAP) -->
                <div class="signature-section mt-auto pt-12 grid grid-cols-3 gap-12 text-center text-[11px]">
                    <div class="space-y-20">
                        <p class="font-black uppercase tracking-widest text-slate-400">Penerima / Customer</p>
                        <div>
                            <div class="border-b-2 border-slate-800 w-40 mx-auto"></div>
                            <p class="text-[8px] font-bold text-slate-300 uppercase mt-2">( Nama Terang & Stempel )</p>
                        </div>
                    </div>
                    <div class="space-y-20">
                        <p class="font-black uppercase tracking-widest text-slate-400">Keuangan</p>
                        <div>
                            <div class="border-b-2 border-slate-800 w-40 mx-auto"></div>
                            <p class="text-[8px] font-bold text-slate-300 uppercase mt-2">( Mengetahui )</p>
                        </div>
                    </div>
                    <!-- 7. FIX OVERLAP STEMPEL -->
                    <div class="space-y-2 relative">
                        <p class="font-black uppercase tracking-widest text-slate-400 mb-2">Hormat Kami,</p>
                        <p class="font-black text-slate-800 uppercase text-[10px]">CV. Listrindo Jaya Elektrik</p>
                        
                        <div class="relative h-28 flex items-center justify-center group">
                            <!-- 7. STEMPEL PLACEHOLDER (SOFT & POSITIONED) -->
                            <div class="absolute w-24 h-24 border-2 border-dashed border-indigo-100 rounded-full flex items-center justify-center opacity-40 -rotate-12">
                                <p class="text-[8px] font-black text-indigo-300 uppercase text-center">Stempel<br>Perusahaan</p>
                            </div>
                            <PhSealCheck :size="64" weight="thin" class="text-[#1E3A5F] opacity-[0.05]" />
                        </div>

                        <div>
                            <div class="border-b-2 border-slate-800 w-48 mx-auto"></div>
                            <p class="text-[8px] font-black text-slate-800 uppercase mt-2 italic tracking-widest">Administrator WMS</p>
                        </div>
                    </div>
                </div>

                <!-- 10. DISCLAIMER & TERMS -->
                <div class="mt-12 pt-6 border-t border-slate-100">
                    <p class="text-[8px] italic text-slate-400 text-center leading-relaxed font-medium uppercase tracking-tighter">
                        
                        Mohon konfirmasi bukti transfer via WhatsApp: 0812-2507-9988. <br>
                        "Pembayaran sah apabila sudah diterima di rekening perusahaan kami."
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700;900&display=swap');
.font-serif { font-family: 'Merriweather', 'Georgia', serif; }

.invoice-container {
    width: 210mm;
    min-height: 297mm; /* Standard A4 height */
    padding: 15mm;
    box-sizing: border-box;
}

@media print {
    body { background: white !important; margin: 0 !important; }
    .no-print { display: none !important; }
    .invoice-container { 
        width: 100% !important; 
        height: auto !important;
        min-height: 0 !important;
        border: none !important;
        padding: 10mm !important;
        margin: 0 !important;
    }
    
    /* 3. PREVENT PAGE BREAK FOR SIGNATURES */
    .signature-section {
        page-break-inside: avoid;
        break-inside: avoid;
    }
    
    .no-page-break {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    @page {
        size: A4;
        margin: 0;
    }
}
</style>
