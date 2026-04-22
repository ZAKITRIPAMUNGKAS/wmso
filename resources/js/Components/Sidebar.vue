<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { 
    PhPackage, 
    PhSquaresFour, 
    PhDatabase, 
    PhArrowCircleDown, 
    PhArrowCircleUp, 
    PhReceipt, 
    PhWallet, 
    PhChartLineUp, 
    PhSignOut,
    PhGear,
    PhX 
} from "@phosphor-icons/vue";
import { uiStore } from '@/Stores/uiStore';

const { auth } = usePage().props;

const isActive = (route_name) => {
    return usePage().url.startsWith(route_name);
};
</script>

<template>
    <!-- Mobile Backdrop -->
    <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="uiStore.sidebarOpen" 
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 lg:hidden"
             @click="uiStore.closeSidebar()"></div>
    </Transition>

    <aside :class="[
        'fixed lg:sticky top-0 left-0 z-50 w-72 lg:w-[280px] h-screen bg-slate-900 border-r border-slate-800 flex flex-col transition-transform duration-300 ease-in-out shrink-0 overflow-hidden print:hidden',
        uiStore.sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
    ]">
        <div class="h-16 flex items-center justify-between px-6 border-b border-slate-800">
            <Link :href="route('dashboard')" class="flex items-center group">
                <div v-if="$page.props.company.logo" class="w-9 h-9 rounded-xl overflow-hidden bg-white p-1.5 mr-3 shadow-lg shadow-indigo-500/10 group-hover:scale-105 transition-transform">
                    <img :src="'/storage/' + $page.props.company.logo" class="w-full h-full object-contain" alt="Logo">
                </div>
                <PhPackage v-else :size="24" weight="fill" class="text-indigo-500 mr-3 group-hover:scale-110 transition-transform" />
                <div class="flex flex-col">
                    <span class="text-white font-bold text-sm tracking-wide leading-tight truncate max-w-[140px]">{{ $page.props.company.short_name }}</span>
                    <span class="text-[9px] text-slate-500 font-medium uppercase tracking-wider truncate max-w-[140px]">{{ $page.props.company.tagline }}</span>
                </div>
            </Link>
            <button @click="uiStore.closeSidebar()" class="lg:hidden p-2 text-slate-400 hover:text-white">
                <PhX :size="24" weight="bold" />
            </button>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto font-sans scrollbar-hide">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Menu Utama</p>
            
            <Link :href="route('dashboard')" :class="[
                'flex items-center gap-3 px-3 py-3 rounded-xl font-medium transition-colors min-h-[44px]',
                route().current('dashboard') ? 'bg-indigo-500/10 text-indigo-400 shadow-[inset_0_0_20px_rgba(79,70,229,0.05)]' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'
            ]">
                <PhSquaresFour :size="20" :weight="route().current('dashboard') ? 'fill' : 'regular'" /> Dashboard
            </Link>

            <Link :href="route('products.index')" :class="[
                'flex items-center gap-3 px-3 py-3 rounded-xl font-medium transition-colors min-h-[44px]',
                isActive('/master-data') ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'
            ]">
                <PhDatabase :size="20" :weight="isActive('/master-data') ? 'fill' : 'regular'" /> Master Data
            </Link>

            <Link :href="route('barang-masuk.index')" :class="[
                'flex items-center gap-3 px-3 py-3 rounded-xl font-medium transition-colors min-h-[44px]',
                isActive('/barang-masuk') ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'
            ]">
                <PhArrowCircleDown :size="20" :weight="isActive('/barang-masuk') ? 'fill' : 'regular'" /> Barang Masuk
            </Link>

            <Link :href="route('barang-keluar.index')" :class="[
                'flex items-center gap-3 px-3 py-3 rounded-xl font-medium transition-colors min-h-[44px]',
                isActive('/barang-keluar') ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'
            ]">
                <PhArrowCircleUp :size="20" :weight="isActive('/barang-keluar') ? 'fill' : 'regular'" /> Barang Keluar
            </Link>

            <Link :href="route('invoices.index')" :class="[
                'flex items-center gap-3 px-3 py-3 rounded-xl font-medium transition-colors min-h-[44px]',
                isActive('/invoices') ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'
            ]">
                <PhReceipt :size="20" :weight="isActive('/invoices') ? 'fill' : 'regular'" /> Invoice
            </Link>

            <Link :href="route('payments.index')" :class="[
                'flex items-center gap-3 px-3 py-3 rounded-xl font-medium transition-colors min-h-[44px]',
                route().current('payments.*') ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'
            ]">
                <PhWallet :size="20" :weight="route().current('payments.*') ? 'fill' : 'regular'" /> Payment
            </Link>

            <Link :href="route('reports.index')" :class="[
                'flex items-center gap-3 px-3 py-3 rounded-xl font-medium transition-colors min-h-[44px]',
                isActive('/laporan') ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'
            ]">
                <PhChartLineUp :size="20" :weight="isActive('/laporan') ? 'fill' : 'regular'" /> Laporan
            </Link>

            <Link :href="route('settings.company')" :class="[
                'flex items-center gap-3 px-3 py-3 rounded-xl font-medium transition-colors min-h-[44px]',
                isActive('/settings') ? 'bg-indigo-500/10 text-indigo-400' : 'text-slate-400 hover:text-white hover:bg-slate-800/50'
            ]">
                <PhGear :size="20" :weight="isActive('/settings') ? 'fill' : 'regular'" /> Pengaturan
            </Link>
        </nav>

        <div class="p-4 border-t border-slate-800">
            <div class="flex items-center justify-between gap-3 px-3 py-3 rounded-2xl bg-slate-800/50">
                <div class="flex items-center gap-3">
                    <img :src="`https://ui-avatars.com/api/?name=${auth.user.name}&background=4f46e5&color=fff`" class="w-9 h-9 rounded-full shadow-lg" loading="lazy" decoding="async">
                    <div class="truncate">
                        <p class="text-sm font-bold text-white truncate">{{ auth.user.name }}</p>
                        <p class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">{{ auth.user.role?.replace('_', ' ') }}</p>
                    </div>
                </div>
                <Link :href="route('logout')" method="post" as="button" class="p-2.5 text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 rounded-xl transition-all active:scale-95" title="Logout">
                    <PhSignOut :size="20" weight="bold" />
                </Link>
            </div>
        </div>
    </aside>
</template>
