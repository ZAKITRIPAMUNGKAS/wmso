<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, reactive, ref, onMounted } from 'vue';
import { 
    PhPrinter, 
    PhArrowLeft, 
    PhBank,
    PhSealCheck,
    PhWarning,
    PhCircleWavyCheck,
    PhCircleWavyWarning,
    PhGear,
    PhPlus,
    PhTrash,
    PhCaretRight,
    PhX
} from "@phosphor-icons/vue";

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

const showCustomizer = ref(false);

const settings = reactive({
    invoiceNumber: getOfficialNumber(props.invoice),
    invoiceDate: formatDateRapi(props.invoice?.tanggal),
    receiverName: '',
    signatoryName: 'Administrator WMS',
    showNpwp: true,
    ppnPercent: 11,
    footerLine1: 'Mohon konfirmasi bukti transfer via WhatsApp: 0812-2507-9988.',
    footerLine2: '"Pembayaran sah apabila sudah diterima di rekening perusahaan kami."',
    banks: [
        { name: 'Bank BCA', number: '1234-5678-90', holder: 'A/N CV. Listrindo Jaya Elektrik', logo: '/images/banks/bca.png' },
        { name: 'Bank Mandiri', number: '0987-6543-21', holder: 'A/N CV. Listrindo Jaya Elektrik', logo: '/images/banks/mandiri.png' }
    ]
});

const addBank = () => {
    settings.banks.push({ name: 'Bank Baru', number: '0000-0000-00', holder: 'A/N Nama Rekening', logo: '' });
};

const removeBank = (index) => {
    settings.banks.splice(index, 1);
};

