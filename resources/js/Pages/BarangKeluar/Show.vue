<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { PhArrowLeft, PhPrinter } from "@phosphor-icons/vue";

const props = defineProps({
    deliveryOrder: Object,
});

const totalItems = computed(() => props.deliveryOrder.items.length);
const totalQty = computed(() => {
    return props.deliveryOrder.items.reduce((acc, item) => acc + (parseInt(item.quantity) || 0), 0);
});

// Helper untuk format nomor dokumen resmi Indonesia: 001/SJ/LJE/IV/2026
const getOfficialNumber = computed(() => {
    const date = new Date(props.deliveryOrder.tanggal);
    const romanMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
    const id = String(props.deliveryOrder.id).padStart(3, '0');
    const month = romanMonths[date.getMonth()];
    const year = date.getFullYear();
    return `${id}/SJ/LJE/${month}/${year}`;
});

const printPage = () => { window.print(); };

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', { 
        day: 'numeric', month: 'long', year: 'numeric' 
    });
};

const currentTime = new Date().toLocaleString('id-ID', { 
    day: '2-digit', month: '2-digit', year: 'numeric', 
    hour: '2-digit', minute: '2-digit' 
}).replace(',', '');
</script>

<template>
    <Head :title="deliveryOrder.no_sj" />

    <AuthenticatedLayout :title="'Dokumen ' + deliveryOrder.no_sj">
        <!-- UI Actions (Hidden on Print) -->
        <div class="mb-6 flex items-center justify-between no-print font-sans">
            <Link :href="route('barang-keluar.index')" class="text-[11px] font-bold text-slate-500 hover:text-[#1E3A5F] flex items-center gap-2 uppercase tracking-wider transition">
                <PhArrowLeft weight="bold" /> Kembali ke Index
            </Link>
            <button @click="printPage" class="bg-[#1E3A5F] text-white px-6 py-2 text-[11px] font-bold uppercase tracking-widest hover:bg-[#162a45] transition shadow-sm active:scale-95">
                <PhPrinter weight="fill" class="inline mr-2" /> Cetak Surat Jalan
            </button>
        </div>

        <!-- OFFICIAL CORPORATE DOCUMENT CONTAINER -->
        <div class="print-container bg-white border border-[#D1D5DB] shadow-sm relative font-business text-[#1F2937] leading-tight mx-auto overflow-hidden">
            
            <!-- LEMBAR INDIKATOR -->
            <div class="absolute top-4 right-8 text-[8px] italic font-black text-slate-300 uppercase tracking-[0.2em] print-only">
                Lembar 1 - ASLI (Untuk Penerima)
            </div>

            <!-- 1. KOP SURAT PERUSAHAAN -->
            <div class="p-6 pb-4 border-b-[3px] border-double border-[#1F2937]">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-6">
                        <div v-if="$page.props.company.logo" class="w-16 h-16 bg-white border border-slate-100 flex items-center justify-center p-1.5 shadow-lg shadow-blue-900/5">
                            <img :src="'/storage/' + $page.props.company.logo" class="w-full h-full object-contain" alt="Company Logo">
                        </div>
                        <div v-else class="w-16 h-16 bg-[#1E3A5F] flex items-center justify-center text-white font-serif font-black text-2xl shadow-lg shadow-blue-900/10">
                            LJE
                        </div>
                        
                        <div>
                            <h1 class="text-[16px] font-serif font-black uppercase tracking-tight mb-1 text-[#1E3A5F]">
                                {{ $page.props.company.name }}
                            </h1>
                            <p class="text-[9px] font-sans font-medium text-[#4B5563] max-w-md leading-[1.3]">
                                Jl. Tebet Raya No. 11G, Tebet Barat, Jakarta Selatan 12810<br>
                                Telp: {{ $page.props.company.phone_primary }} | Email: {{ $page.props.company.email || 'info@listrindojaya.co.id' }}<br>
                                NPWP: 01.234.567.8-901.000
                            </p>
                        </div>
                    </div>
                    
                    <div class="text-right border border-[#1F2937] p-3 min-w-[200px]">
                        <h2 class="text-[13px] font-serif font-black uppercase tracking-widest mb-1 border-b border-[#D1D5DB] pb-1">
                            SURAT JALAN
                        </h2>
                        <p class="text-[10px] font-sans font-bold tracking-tight text-[#4B5563]">
                            No: {{ getOfficialNumber }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <!-- 2. PERIHAL & INFO CUSTOMER -->
                <div class="mb-6 flex justify-between items-start text-[10px]">
                    <div class="space-y-1">
                        <p><span class="font-black w-24 inline-block">KEPADA YTH.</span> : {{ deliveryOrder.customer.nama.toUpperCase() }}</p>
                        <p><span class="font-black w-24 inline-block">ALAMAT</span> : {{ deliveryOrder.customer.alamat.toUpperCase() }}</p>
                        <p><span class="font-black w-24 inline-block">PO NUMBER</span> : {{ deliveryOrder.po_number || '-' }}</p>
                        <p><span class="font-black w-24 inline-block">PERIHAL</span> : Pengiriman Barang (DO)</p>
                    </div>
                    <div class="text-right space-y-1">
                        <p><span class="font-black italic tracking-tighter">Jakarta,</span> {{ formatDate(deliveryOrder.tanggal) }}</p>
                        <div class="inline-block border-2 border-double border-[#1E3A5F] px-3 py-1 mt-2">
                            <span class="text-[10px] font-serif font-black text-[#1E3A5F] uppercase tracking-[0.2em]">{{ deliveryOrder.status.toUpperCase() }}</span>
                        </div>
                    </div>
                </div>

                <div class="border-t border-[#D1D5DB] pt-4 mb-4">
                    <p class="text-[10px] italic font-serif text-[#4B5563] mb-4">
                        Mohon diterima dengan baik barang-barang tersebut di bawah ini yang kami kirimkan dengan kendaraan operasional kami:
                    </p>
                </div>

                <!-- 3. TABEL BARANG (NO width: 40px) -->
                <table class="w-full border-collapse border border-[#1F2937] mb-6 font-sans">
                    <thead>
                        <tr class="bg-[#1E3A5F] text-white">
                            <th class="border border-[#1F2937] p-2 text-[10px] font-serif font-black uppercase w-[40px]">NO</th>
                            <th class="border border-[#1F2937] p-2 text-[10px] font-serif font-black uppercase text-left w-[40%]">NAMA BARANG / DESKRIPSI</th>
                            <th class="border border-[#1F2937] p-2 text-[10px] font-serif font-black uppercase text-center w-[15%]">QTY</th>
                            <th class="border border-[#1F2937] p-2 text-[10px] font-serif font-black uppercase text-right w-[20%]">HARGA</th>
                            <th class="border border-[#1F2937] p-2 text-[10px] font-serif font-black uppercase text-right w-[20%] pr-4">SUBTOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in deliveryOrder.items" :key="item.id" class="page-break-avoid h-10">
                            <td class="border border-[#1F2937] p-2 text-[11px] text-center font-bold">{{ index + 1 }}</td>
                            <td class="border border-[#1F2937] p-2 text-[11px] font-black tracking-tight">{{ item.product.nama.toUpperCase() }}</td>
                            <td class="border border-[#1F2937] p-2 text-[11px] font-black text-center">{{ item.quantity }} PCS</td>
                            <td class="border border-[#1F2937] p-2 text-[11px] text-right font-bold uppercase">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.harga) }}</td>
                            <td class="border border-[#1F2937] p-2 text-[11px] text-right font-black pr-4">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(item.subtotal) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50">
                            <td colspan="3" class="border border-[#1F2937] p-2 text-[10px] font-serif font-black uppercase text-right italic text-[#6B7280]">Total Quantity: {{ totalQty }} Pcs</td>
                            <td class="border border-[#1F2937] p-2 text-[10px] font-serif font-black uppercase text-right">GRAND TOTAL</td>
                            <td class="border border-[#1F2937] p-2 text-[11px] font-black text-right pr-4 bg-[#EEF2FF]">{{ new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(deliveryOrder.total) }}</td>
                        </tr>
                    </tfoot>
                </table>

                <!-- 4. CATATAN & INFO LOGISTIK -->
                <div class="mb-10 font-sans">
                    <div class="w-full">
                        <div class="border border-[#D1D5DB] p-4 min-h-[60px] relative">
                            <p class="absolute -top-2 left-3 bg-white px-2 text-[9px] font-black uppercase tracking-widest text-[#4B5563]">Keterangan Pengiriman</p>
                            <p class="text-[10px] leading-relaxed italic text-slate-600">
                                {{ deliveryOrder.keterangan || 'Barang telah diperiksa dan diserahkan dalam kondisi baik. Harap periksa kembali kesesuaian barang saat diterima.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 5. AREA TANDA TANGAN (Indonesian Operational Style) -->
                <div class="flex justify-between items-start font-serif mb-12 px-2">
                    <div class="text-center w-[160px]">
                        <p class="text-[10px] font-black mb-16 uppercase italic">Diterima Oleh,</p>
                        <div class="border-b-[1.5px] border-[#1F2937] w-full"></div>
                        <p class="text-[8px] font-sans font-bold text-slate-400 uppercase mt-1">( Tanda Tangan & Stempel )</p>
                    </div>
                    
                    <div class="text-center w-[200px]">
                        <p class="text-[10px] font-black mb-16 uppercase">Sopir / Kurir,</p>
                        <div class="border-b-[1.5px] border-[#1F2937] w-full"></div>
                        <div class="mt-2 text-[8px] font-sans font-bold text-left space-y-1 text-slate-400 uppercase tracking-tighter">
                            <p>No. Pol : ________________</p>
                            <p>Jam : ________________</p>
                        </div>
                    </div>

                    <div class="text-center w-[180px] relative">
                        <p class="text-[10px] font-black mb-16 uppercase">Hormat Kami,</p>
                        
                        <!-- Stempel Placeholder -->
                        <div class="absolute top-6 left-1/2 -translate-x-1/2 w-16 h-16 border border-dashed border-slate-200 flex items-center justify-center opacity-40">
                            <p class="text-[7px] font-sans font-black text-slate-300 uppercase leading-tight text-center">Stempel<br>Perusahaan</p>
                        </div>

                        <div class="border-b-[1.5px] border-[#1F2937] w-full"></div>
                        <p class="text-[10px] font-black uppercase mt-1">{{ deliveryOrder.user.name.toUpperCase() }}</p>
                        <p class="text-[8px] font-sans font-bold text-slate-400 uppercase tracking-widest">WAREHOUSE DEPT.</p>
                    </div>
                </div>
            </div>

            <!-- 6. LEGAL FOOTER -->
            <div class="p-4 pt-2 border-t-[3px] border-double border-[#1F2937] bg-slate-50/50 print-footer">
                <p class="text-center text-[8px] italic font-serif text-[#6B7280] mb-2 leading-none">
                    "Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan tanpa perjanjian terlebih dahulu. Surat Jalan ini merupakan dokumen resmi CV. Listrindo Jaya Elektrik."
                </p>
                <div class="flex justify-between items-center text-[8px] font-sans font-black text-[#9CA3AF] uppercase tracking-widest border-t border-slate-200 pt-2 px-4">
                    <span>SJ-ID: {{ getOfficialNumber }}</span>
                    <span>HALAMAN 1 DARI 1</span>
                    <span>PRINTED: {{ currentTime }}</span>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700;900&display=swap');
.font-business { font-family: 'Arial', 'Helvetica', sans-serif; }
.font-serif { font-family: 'Merriweather', 'Georgia', serif; }
.print-container { max-width: 842px; margin: 0 auto; border-radius: 0 !important; }

@media print {
    @page { size: A4 portrait; margin: 10mm; }
    .print-container { max-width: 100% !important; margin: 0 !important; border: none !important; box-shadow: none !important; }
    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    body { background: white !important; color: #1F2937 !important; }
    .no-print, nav, header, button, .sidebar, .topbar { display: none !important; }
    table, th, td { border: 1px solid #1F2937 !important; }
    .bg-[#1E3A5F] { background-color: #1E3A5F !important; color: white !important; }
    .print-footer { position: fixed; bottom: 0; left: 0; right: 0; background: transparent !important; }
}
</style>
