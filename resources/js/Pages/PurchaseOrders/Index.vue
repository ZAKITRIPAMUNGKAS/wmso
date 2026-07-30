<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ResponsiveTable from '@/Components/ResponsiveTable.vue';
import { router } from '@inertiajs/vue3';
import { 
    PhPlus, 
    PhEye, 
    PhPencilSimple, 
    PhTrash, 
    PhShoppingCart, 
    PhCalendar, 
    PhUser, 
    PhClock, 
    PhCheckCircle, 
    PhReceipt 
} from "@phosphor-icons/vue";

defineProps({
    purchaseOrders: Object
});

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'draft':
            return 'text-slate-600 bg-slate-100 border-slate-200';
        case 'confirmed':
            return 'text-indigo-600 bg-indigo-50 border-indigo-100';
        case 'received':
            return 'text-emerald-600 bg-emerald-50 border-emerald-100';
        default:
            return 'text-slate-600 bg-slate-100 border-slate-200';
    }
};

const getStatusLabel = (status) => {
    switch (status) {
        case 'draft':
            return 'Draft';
        case 'confirmed':
            return 'Confirmed';
        case 'received':
            return 'Received';
        default:
            return status;
    }
};

const deleteItem = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus Purchase Order ini?')) {
        router.delete(route('purchase-orders.destroy', id), {
            preserveScroll: true,
            onSuccess: () => {
                // Flash message automatically handled by Inertia/Laravel session
            }
        });
    }
};
</script>

