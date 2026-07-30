<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';
import { 
    PhPackage, 
    PhBell, 
    PhSignOut,
    PhList,
    PhMagnifyingGlass,
    PhUserCircle
} from "@phosphor-icons/vue";
import { uiStore } from '@/Stores/uiStore';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    title: String,
    isReady: Boolean
});

const page = usePage();
const auth = computed(() => page.props.auth);
const notifications = computed(() => page.props.notifications);

const showNotifications = ref(false);
const showUserMenu = ref(false);

const closeDropdowns = (e) => {
    if (!e.target.closest('.dropdown-trigger')) {
        showNotifications.value = false;
        showUserMenu.value = false;
    }
};

onMounted(() => window.addEventListener('click', closeDropdowns));
onUnmounted(() => window.removeEventListener('click', closeDropdowns));

const logout = () => {
    router.post(route('logout'));
};

const markAsRead = () => {
    router.post(route('notifications.mark-as-read'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            showNotifications.value = false;
        }
    });
};
</script>

<template>
    <header 
        class="h-16 sticky top-0 bg-white/80 backdrop-blur-xl border-b border-slate-200 flex justify-between items-center px-4 md:px-8 shadow-sm z-30 shrink-0 font-sans print:hidden transition-all duration-700 delay-300"
        :class="isReady ? 'translate-y-0 opacity-100' : '-translate-y-full opacity-0'"
    >
        <div class="flex items-center gap-4">
            <!-- Mobile Toggle -->
            <button @click="uiStore.toggleSidebar()" class="lg:hidden p-2 text-slate-600 hover:bg-slate-100 rounded-xl transition-all active:scale-95">
                <PhList :size="24" weight="bold" />
            </button>

            <div class="flex items-center gap-3 animate-fade-down stagger-4">
                <PhPackage :size="24" weight="fill" class="text-indigo-600 lg:hidden" />
                <h1 class="text-lg md:text-xl font-black text-slate-800 tracking-tight truncate max-w-[150px] sm:max-w-none">{{ title }}</h1>
            </div>
        </div>

        <div class="flex-1"></div>

        <div class="flex items-center gap-2 md:gap-4 animate-fade-down stagger-6">
            <!-- Notifications -->
            <div class="relative dropdown-trigger">
                <button @click="showNotifications = !showNotifications" class="relative p-2.5 text-slate-400 hover:bg-slate-100 rounded-xl transition-all active:scale-95" :class="{ 'bg-slate-100 text-indigo-600': showNotifications }">
                    <PhBell :size="20" weight="bold" />
                    <span v-if="notifications?.length > 0" class="absolute top-2.5 right-2.5 w-2.5 h-2.5 bg-rose-500 rounded-full border-2 border-white shadow-sm animate-pulse"></span>
                </button>

                <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                    <div v-if="showNotifications" class="absolute right-0 mt-2 w-80 bg-white border border-slate-100 rounded-3xl shadow-2xl overflow-hidden z-50">
                        <div class="px-6 py-4 border-b border-slate-50 flex justify-between items-center bg-slate-50/30">
                            <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest">Notifikasi</h3>
                            <button v-if="notifications?.length > 0" @click="markAsRead" class="text-[10px] font-black text-indigo-600 uppercase tracking-tighter hover:underline">Tandai Dibaca</button>
                        </div>
                        <div class="max-h-96 overflow-y-auto divide-y divide-slate-50">
                            <div v-for="n in notifications" :key="n.id" class="p-4 hover:bg-slate-50 transition cursor-pointer group">
                                <div class="flex gap-3">
                                    <div :class="['w-2 h-2 mt-1.5 rounded-full shrink-0', n.type === 'warning' ? 'bg-amber-500' : n.type === 'success' ? 'bg-emerald-500' : 'bg-rose-500']"></div>
                                    <div>
                                        <p class="text-xs font-black text-slate-800 tracking-tight group-hover:text-indigo-600 transition">{{ n.title }}</p>
                                        <p class="text-[11px] text-slate-500 font-medium leading-relaxed mt-0.5">{{ n.message }}</p>
                                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest mt-2">{{ n.time }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!notifications || notifications.length === 0" class="p-10 text-center">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Tidak ada notifikasi baru</p>
                            </div>
                        </div>
                        <div v-if="notifications?.length > 0" class="p-4 bg-slate-50/50 text-center border-t border-slate-50">
                            <button class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] hover:text-slate-600 transition">Lihat Semua Aktivitas</button>
                        </div>
                    </div>
                </Transition>
            </div>
            
            <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>

            <!-- User Menu -->
            <div class="relative dropdown-trigger">
                <button @click="showUserMenu = !showUserMenu" class="flex items-center gap-3 p-1.5 pr-3 hover:bg-slate-100 rounded-2xl transition-all active:scale-95" :class="{ 'bg-slate-100': showUserMenu }">
                    <img :src="`https://ui-avatars.com/api/?name=${auth.user.name}&background=025cca&color=fff`" class="w-9 h-9 rounded-xl shadow-md border border-white" loading="lazy" decoding="async">
                    <div class="hidden sm:flex flex-col items-start">
                        <span class="text-xs font-black text-slate-800 leading-tight">{{ auth.user.name }}</span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ auth.user.role?.replace('_', ' ') || 'Admin' }}</span>
                    </div>
                </button>

                <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                    <div v-if="showUserMenu" class="absolute right-0 mt-2 w-56 bg-white border border-slate-100 rounded-2xl shadow-2xl overflow-hidden z-50 p-2">
                        <Link :href="route('profile.edit')" class="flex items-center gap-3 p-3 hover:bg-slate-50 rounded-xl transition group">
                            <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition">
                                <PhUserCircle :size="18" weight="bold" />
                            </div>
                            <span class="text-xs font-black text-slate-700 uppercase tracking-widest">Profil Saya</span>
                        </Link>
                        <button @click="logout" class="w-full flex items-center gap-3 p-3 hover:bg-rose-50 rounded-xl transition group text-left">
                            <div class="p-2 bg-rose-50 rounded-lg text-rose-600 group-hover:bg-rose-600 group-hover:text-white transition">
                                <PhSignOut :size="18" weight="bold" />
                            </div>
                            <span class="text-xs font-black text-rose-700 uppercase tracking-widest">Keluar</span>
                        </button>
                    </div>
                </Transition>
            </div>
        </div>
    </header>
</template>



