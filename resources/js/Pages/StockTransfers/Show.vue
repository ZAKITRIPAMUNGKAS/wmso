<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed, ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { PhArrowLeft, PhPrinter, PhGear, PhX, PhCheckCircle } from "@phosphor-icons/vue";

const props = defineProps({
    transfer: Object,
});

const getOfficialNumber = computed(() => {
    return props.transfer?.no_transfer || '-';
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    const months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
};

const totalItems = computed(() => props.transfer?.items?.length || 0);
const totalQty = computed(() => {
    return props.transfer?.items?.reduce((acc, item) => acc + (parseInt(item.quantity) || 0), 0);
});

const printPage = () => {
    window.print();
};

const confirmTransfer = () => {
    if (confirm('Apakah Anda yakin ingin menyelesaikan transfer stok ini? Tindakan ini akan memperbarui persediaan di gudang asal dan tujuan secara real-time.')) {
        router.put(route('stock-transfers.update', props.transfer.id), {
            source_warehouse_id: props.transfer.source_warehouse_id,
            destination_warehouse_id: props.transfer.destination_warehouse_id,
            tanggal: props.transfer.tanggal ? props.transfer.tanggal.substring(0, 10) : '',
            status: 'completed',
            catatan: props.transfer.catatan,
            items: props.transfer.items.map(i => ({
                product_id: i.product_id,
                quantity: i.quantity
            }))
        });
    }
};

const currentTime = new Date().toLocaleString('id-ID', { 
    day: '2-digit', month: '2-digit', year: 'numeric', 
    hour: '2-digit', minute: '2-digit' 
}).replace(',', '');

const showCustomizer = ref(false);

const settings = reactive({
    docNumber: getOfficialNumber.value,
    docDate: formatDate(props.transfer.tanggal),
    sourceWarehouse: props.transfer.source_warehouse?.nama.toUpperCase(),
    destinationWarehouse: props.transfer.destination_warehouse?.nama.toUpperCase(),
    senderSignatory: '', // Diserahkan Oleh
    receiverSignatory: '', // Diterima Oleh
    managerSignatory: props.transfer.user.name.toUpperCase(), // Mengetahui
    catatan: props.transfer.catatan || 'Barang telah dipindahkan antar gudang dan diterima dalam kondisi baik. Selisih kuantitas atau kerusakan setelah dokumen ini disahkan adalah tanggung jawab penuh masing-masing kepala gudang.'
});
</script>

<template>
    <div>
        <Head :title="transfer.no_transfer" />

        <AuthenticatedLayout :title="'Dokumen ' + transfer.no_transfer">
            <!-- UI Actions (Hidden on Print) -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 no-print font-sans">
                <Link :href="route('stock-transfers.index')" class="text-[11px] font-bold text-slate-500 hover:text-[#1E3A5F] flex items-center gap-2 uppercase tracking-wider transition">
                    <PhArrowLeft weight="bold" /> Kembali ke Index
                </Link>
                <div class="flex flex-wrap items-center gap-3">
                    <button v-if="$page.props.auth.user.role !== 'viewer' && transfer.status === 'draft'" @click="confirmTransfer" class="bg-emerald-600 text-white px-6 py-2 text-[11px] font-bold uppercase tracking-widest hover:bg-emerald-700 transition shadow-sm active:scale-95 flex items-center gap-2">
                        <PhCheckCircle weight="bold" :size="16" /> Konfirmasi Transfer
                    </button>
                    <button @click="showCustomizer = true" class="bg-white border border-slate-200 text-slate-700 px-6 py-2 text-[11px] font-bold uppercase tracking-widest hover:border-indigo-600 transition shadow-sm active:scale-95">
                        <PhGear weight="bold" class="inline mr-2" /> Kustomisasi
                    </button>
                    <button @click="printPage" class="bg-[#1E3A5F] text-white px-6 py-2 text-[11px] font-bold uppercase tracking-widest hover:bg-[#162a45] transition shadow-sm active:scale-95">
                        <PhPrinter weight="fill" class="inline mr-2" /> Cetak Dokumen Resmi
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
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Kustomisasi Dokumen</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Edit tampilan dokumen</p>
                        </div>
                        <button @click="showCustomizer = false" class="p-2 hover:bg-white rounded-lg text-slate-400 hover:text-rose-500 transition shadow-sm">
                            <PhX :size="20" weight="bold" />
                        </button>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-6 space-y-8 scrollbar-hide font-sans">
                        <!-- General Settings -->
                        <div class="space-y-4">
                            <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">Informasi Dokumen</p>
                            
                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black text-slate-400 uppercase">Nomor Transfer (TRF)</label>
                                <input v-model="settings.docNumber" type="text" class="w-full bg-slate-50 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600/20">
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black text-slate-400 uppercase">Tanggal</label>
                                <input v-model="settings.docDate" type="text" class="w-full bg-slate-50 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600/20">
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black text-slate-400 uppercase">Gudang Asal</label>
                                <input v-model="settings.sourceWarehouse" type="text" class="w-full bg-slate-50 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600/20">
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-[9px] font-black text-slate-400 uppercase">Gudang Tujuan</label>
                                <input v-model="settings.destinationWarehouse" type="text" class="w-full bg-slate-50 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600/20">
                            </div>
                        </div>

                        <!-- Signatory Settings -->
                        <div class="space-y-4 pt-4 border-t border-slate-100">
                            <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">Penanda Tangan</p>
                            <div class="space-y-3">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-black text-slate-400 uppercase">Diserahkan Oleh (Kiri)</label>
                                    <input v-model="settings.senderSignatory" type="text" placeholder="Gudang Asal / Driver" class="w-full bg-slate-50 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600/20">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-black text-slate-400 uppercase">Diterima Oleh (Tengah)</label>
                                    <input v-model="settings.receiverSignatory" type="text" placeholder="Penerima / Gudang Tujuan" class="w-full bg-slate-50 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600/20">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] font-black text-slate-400 uppercase">Mengetahui (Kanan)</label>
                                    <input v-model="settings.managerSignatory" type="text" class="w-full bg-slate-50 border-none rounded-lg text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-600/20">
                                </div>
                            </div>
                        </div>

                        <!-- Catatan -->
                        <div class="space-y-4 pt-4 border-t border-slate-100">
                            <p class="text-[10px] font-black text-indigo-600 uppercase tracking-widest">Catatan Tambahan</p>
                            <textarea v-model="settings.catatan" rows="4" class="w-full bg-slate-50 border-none rounded-lg text-[10px] font-bold text-slate-600 focus:ring-2 focus:ring-indigo-600/20 resize-none"></textarea>
                        </div>
                    </div>

                    <div class="p-6 border-t border-slate-100 bg-slate-50/50">
                        <button @click="showCustomizer = false" class="w-full bg-[#1E3A5F] text-white py-3 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-900/10 active:scale-95 transition">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- OFFICIAL CORPORATE DOCUMENT CONTAINER (TRF) -->
            <div class="print-container bg-white border border-[#D1D5DB] shadow-sm relative font-business text-[#1F2937] leading-tight mx-auto overflow-hidden">
                
                <!-- INDIKATOR LEMBAR -->
                <div class="absolute top-4 right-8 text-[8px] italic font-black text-slate-300 uppercase tracking-[0.2em] print-only">
                    Lembar 1 - ASLI (Arsip Warehouse)
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
                                TRANSFER STOK
                            </h2>
                            <p class="text-[10px] font-sans font-bold tracking-tight text-[#4B5563]">
                                No: {{ settings.docNumber }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-8">
                    <!-- 2. PERIHAL & INFO UTAMA -->
                    <div class="mb-6 flex justify-between items-start text-[10px] font-sans">
                        <div class="space-y-1">
                            <p><span class="font-black w-28 inline-block">PERIHAL</span> : Perpindahan Barang Antar Gudang</p>
                            <p><span class="font-black w-28 inline-block">GUDANG ASAL</span> : {{ settings.sourceWarehouse }}</p>
                            <p><span class="font-black w-28 inline-block">GUDANG TUJUAN</span> : {{ settings.destinationWarehouse }}</p>
                            <p><span class="font-black w-28 inline-block">OPERATOR</span> : {{ transfer.user.name.toUpperCase() }}</p>
                        </div>
                        <div class="text-right space-y-1">
                            <p><span class="font-black italic tracking-tighter">Jakarta,</span> {{ settings.docDate }}</p>
                            <div class="inline-block border-2 border-double px-3 py-1 mt-2 transform -rotate-2" :class="[transfer.status === 'completed' ? 'border-[#166534]' : 'border-amber-600']">
                                <span class="text-[11px] font-serif font-black uppercase tracking-[0.2em]" :class="[transfer.status === 'completed' ? 'text-[#166534]' : 'text-amber-600']">
                                    {{ transfer.status === 'completed' ? 'MUTASI SELESAI' : 'MUTASI DRAF' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[#D1D5DB] pt-4 mb-4">
                        <p class="text-[10px] italic font-serif text-[#4B5563] mb-4">
                            Telah disiapkan mutasi persediaan barang dengan rincian unit produk dan kuantitas berikut ini:
                        </p>
                    </div>

                    <!-- 3. TABEL BARANG -->
                    <table class="w-full border-collapse border border-[#1F2937] mb-6 font-sans">
                        <thead>
                            <tr class="bg-[#1E3A5F] text-white">
                                <th class="border border-[#1F2937] p-2 text-[10px] font-serif font-black uppercase w-[40px]">NO</th>
                                <th class="border border-[#1F2937] p-2 text-[10px] font-serif font-black uppercase text-left w-[55%]">NAMA PRODUK / SPESIFIKASI</th>
                                <th class="border border-[#1F2937] p-2 text-[10px] font-serif font-black uppercase text-center w-[15%]">QTY</th>
                                <th class="border border-[#1F2937] p-2 text-[10px] font-serif font-black uppercase text-center w-[15%]">SATUAN</th>
                                <th class="border border-[#1F2937] p-2 text-[10px] font-serif font-black uppercase text-center w-[10%]">KET</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, index) in transfer.items" :key="item.id" class="page-break-avoid h-10">
                                <td class="border border-[#1F2937] p-2 text-[11px] text-center font-bold">{{ index + 1 }}</td>
                                <td class="border border-[#1F2937] p-2 text-[11px] font-black tracking-tight">{{ item.product.nama.toUpperCase() }}</td>
                                <td class="border border-[#1F2937] p-2 text-[11px] font-black text-center">{{ item.quantity }}</td>
                                <td class="border border-[#1F2937] p-2 text-[11px] text-center uppercase font-bold">{{ item.product.satuan || 'PCS' }}</td>
                                <td class="border border-[#1F2937] p-2 text-[10px] text-center font-bold">✓</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50">
                                <td colspan="2" class="border border-[#1F2937] p-2 text-[10px] font-serif font-black uppercase text-right">TOTAL TRANSFER</td>
                                <td class="border border-[#1F2937] p-2 text-[11px] font-black text-center">{{ totalQty }}</td>
                                <td class="border border-[#1F2937] p-2 text-[10px] text-center font-black uppercase">{{ totalItems }} SKU</td>
                                <td class="border border-[#1F2937] p-2"></td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- 4. CATATAN & QR CODE -->
                    <div class="grid grid-cols-12 gap-8 mb-12 font-sans">
                        <div class="col-span-8">
                            <div class="border border-[#D1D5DB] p-4 min-h-[60px] relative">
                                <p class="absolute -top-2 left-3 bg-white px-2 text-[9px] font-black uppercase tracking-widest text-[#4B5563]">Keterangan Tambahan / Catatan</p>
                                <p class="text-[10px] leading-relaxed italic text-slate-600">
                                    {{ settings.catatan }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-span-4 flex flex-col items-center justify-center border border-[#D1D5DB] bg-slate-50/50 p-3">
                            <div class="bg-white p-1 border border-[#D1D5DB]">
                                <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=55x55&data=${$page.url}`" class="w-[55px] h-[55px]" />
                            </div>
                            <p class="text-[7px] font-sans font-black uppercase mt-2 tracking-widest text-slate-500 text-center">Verifikasi Perpindahan Digital</p>
                        </div>
                    </div>

                    <!-- 5. AREA TANDA TANGAN -->
                    <div class="flex justify-between items-start font-serif mb-12 px-10">
                        <div class="text-center w-[200px]">
                            <p class="text-[11px] font-black mb-16 uppercase">Diserahkan Oleh,</p>
                            <div class="border-b-[1.5px] border-[#1F2937] w-full"></div>
                            <p class="text-[10px] font-black uppercase mt-1 italic tracking-widest">{{ settings.senderSignatory || '( ............................ )' }}</p>
                            <p class="text-[9px] font-sans font-bold text-slate-400 uppercase">Kepala Gudang Asal</p>
                        </div>
                        
                        <div class="text-center w-[200px]">
                            <p class="text-[11px] font-black mb-16 uppercase italic">Diterima Oleh,</p>
                            <div class="border-b-[1.5px] border-[#1F2937] w-full"></div>
                            <p class="text-[10px] font-black uppercase mt-1 italic tracking-widest">{{ settings.receiverSignatory || '( ............................ )' }}</p>
                            <p class="text-[9px] font-sans font-bold text-slate-400 uppercase tracking-widest">Kepala Gudang Tujuan</p>
                        </div>

                        <div class="text-center w-[200px]">
                            <p class="text-[11px] font-black mb-16 uppercase">Mengetahui / Otomatisasi,</p>
                            <div class="border-b-[1.5px] border-[#1F2937] w-full"></div>
                            <p class="text-[10px] font-black uppercase mt-1 italic tracking-widest">{{ settings.managerSignatory }}</p>
                            <p class="text-[9px] font-sans font-bold text-slate-400 uppercase">Admin / Penanggung Jawab</p>
                        </div>
                    </div>
                </div>

                <!-- 6. LEGAL FOOTER -->
                <div class="p-4 pt-2 border-t-[3px] border-double border-[#1F2937] bg-slate-50/50 print-footer">
                    <p class="text-center text-[8px] italic font-serif text-[#6B7280] mb-2 leading-none">
                        "Dokumen ini merupakan bukti sah mutasi barang internal yang diterbitkan secara otomatis oleh sistem WMS CV. Listrindo Jaya Elektrik."
                    </p>
                    <div class="flex justify-between items-center text-[8px] font-sans font-black text-[#9CA3AF] uppercase tracking-widest border-t border-slate-200 pt-2 px-4">
                        <span>TRF-ID: {{ settings.docNumber }}</span>
                        <span>HALAMAN 1 DARI 1</span>
                        <span>TGL CETAK: {{ currentTime }}</span>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    </div>
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
