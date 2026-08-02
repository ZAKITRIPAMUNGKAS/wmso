<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import { PhPlus, PhMagnifyingGlass } from "@phosphor-icons/vue";

defineProps({
    title: String,
    activeTab: String,
    search: String,
    addButtonLabel: String
});

defineEmits(['search', 'add']);
</script>

<template>
    <AuthenticatedLayout :title="title">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 mb-8 font-sans">
            <!-- Tabs: Scrollable on mobile -->
            <div class="overflow-x-auto scrollbar-hide -mx-4 px-4 sm:mx-0 sm:px-0">
                <div class="flex items-center gap-1 p-1 bg-slate-200/50 rounded-2xl w-max">
                    <Link 
                        v-for="tab in ['Produk', 'Customer', 'Supplier', 'Gudang', 'Rak']"
                        :key="tab"
                        :href="route(tab === 'Produk' ? 'products.index' : tab === 'Customer' ? 'customers.index' : tab === 'Supplier' ? 'suppliers.index' : tab === 'Gudang' ? 'warehouses.index' : 'racks.index')" 
                        :class="[
                            'px-5 py-2.5 text-sm transition-all whitespace-nowrap min-h-[40px] flex items-center justify-center',
                            activeTab === tab ? 'font-bold text-indigo-600 bg-white shadow-sm rounded-xl' : 'font-bold text-slate-500 hover:text-slate-800 rounded-lg'
                        ]"
                    >
                        {{ tab }}
                    </Link>
                </div>
            </div>

            <!-- Search & Actions -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                <div class="relative group flex-1 sm:w-72">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                        <PhMagnifyingGlass weight="bold" />
                    </span>
                    <input 
                        type="text" 
                        :value="search"
                        @input="$emit('search', $event.target.value)"
                        :placeholder="`Cari ${activeTab.toLowerCase()}...`" 
                        class="w-full pl-12 pr-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm font-bold text-slate-700 outline-none focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all placeholder:text-slate-400"
                    >
                </div>
                <button v-if="addButtonLabel" @click="$emit('add')" class="btn-primary flex-1 sm:flex-none flex items-center justify-center gap-2">
                    <PhPlus weight="bold" /> <span class="tracking-tight">{{ addButtonLabel }}</span>
                </button>
            </div>
        </div>

        <div class="w-full">
            <slot />
        </div>
    </AuthenticatedLayout>
</template>