<template>
    <div>
        <Head title="Purchase Order" />

        <AuthenticatedLayout title="Purchase Order">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 font-sans">
                <div>
                    <h2 class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Purchase Order</h2>
                    <p class="text-[11px] text-slate-500 font-bold mt-1 uppercase tracking-tight">Manajemen Pembelian & Logistik Masuk</p>
                </div>
                <Link v-if="$page.props.auth.user.role !== 'viewer'" :href="route('purchase-orders.create')" class="btn-primary flex items-center justify-center gap-2">
                    <PhPlus weight="bold" /> 
                    <span class="tracking-tight uppercase font-black text-[10px]">Buat Purchase Order</span>
                </Link>
            </div>

            <ResponsiveTable 
                :headers="['No. PO', 'Supplier', 'Tanggal', 'Status', 'Total Tagihan', 'Dibuat Oleh']" 
                :items="purchaseOrders.data"
            >
                <template #row="{ item }">
                    <!-- No. PO -->
                    <td class="px-6 py-5">
                        <span class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg border border-indigo-100 uppercase tracking-wider">
                            {{ item.no_po }}
                        </span>
                    </td>
                    
                    <!-- Supplier -->
                    <td class="px-6 py-5 text-sm font-black text-slate-800 tracking-tight uppercase">
                        {{ item.supplier?.nama }}
                    </td>
                    
                    <!-- Tanggal -->
                    <td class="px-6 py-5 text-sm font-bold text-slate-500 tracking-tight uppercase">
                        {{ new Date(item.tanggal).toLocaleDateString('id-ID') }}
                    </td>
                    
                    <!-- Status -->
                    <td class="px-6 py-5 text-center">
                        <span :class="getStatusBadgeClass(item.status)" class="text-[9px] font-black px-2.5 py-1 rounded-lg border uppercase tracking-wider">
                            {{ getStatusLabel(item.status) }}
                        </span>
                    </td>
                    
                    <!-- Total Tagihan -->
                    <td class="px-6 py-5 text-sm font-black text-slate-800 text-right tracking-tight">
                        Rp {{ parseFloat(item.total).toLocaleString('id-ID') }}
                    </td>
                    
                    <!-- Dibuat Oleh -->
                    <td class="px-6 py-5 text-sm font-bold text-slate-600 tracking-tight uppercase">
                        {{ item.user?.name }}
                    </td>
                    
                    <!-- Action -->
                    <td class="px-6 py-5 text-right">
                        <div class="flex justify-end gap-2">
                            <!-- View -->
                            <Link :href="route('purchase-orders.show', item.id)" class="p-2 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-100 transition shadow-sm active:scale-95">
                                <PhEye :size="18" weight="bold" />
                            </Link>
                            
                            <!-- Edit (only if draft or confirmed and not viewer) -->
                            <Link 
                                v-if="$page.props.auth.user.role !== 'viewer' && item.status !== 'received'" 
                                :href="route('purchase-orders.edit', item.id)" 
                                class="p-2 bg-amber-50 text-amber-600 rounded-xl hover:bg-amber-100 transition shadow-sm active:scale-95"
                            >
                                <PhPencilSimple :size="18" weight="bold" />
                            </Link>
                            
                            <!-- Delete (only if draft or confirmed and not viewer) -->
                            <button 
                                v-if="$page.props.auth.user.role !== 'viewer' && item.status !== 'received'" 
                                @click="deleteItem(item.id)" 
                                class="p-2 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-100 transition shadow-sm active:scale-95"
                            >
                                <PhTrash :size="18" weight="bold" />
                            </button>
                        </div>
                    </td>
                </template>

                <template #mobile-card="{ item }">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                                <PhShoppingCart :size="20" weight="fill" />
                            </div>
                            <div>
                                <div class="font-black text-slate-800 tracking-tight uppercase">{{ item.no_po }}</div>
                                <div class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ item.supplier?.nama }}</div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <!-- View -->
                            <Link :href="route('purchase-orders.show', item.id)" class="p-2 text-indigo-600 bg-indigo-50 rounded-lg">
                                <PhEye :size="18" weight="bold" />
                            </Link>
                            
                            <!-- Edit -->
                            <Link 
                                v-if="$page.props.auth.user.role !== 'viewer' && item.status !== 'received'" 
                                :href="route('purchase-orders.edit', item.id)" 
                                class="p-2 text-amber-600 bg-amber-50 rounded-lg"
                            >
                                <PhPencilSimple :size="18" weight="bold" />
                            </Link>
                            
                            <!-- Delete -->
                            <button 
                                v-if="$page.props.auth.user.role !== 'viewer' && item.status !== 'received'" 
                                @click="deleteItem(item.id)" 
                                class="p-2 text-rose-600 bg-rose-50 rounded-lg"
                            >
                                <PhTrash :size="18" weight="bold" />
                            </button>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-50 uppercase">
                        <div class="flex items-center gap-2">
                            <PhCalendar :size="14" class="text-slate-300" />
                            <p class="text-[10px] font-black text-slate-500 uppercase">
                                {{ new Date(item.tanggal).toLocaleDateString('id-ID') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2 justify-end text-right">
                            <span :class="getStatusBadgeClass(item.status)" class="text-[8px] font-black px-2 py-0.5 rounded border uppercase tracking-wider">
                                {{ getStatusLabel(item.status) }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center mt-3 pt-3 border-t border-slate-50 font-bold">
                        <div class="flex items-center gap-2 text-slate-400">
                            <PhUser :size="14" />
                            <span class="text-[10px] uppercase">{{ item.user?.name }}</span>
                        </div>
                        <div class="text-xs font-black text-indigo-600">
                            Rp {{ parseFloat(item.total).toLocaleString('id-ID') }}
                        </div>
                    </div>
                </template>

                <template #pagination>
                    <div class="flex justify-between items-center w-full px-6 py-4 bg-slate-50/50 uppercase">
                        <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest italic">
                            Logs {{ purchaseOrders.from || 0 }}-{{ purchaseOrders.to || 0 }} of {{ purchaseOrders.total }}
                        </p>
                        <div class="flex gap-1">
                            <template v-for="(link, k) in purchaseOrders.links" :key="k">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    v-html="link.label" 
                                    class="px-3 py-1.5 text-[10px] font-black rounded-lg transition uppercase" 
                                    :class="[link.active ? 'bg-indigo-600 text-white' : 'bg-white text-slate-500 border border-slate-100']" 
                                />
                            </template>
                        </div>
                    </div>
                </template>
            </ResponsiveTable>
        </AuthenticatedLayout>
    </div>
</template>
