<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ResponsiveTable from '@/Components/ResponsiveTable.vue';
import { router } from '@inertiajs/vue3';
import { PhPlus, PhArrowCircleDown, PhCalendar, PhUser, PhWarehouse, PhEye, PhTrash } from "@phosphor-icons/vue";

defineProps({
    receipts: Object
});

const deleteItem = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus data ini? Stok akan dikurangi kembali secara otomatis (kebalikan dari saat barang masuk).')) {
        router.delete(route('barang-masuk.destroy', id), {
            preserveScroll: true,
            onSuccess: () => {
                // Notifikasi sukses (jika ada sistem flash message)
            }
        });
    }
};
</script>

<template>
    <div>
        <Head title="Barang Masuk" />

        <AuthenticatedLayout title="Barang Masuk">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 font-sans">
                <div>
                    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Penerimaan Barang</h2>
                    <p class="text-[11px] text-slate-500 font-bold mt-1 uppercase tracking-tight">Logistik Masuk & Purchase Order</p>
                </div>
                <Link :href="route('barang-masuk.create')" class="btn-primary flex items-center justify-center gap-2">
                    <PhPlus weight="bold" /> <span class="tracking-tight uppercase font-black text-[10px]">Penerimaan Baru</span>
                </Link>
            </div>

            <ResponsiveTable :headers="['No. Receipt', 'No. PO', 'Warehouse', 'Tanggal', 'Penerima']" :items="receipts.data">
                <template #row="{ item }">
                    <td class="px-8 py-5">
                        <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-100 uppercase tracking-wider uppercase">
                            {{ item.no_receipt }}
                        </span>
                    </td>
                    <td class="px-8 py-5 text-sm font-black text-slate-800 tracking-tight uppercase">{{ item.purchase_order?.no_po || '-' }}</td>
                    <td class="px-8 py-5 text-sm font-bold text-slate-600 tracking-tight uppercase">{{ item.warehouse?.nama }}</td>
                    <td class="px-8 py-5 text-sm font-bold text-slate-500 tracking-tight uppercase">{{ new Date(item.tanggal).toLocaleDateString('id-ID') }}</td>
                    <td class="px-8 py-5 text-sm font-bold text-slate-600 tracking-tight uppercase">{{ item.user?.name }}</td>
                    <td class="px-8 py-5 text-right">
                        <div class="flex justify-end gap-2">
                            <Link :href="route('barang-masuk.show', item.id)" class="p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-100 transition shadow-sm active:scale-95">
                                <PhEye :size="18" weight="bold" />
                            </Link>
                            <button @click="deleteItem(item.id)" class="p-2 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-100 transition shadow-sm active:scale-95">
                                <PhTrash :size="18" weight="bold" />
                            </button>
                        </div>
                    </td>
                </template>

                <template #mobile-card="{ item }">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                <PhArrowCircleDown :size="20" weight="fill" />
                            </div>
                            <div>
                                <div class="font-black text-slate-800 tracking-tight uppercase">{{ item.no_receipt }}</div>
                                <div class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest uppercase">PO: {{ item.purchase_order?.no_po || '-' }}</div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Link :href="route('barang-masuk.show', item.id)" class="p-2 text-indigo-600 bg-indigo-50 rounded-lg">
                                <PhEye :size="18" weight="bold" />
                            </Link>
                            <button @click="deleteItem(item.id)" class="p-2 text-rose-600 bg-rose-50 rounded-lg">
                                <PhTrash :size="18" weight="bold" />
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-50 uppercase">
                        <div class="flex items-start gap-2">
                            <PhWarehouse :size="14" class="text-slate-300 mt-0.5" />
                            <p class="text-[10px] font-black text-slate-600 truncate uppercase">{{ item.warehouse?.nama }}</p>
                        </div>
                        <div class="flex items-start gap-2 justify-end text-right">
                            <PhCalendar :size="14" class="text-slate-300 mt-0.5" />
                            <p class="text-[10px] font-black text-slate-500 uppercase">{{ new Date(item.tanggal).toLocaleDateString('id-ID') }}</p>
                        </div>
                    </div>
                </template>

                <template #pagination>
                    <div class="flex justify-between items-center w-full px-6 py-4 bg-slate-50/50 uppercase">
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest italic uppercase">Logs {{ receipts.from || 0 }}-{{ receipts.to || 0 }} of {{ receipts.total }}</p>
                        <div class="flex gap-1">
                            <template v-for="(link, k) in receipts.links" :key="k">
                                <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1.5 text-[10px] font-black rounded-lg transition uppercase" :class="[link.active ? 'bg-indigo-600 text-white' : 'bg-white text-slate-500 border border-slate-100']" />
                            </template>
                        </div>
                    </div>
                </template>
            </ResponsiveTable>
        </AuthenticatedLayout>
    </div>
</template>
