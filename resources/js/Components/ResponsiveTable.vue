<script setup>
import { PhPackage } from "@phosphor-icons/vue";

defineProps({
    headers: Array,
    items: {
        type: Array,
        default: () => []
    },
    loading: Boolean,
    title: String // Optional title for mobile card view
});
</script>

<template>
    <div class="font-sans">
        <!-- Desktop Table View (Visible from md up) -->
        <div class="hidden md:block bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden transition-all">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 border-b border-slate-200">
                        <tr>
                            <th v-for="header in headers" :key="header" :class="[
                                'px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]',
                                header === 'Total Tagihan' ? 'text-right' : (header === 'Status' ? 'text-center' : 'text-left')
                            ]">
                                {{ header }}
                            </th>
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="items.length === 0" class="italic">
                            <td :colspan="headers.length + 1" class="px-6 py-16 text-center text-slate-400">
                                <div class="flex flex-col items-center gap-2">
                                    <PhPackage :size="32" weight="light" class="text-slate-200" />
                                    <p class="text-sm font-medium">Belum ada data tersedia</p>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="(item, index) in items" :key="index" class="hover:bg-slate-50/80 transition-all group">
                            <slot name="row" :item="item" />
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Desktop Footer -->
            <div class="px-6 py-5 bg-slate-50/50 border-t border-slate-100 flex justify-between items-center">
                <slot name="pagination" />
            </div>
        </div>

        <!-- Mobile Card View (Visible below md) -->
        <div class="md:hidden space-y-4">
            <div v-if="items.length === 0" class="bg-white border border-slate-200 rounded-3xl p-12 text-center text-slate-400 italic">
                <PhPackage :size="40" weight="light" class="mx-auto mb-3 text-slate-200" />
                <p class="text-sm">Tidak ada data ditemukan</p>
            </div>
            
            <div v-for="(item, index) in items" :key="index" 
                 class="bg-white border border-slate-200 rounded-3xl p-5 shadow-sm active:scale-[0.98] transition-all">
                <slot name="mobile-card" :item="item" />
            </div>

            <!-- Mobile Pagination Container -->
            <div v-if="items.length > 0" class="pt-4 flex flex-col gap-4">
                <slot name="pagination" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
