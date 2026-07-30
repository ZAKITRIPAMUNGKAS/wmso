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
    PhUserGear,
    PhX,
    PhShoppingCart,
    PhArrowsLeftRight,
    PhClipboardText,
    PhClockCounterClockwise
} from "@phosphor-icons/vue";
import { uiStore } from '@/Stores/uiStore';

const props = defineProps({
    isReady: Boolean
});

const { auth } = usePage().props;

const isActive = (route_name) => {
    return usePage().url.startsWith(route_name);
};

const menuItems = [
    { label: 'Dashboard', route: 'dashboard', icon: PhSquaresFour, active: (r) => r.current('dashboard'), roles: ['admin', 'staff_gudang', 'viewer'] },
    { label: 'Master Data', route: 'products.index', icon: PhDatabase, active: () => isActive('/master-data'), roles: ['admin', 'staff_gudang', 'viewer'] },
    { label: 'Purchase Order', route: 'purchase-orders.index', icon: PhShoppingCart, active: () => isActive('/purchase-orders'), roles: ['admin', 'staff_gudang', 'viewer'] },
    { label: 'Barang Masuk', route: 'barang-masuk.index', icon: PhArrowCircleDown, active: () => isActive('/barang-masuk'), roles: ['admin', 'staff_gudang', 'viewer'] },
    { label: 'Barang Keluar', route: 'barang-keluar.index', icon: PhArrowCircleUp, active: () => isActive('/barang-keluar'), roles: ['admin', 'staff_gudang', 'viewer'] },
    { label: 'Transfer Stok', route: 'stock-transfers.index', icon: PhArrowsLeftRight, active: () => isActive('/stock-transfers'), roles: ['admin', 'staff_gudang', 'viewer'] },
    { label: 'Stock Opname', route: 'stock-adjustments.index', icon: PhClipboardText, active: () => isActive('/stock-adjustments'), roles: ['admin', 'staff_gudang', 'viewer'] },
    { label: 'Kartu Stok', route: 'stock-movements.index', icon: PhClockCounterClockwise, active: () => isActive('/stock-movements'), roles: ['admin', 'staff_gudang', 'viewer'] },
    { label: 'Invoice', route: 'invoices.index', icon: PhReceipt, active: () => isActive('/invoices'), roles: ['admin', 'staff_gudang', 'viewer'] },
    { label: 'Payment', route: 'payments.index', icon: PhWallet, active: (r) => r.current('payments.*'), roles: ['admin'] },
    { label: 'Laporan', route: 'reports.index', icon: PhChartLineUp, active: () => isActive('/laporan'), roles: ['admin'] },
    { label: 'Pengaturan', route: 'settings.company', icon: PhGear, active: () => isActive('/settings'), roles: ['admin'] },
    { label: 'Manajemen User', route: 'users.index', icon: PhUserGear, active: () => isActive('/users'), roles: ['admin'] },
];
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
        'fixed lg:sticky top-0 left-0 z-50 w-72 lg:w-[280px] h-screen bg-slate-900 border-r border-slate-800 flex flex-col transition-all duration-700 ease-in-out shrink-0 overflow-hidden print:hidden',
        uiStore.sidebarOpen ? 'translate-x-0' : (isReady ? 'translate-x-0' : '-translate-x-full lg:translate-x-0 opacity-0')
    ]">
        <div class="h-16 flex items-center justify-between px-6 border-b border-slate-800" :class="isReady ? 'animate-fade-down' : 'opacity-0'">
            <Link :href="route('dashboard')" class="flex items-center group">
                <div v-if="$page.props.company.logo" class="w-9 h-9 rounded-xl overflow-hidden bg-white p-1.5 mr-3 shadow-lg shadow-indigo-500/10 group-hover:scale-105 transition-transform animate-scale-in stagger-2">
                    <img :src="'/storage/' + $page.props.company.logo" class="w-full h-full object-contain" alt="Logo">
                </div>
                <PhPackage v-else :size="24" weight="fill" class="text-indigo-500 mr-3 group-hover:scale-110 transition-transform animate-scale-in stagger-2" />
                <div class="flex flex-col animate-fade-in-left stagger-3">
                    <span class="text-white font-bold text-sm tracking-wide leading-tight truncate max-w-[140px]">{{ $page.props.company.short_name }}</span>
                    <span class="text-[9px] text-slate-500 font-medium uppercase tracking-wider truncate max-w-[140px]">{{ $page.props.company.tagline }}</span>
                </div>
            </Link>
            <button @click="uiStore.closeSidebar()" class="lg:hidden p-2 text-slate-400 hover:text-white">
                <PhX :size="24" weight="bold" />
            </button>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto font-sans scrollbar-hide">
            <p class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2" :class="isReady ? 'animate-fade-up stagger-3' : 'opacity-0'">Menu Utama</p>
            
            <template v-for="(item, index) in menuItems" :key="item.route">
                <Link v-if="item.roles.includes(auth.user.role)" :href="route(item.route)" :class="[
                    'group relative flex items-center gap-3 px-3 py-3 rounded-xl font-medium transition-all duration-300 min-h-[44px] overflow-hidden',
                    item.active(route()) ? 'text-indigo-400' : 'text-slate-400 hover:text-white',
                    isReady ? 'animate-fade-in-left' : 'opacity-0'
                ]" :style="{ animationDelay: `${400 + (index * 50)}ms` }">
                    
                    <!-- Animated Background Layer -->
                    <div :class="[
                        'absolute inset-0 transition-all duration-500 transform origin-left z-0',
                        item.active(route()) ? 'bg-indigo-500/10 scale-x-100 opacity-100' : 'bg-slate-800/50 scale-x-0 opacity-0 group-hover:scale-x-100 group-hover:opacity-100'
                    ]"></div>

                    <!-- Glow effect for active item -->
                    <div v-if="item.active(route())" class="absolute inset-0 bg-indigo-500/5 blur-md animate-pulse"></div>

                    <!-- Content -->
                    <div class="relative z-10 flex items-center gap-3 transition-transform duration-300 group-hover:translate-x-1">
                        <component :is="item.icon" :size="20" :weight="item.active(route()) ? 'fill' : 'regular'" class="transition-colors duration-300" /> 
                        <span>{{ item.label }}</span>
                    </div>
                </Link>
            </template>
        </nav>

        <div class="p-4 border-t border-slate-800" :class="isReady ? 'animate-fade-up stagger-8' : 'opacity-0'">
            <div class="flex items-center justify-between gap-3 px-3 py-3 rounded-2xl bg-slate-800/50 group hover:bg-slate-800 transition-colors duration-300">
                <div class="flex items-center gap-3">
                    <img :src="`https://ui-avatars.com/api/?name=${auth.user.name}&background=025cca&color=fff`" class="w-9 h-9 rounded-full shadow-lg group-hover:scale-110 transition-transform duration-300" loading="lazy" decoding="async">
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