const grandTotal = computed(() => {
    const subtotal = Number(props.invoice?.total || 0);
    const taxFactor = 1 + (settings.ppnPercent / 100);
    return subtotal * taxFactor;
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
            <div class="flex items-center gap-3">
                <button v-if="$page.props.auth.user.role !== 'viewer'" @click="showCustomizer = true" class="flex items-center gap-2 bg-white border border-slate-200 text-slate-700 px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-sm hover:border-indigo-600 hover:text-indigo-600 transition">
                    <PhGear weight="bold" :size="16" /> Kustomisasi
                </button>
                <button @click="print" class="flex items-center gap-2 bg-[#1E3A5F] text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-900/20 active:scale-95 transition">
                    <PhPrinter weight="bold" /> Cetak Dokumen
                </button>
            </div>
        </div>

        <!-- CUSTOMIZATION SIDEBAR -->
        <Transition
            enter-active-class="transition duration-500 ease-out"
            enter-from-class="opacity-0 translate-x-full"
            enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition duration-500 ease-in"
            leave-from-class="opacity-100 translate-x-0"
            leave-to-class="opacity-0 translate-x-full"
        >
            <div v-if="showCustomizer" class="fixed inset-y-0 right-0 w-80 bg-white shadow-[-20px_0_60px_rgba(0,0,0,0.1)] z-[100] no-print flex flex-col border-l border-slate-100">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Kustomisasi</h3>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Edit tampilan dokumen</p>
                    </div>
                    <button @click="showCustomizer = false" class="p-2 hover:bg-white rounded-lg text-slate-400 hover:text-rose-500 transition shadow-sm">
                        <PhX :size="20" weight="bold" />
                    </button>
                </div>
                
                <div class="flex-1 overflow-y-auto p-6 space-y-8 scrollbar-hide">
                    <!-- General Settings -->
                    <div class="space-y-4">
                        <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">Informasi Umum</p>
                        
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Nomor Invoice</label>
                            <input v-model="settings.invoiceNumber" type="text" class="w-full bg-slate-50 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600/20">
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Tanggal Invoice</label>
                            <input v-model="settings.invoiceDate" type="text" class="w-full bg-slate-50 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600/20">
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Nama Penerima (Tanda Tangan)</label>
                            <input v-model="settings.receiverName" type="text" placeholder="Nama Terang & Stempel" class="w-full bg-slate-50 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600/20">
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black text-slate-400 uppercase">Nama Pengirim (Tanda Tangan)</label>
                            <input v-model="settings.signatoryName" type="text" class="w-full bg-slate-50 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600/20">
                        </div>

                        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                            <span class="text-[9px] font-black text-slate-600 uppercase">Tampilkan NPWP</span>
                            <button @click="settings.showNpwp = !settings.showNpwp" 
                                    :class="[settings.showNpwp ? 'bg-indigo-600' : 'bg-slate-300']"
                                    class="w-10 h-5 rounded-full relative transition-colors duration-300">
                                <div :class="[settings.showNpwp ? 'translate-x-5' : 'translate-x-1']" 
                                     class="absolute top-1 w-3 h-3 bg-white rounded-full transition-transform duration-300"></div>
                            </button>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black text-slate-400 uppercase">PPN (%)</label>
                            <div class="relative">
                                <input v-model.number="settings.ppnPercent" type="number" class="w-full bg-slate-50 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600/20 pr-8">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400">%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Settings -->
                    <div class="space-y-4 pt-4 border-t border-slate-100">
                        <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">Keterangan Bawah</p>
                        <div class="space-y-3">
                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black text-slate-400 uppercase">Baris 1 (WA/Konfirmasi)</label>
                                <textarea v-model="settings.footerLine1" rows="2" class="w-full bg-slate-50 border-none rounded-lg text-[10px] font-bold text-slate-600 focus:ring-2 focus:ring-indigo-600/20 resize-none"></textarea>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black text-slate-400 uppercase">Baris 2 (Disclaimer)</label>
                                <textarea v-model="settings.footerLine2" rows="2" class="w-full bg-slate-50 border-none rounded-lg text-[10px] font-bold text-slate-600 focus:ring-2 focus:ring-indigo-600/20 resize-none"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Settings -->
                    <div class="space-y-4 pt-4 border-t border-slate-100">
                        <div class="flex items-center justify-between">
                            <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">Informasi Bank</p>
                            <button @click="addBank" class="p-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                                <PhPlus :size="14" weight="bold" />
                            </button>
                        </div>
                        
                        <div v-for="(bank, index) in settings.banks" :key="index" class="p-4 bg-slate-50 rounded-2xl space-y-3 relative group/bank">
                            <button @click="removeBank(index)" class="absolute -top-2 -right-2 w-6 h-6 bg-rose-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover/bank:opacity-100 transition shadow-lg">
                                <PhTrash :size="12" weight="bold" />
                            </button>
                            <div class="space-y-1.5">
                                <label class="text-[8px] font-black text-slate-400 uppercase">Nama Bank & Nomor Rekening</label>
                                <input v-model="bank.number" type="text" placeholder="Bank BCA: 1234-5678-90" class="w-full bg-white border-none rounded-lg text-[10px] font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600/20">
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-[8px] font-black text-slate-400 uppercase">Atas Nama</label>
                                <input v-model="bank.holder" type="text" placeholder="A/N CV. Listrindo Jaya Elektrik" class="w-full bg-white border-none rounded-lg text-[10px] font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600/20">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t border-slate-100 bg-slate-50/50">
                    <button @click="showCustomizer = false" class="w-full bg-[#1E3A5F] text-white py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-900/10 active:scale-95 transition">
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </Transition>

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
                <div class="flex justify-between items-start border-b-[3px] border-[#1E3A5F] pb-4 mb-4">
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
                                <span v-if="settings.showNpwp">NPWP: 01.234.567.8-901.000</span>
                            </p>
                        </div>
                    </div>
                    <div class="text-right flex flex-col items-end gap-2">
                        <p class="text-[11px] font-bold text-slate-800">Jakarta, {{ settings.invoiceDate }}</p>
                        <!-- 8. STATUS BADGE SMALL -->
                        <div :class="['flex items-center gap-1.5 px-3 py-1.5 border-2 rounded-lg text-[10px] font-black tracking-widest uppercase', statusConfig.class]">
                            <component :is="statusConfig.icon" :size="14" weight="bold" />
                            {{ statusConfig.label }}
                        </div>
                    </div>
                </div>

                <!-- DOCUMENT TITLE -->
                <h2 class="text-2xl font-serif font-black text-[#1E3A5F] uppercase text-center tracking-[0.4em] mb-6 border-b border-slate-50 pb-4">INVOICE</h2>

                <!-- 2. HEADER INFO SYMMETRICAL (4x4 Rows) -->
                <div class="grid grid-cols-2 gap-12 mb-6 text-[11px]">
                    <div class="space-y-1.5">
                        <p class="flex"><span class="font-black min-w-[120px] text-slate-400">KEPADA YTH.</span> <span class="mr-2">:</span> <span class="font-black text-slate-800 uppercase">{{ invoice?.delivery_order?.customer?.nama || '-' }}</span></p>
                        <p class="flex"><span class="font-black min-w-[120px] text-slate-400">ALAMAT</span> <span class="mr-2">:</span> <span class="font-bold text-slate-600 leading-tight">{{ invoice?.delivery_order?.customer?.alamat || '-' }}</span></p>
                        <p class="flex"><span class="font-black min-w-[120px] text-slate-400">PO NUMBER</span> <span class="mr-2">:</span> <span class="font-black text-slate-800">{{ invoice?.delivery_order?.po_number || '-' }}</span></p>
                        <p class="flex"><span class="font-black min-w-[120px] text-slate-400">PERIHAL</span> <span class="mr-2">:</span> <span class="font-bold text-slate-600">Penagihan Pembayaran (Invoice)</span></p>
                    </div>
                    <div class="space-y-1.5 ml-auto">
                        <p class="flex"><span class="font-black min-w-[120px] text-slate-400">NO. INVOICE</span> <span class="mr-2">:</span> <span class="font-black text-slate-800">{{ settings.invoiceNumber }}</span></p>
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
                                <th class="border border-white/20 p-2 text-[10px] font-black uppercase w-10 text-center">NO</th>
                                <th class="border border-white/20 p-2 text-[10px] font-black uppercase text-left">DESKRIPSI BARANG / PRODUK</th>
                                <th class="border border-white/20 p-2 text-[10px] font-black uppercase text-center w-20">QTY</th>
                                <th class="border border-white/20 p-2 text-[10px] font-black uppercase text-right w-36">HARGA SATUAN</th>
                                <th class="border border-white/20 p-2 text-[10px] font-black uppercase text-right w-40 pr-4">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody class="text-[11px] text-slate-700">
                            <tr v-for="(item, idx) in invoice?.delivery_order?.items || []" :key="idx" class="border-b border-slate-100 hover:bg-slate-50/50 transition-colors">
                                <td class="border-x border-slate-200 p-1.5 text-center font-bold text-slate-400">{{ idx + 1 }}</td>
                                <td class="border-x border-slate-200 p-1.5 font-black text-[#1E3A5F] uppercase text-[10px]">{{ item.product?.nama || '-' }}</td>
                                <td class="border-x border-slate-200 p-1.5 text-center font-black text-[10px]">{{ item.quantity || 0 }} PCS</td>
                                <td class="border-x border-slate-200 p-1.5 text-right font-bold text-[10px]">{{ formatRupiah(item.harga) }}</td>
                                <td class="border-x border-slate-200 p-1.5 text-right pr-4 font-black text-slate-900 text-[10px]">{{ formatRupiah(item.subtotal) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- BREAKDOWN & BANK INFO -->
                <div class="mt-6 flex justify-between items-start gap-12 no-page-break">
                    <div class="w-1/2">
                        <!-- 1. TERBILANG (MATCH GRAND TOTAL) -->
                        <div class="bg-slate-50 p-4 border-l-4 border-[#1E3A5F] mb-6">
                            <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Terbilang:</p>
                            <p class="text-[11px] font-serif font-black text-[#1E3A5F] italic leading-relaxed">
                                {{ terbilangGrandTotal }}
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-3">
                            <div v-for="(bank, index) in settings.banks" :key="index" class="flex items-center gap-4 p-3 border border-slate-100 rounded-xl bg-white shadow-sm transition-all hover:border-indigo-100">
                                <div class="w-12 h-10 flex items-center justify-center rounded-lg bg-slate-50 overflow-hidden">
                                    <img v-if="bank.logo" :src="bank.logo" class="w-full h-full object-contain p-1" :alt="bank.name">
                                    <PhBank v-else :size="24" weight="duotone" class="text-indigo-600" />
                                </div>
                                <div class="text-[10px]">
                                    <p class="font-black text-[#1E3A5F] tracking-widest uppercase">{{ bank.number }}</p>
                                    <p class="font-bold text-slate-400 uppercase leading-tight">{{ bank.holder }}</p>
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
                            <span class="font-bold text-slate-400 uppercase tracking-tighter">PPN ({{ settings.ppnPercent }}%)</span>
                            <span class="font-black text-slate-800">{{ formatRupiah((invoice?.total || 0) * (settings.ppnPercent / 100)) }}</span>
                        </div>
                        <div class="flex justify-between py-3 bg-[#1E3A5F] text-white px-4 mt-2 shadow-lg shadow-blue-900/10">
                            <span class="font-black uppercase tracking-widest">GRAND TOTAL</span>
                            <span class="font-black text-base">{{ formatRupiah(grandTotal) }}</span>
                        </div>
                    </div>
                </div>

                <!-- 3 & 7. SIGNATURE SECTION (PREVENT PAGE BREAK & NO OVERLAP) -->
                <div class="signature-section mt-auto pt-8 grid grid-cols-2 gap-12 text-center text-[11px]">
                    <div class="space-y-20">
                        <p class="font-black uppercase tracking-widest text-slate-400">Penerima / Customer</p>
                        <div>
                            <div class="border-b-2 border-slate-800 w-40 mx-auto"></div>
                            <p class="text-[8px] font-bold text-slate-800 uppercase mt-2 italic">{{ settings.receiverName || '( Nama Terang & Stempel )' }}</p>
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
                            <p class="text-[8px] font-black text-slate-800 uppercase mt-2 italic tracking-widest">{{ settings.signatoryName }}</p>
                        </div>
                    </div>
                </div>

                <!-- 10. DISCLAIMER & TERMS -->
                <div class="mt-12 pt-6 border-t border-slate-100">
                    <p class="text-[8px] italic text-slate-400 text-center leading-relaxed font-medium uppercase tracking-tighter">
                        {{ settings.footerLine1 }} <br>
                        {{ settings.footerLine2 }}
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
