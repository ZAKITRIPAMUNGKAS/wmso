<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ResponsiveTable from '@/Components/ResponsiveTable.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { 
    PhPlus, 
    PhArrowsLeftRight, 
    PhCalendar, 
    PhUser, 
    PhWarehouse, 
    PhEye, 
    PhTrash, 
    PhPencil,
    PhFileText
} from "@phosphor-icons/vue";

defineProps({
    transfers: Object
});

const deleteItem = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus transfer stok ini? Pilihan ini hanya tersedia untuk draf.')) {
        router.delete(route('stock-transfers.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <div>
        <Head title="Transfer Stok" />

        <AuthenticatedLayout title="Transfer Stok">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 font-sans">
                <div>
                    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Mutasi & Transfer Stok</h2>
                    <p class="text-[11px] text-slate-500 font-bold mt-1 uppercase tracking-tight">Perpindahan Barang Antar Gudang</p>
                </div>
                <Link v-if="$page.props.auth.user.role !== 'viewer'" :href="route('stock-transfers.create')" class="btn-primary flex items-center justify-center gap-2">
                    <PhPlus weight="bold" /> 
                    <span class="tracking-tight uppercase font-black text-[10px]">Transfer Baru</span>
                </Link>
            </div>

            <!-- Table Section -->
            <ResponsiveTable :headers="['No. Transfer', 'Gudang Asal', 'Gudang Tujuan', 'Tanggal', 'Pembuat', 'Status']" :items="transfers.data">
                <template #row="{ item }">
                    <td class="px-8 py-5">
                        <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-100 uppercase tracking-wider">
                            {{ item.no_transfer }}
                        </span>
                    </td>
                    <td class="px-8 py-5 text-sm font-bold text-slate-600 tracking-tight uppercase">
                        {{ item.source_warehouse?.nama }}
                    </td>
                    <td class="px-8 py-5 text-sm font-bold text-slate-600 tracking-tight uppercase">
                        {{ item.destination_warehouse?.nama }}
                    </td>
                    <td class="px-8 py-5 text-sm font-bold text-slate-500 tracking-tight">
                        {{ new Date(item.tanggal).toLocaleDateString('id-ID') }}
                    </td>
                    <td class="px-8 py-5 text-sm font-bold text-slate-600 tracking-tight uppercase">
                        {{ item.user?.name }}
                    </td>
                    <td class="px-8 py-5">
                        <span :class="[
                            'px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider border',
                            item.status === 'completed' 
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200' 
                                : 'bg-amber-50 text-amber-700 border-amber-200'
                        ]">
                            {{ item.status === 'completed' ? 'Selesai' : 'Draf' }}
                        </span>
                    </td>
                    <td class="px-8 py-5 text-right">
                        <div class="flex justify-end gap-2">
                            <!-- View Details -->
                            <Link :href="route('stock-transfers.show', item.id)" class="p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-100 transition shadow-sm active:scale-95">
                                <PhEye :size="18" weight="bold" />
                            </Link>
                            
                            <!-- Edit (Draft status only and not viewer) -->
                            <Link v-if="$page.props.auth.user.role !== 'viewer' && item.status === 'draft'" :href="route('stock-transfers.edit', item.id)" class="p-2 bg-amber-50 text-amber-600 rounded-xl hover:bg-amber-100 transition shadow-sm active:scale-95">
                                <PhPencil :size="18" weight="bold" />
                            </Link>

                            <!-- Delete (Draft status only and not viewer) -->
                            <button v-if="$page.props.auth.user.role !== 'viewer' && item.status === 'draft'" @click="deleteItem(item.id)" class="p-2 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-100 transition shadow-sm active:scale-95">
                                <PhTrash :size="18" weight="bold" />
                            </button>
                        </div>
                    </td>
                </template>

                <!-- Mobile Card Layout -->
                <template #mobile-card="{ item }">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                <PhArrowsLeftRight :size="20" weight="fill" />
                            </div>
                            <div>
                                <div class="font-black text-slate-800 tracking-tight uppercase">{{ item.no_transfer }}</div>
                                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                                    {{ new Date(item.tanggal).toLocaleDateString('id-ID') }}
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <span :class="[
                                'px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-tighter border',
                                item.status === 'completed' 
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-100' 
                                    : 'bg-amber-50 text-amber-700 border-amber-100'
                            ]">
                                {{ item.status === 'completed' ? 'Selesai' : 'Draf' }}
                            </span>
                            <div class="flex gap-1.5 mt-1">
                                <Link :href="route('stock-transfers.show', item.id)" class="p-2 text-indigo-600 bg-indigo-50 rounded-lg">
                                    <PhEye :size="16" weight="bold" />
                                </Link>
                                <Link v-if="$page.props.auth.user.role !== 'viewer' && item.status === 'draft'" :href="route('stock-transfers.edit', item.id)" class="p-2 text-amber-600 bg-amber-50 rounded-lg">
                                    <PhPencil :size="16" weight="bold" />
                                </Link>
                                <button v-if="$page.props.auth.user.role !== 'viewer' && item.status === 'draft'" @click="deleteItem(item.id)" class="p-2 text-rose-600 bg-rose-50 rounded-lg">
                                    <PhTrash :size="16" weight="bold" />
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Route visualization on Mobile -->
                    <div class="border-t border-slate-50 pt-4 mt-2 space-y-2">
                        <div class="flex items-center justify-between text-[11px] font-black text-slate-600 tracking-tight">
                            <div class="flex items-center gap-1.5">
                                <PhWarehouse :size="12" class="text-slate-400" />
                                <span class="uppercase">{{ item.source_warehouse?.nama }}</span>
                            </div>
                            <span class="text-slate-400">&rarr;</span>
                            <div class="flex items-center gap-1.5">
                                <PhWarehouse :size="12" class="text-slate-400" />
                                <span class="uppercase">{{ item.destination_warehouse?.nama }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 text-[10px] text-slate-400 font-bold uppercase mt-1">
                            <PhUser :size="12" />
                            <span>{{ item.user?.name }}</span>
                        </div>
                    </div>
                </template>

                <!-- Pagination Slot -->
                <template #pagination>
                    <div class="flex justify-between items-center w-full px-6 py-4 bg-slate-50/50 uppercase">
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest italic">
                            Data {{ transfers.from || 0 }}-{{ transfers.to || 0 }} dari {{ transfers.total }}
                        </p>
                        <div class="flex gap-1">
                            <template v-for="(link, k) in transfers.links" :key="k">
                                <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-3 py-1.5 text-[10px] font-black rounded-lg transition" :class="[link.active ? 'bg-indigo-600 text-white' : 'bg-white text-slate-500 border border-slate-100']" />
                            </template>
                        </div>
                    </div>
                </template>
            </ResponsiveTable>
        </AuthenticatedLayout>
    </div>
</template>
