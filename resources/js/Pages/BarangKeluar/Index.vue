<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ResponsiveTable from '@/Components/ResponsiveTable.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { PhPlus, PhTruck, PhCalendar, PhWarehouse, PhUser, PhEye, PhTrash } from "@phosphor-icons/vue";

defineProps({
    deliveryOrders: Object
});

const deleteItem = (id) => {
    if (confirm('PERHATIAN: Menghapus Surat Jalan ini juga akan MENGHAPUS INVOICE terkait dan mengembalikan stok barang ke gudang. Apakah Anda yakin?')) {
        router.delete(route('barang-keluar.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <div>
        <Head title="Barang Keluar" />

        <AuthenticatedLayout title="Barang Keluar">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 font-sans">
                <div>
                    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Pengeluaran Barang</h2>
                    <p class="text-[11px] text-slate-500 font-bold mt-1 uppercase tracking-tight">Surat Jalan & Distribusi Logistik</p>
                </div>
                <Link :href="route('barang-keluar.create')" class="btn-primary flex items-center justify-center gap-2">
                    <PhPlus weight="bold" /> <span class="tracking-tight uppercase font-black text-[10px]">Pengiriman Baru</span>
                </Link>
            </div>

            <ResponsiveTable :headers="['No. SJ', 'Customer', 'Warehouse', 'Tanggal', 'Status']" :items="deliveryOrders.data">
                <template #row="{ item }">
                    <td class="px-8 py-5">
                        <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-100 uppercase tracking-wider uppercase">
                            {{ item.no_sj }}
                        </span>
                    </td>
                    <td class="px-8 py-5 text-sm font-black text-slate-800 tracking-tight uppercase">{{ item.customer?.nama || '-' }}</td>
                    <td class="px-8 py-5 text-sm font-bold text-slate-600 tracking-tight uppercase">{{ item.warehouse?.nama || '-' }}</td>
                    <td class="px-8 py-5 text-sm font-bold text-slate-500 tracking-tight uppercase">{{ new Date(item.tanggal).toLocaleDateString('id-ID') }}</td>
                    <td class="px-8 py-5 uppercase">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[9px] font-black border border-emerald-100 uppercase tracking-tighter uppercase">
                            {{ item.status }}
                        </span>
                    </td>
                    <td class="px-8 py-5 text-right uppercase">
                        <div class="flex justify-end gap-2 uppercase">
                            <Link :href="route('barang-keluar.show', item.id)" class="p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-100 transition shadow-sm active:scale-95 uppercase">
                                <PhEye :size="18" weight="bold" />
                            </Link>
                            <button @click="deleteItem(item.id)" class="p-2 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-100 transition shadow-sm active:scale-95 uppercase">
                                <PhTrash :size="18" weight="bold" />
                            </button>
                        </div>
                    </td>
                </template>

                <template #mobile-card="{ item }">
                    <div class="flex justify-between items-start mb-4 uppercase">
                        <div class="flex items-center gap-3 uppercase">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                                <PhTruck :size="20" weight="fill" />
                            </div>
                            <div>
                                <div class="font-black text-slate-800 tracking-tight uppercase uppercase">{{ item.no_sj }}</div>
                                <div class="text-[10px] font-bold text-amber-600 uppercase tracking-widest uppercase uppercase">{{ item.customer?.nama || '-' }}</div>
                            </div>
                        </div>
                        <div class="flex gap-2 uppercase uppercase">
                            <Link :href="route('barang-keluar.show', item.id)" class="p-2 text-indigo-600 bg-indigo-50 rounded-lg uppercase">
                                <PhEye :size="18" weight="bold" />
                            </Link>
                            <button @click="deleteItem(item.id)" class="p-2 text-rose-600 bg-rose-50 rounded-lg uppercase">
                                <PhTrash :size="18" weight="bold" />
                            </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-50 uppercase uppercase uppercase">
                        <div class="flex items-start gap-2 uppercase uppercase">
                            <PhWarehouse :size="14" class="text-slate-300 mt-0.5" />
                            <p class="text-[10px] font-black text-slate-600 truncate uppercase uppercase uppercase">{{ item.warehouse?.nama }}</p>
                        </div>
                        <div class="flex items-start gap-2 justify-end text-right uppercase uppercase">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[9px] font-black uppercase tracking-tighter uppercase uppercase">
                                {{ item.status }}
                            </span>
                        </div>
                    </div>
                </template>

                <template #pagination>
                    <div class="flex justify-between items-center w-full px-6 py-4 bg-slate-50/50 uppercase uppercase">
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest italic uppercase uppercase uppercase">Logs {{ deliveryOrders.from || 0 }}-{{ deliveryOrders.to || 0 }} of {{ deliveryOrders.total }}</p>
                        <div class="flex gap-1 uppercase uppercase">
                            <template v-for="(link, k) in deliveryOrders.links" :key="k">
                                <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1.5 text-[10px] font-black rounded-lg transition uppercase uppercase uppercase" :class="[link.active ? 'bg-indigo-600 text-white' : 'bg-white text-slate-500 border border-slate-100']" />
                            </template>
                        </div>
                    </div>
                </template>
            </ResponsiveTable>
        </AuthenticatedLayout>
    </div>
</template>
