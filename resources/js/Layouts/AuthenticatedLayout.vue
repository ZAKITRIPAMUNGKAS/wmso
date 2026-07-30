<script setup>
import Sidebar from '@/Components/Sidebar.vue';
import Topbar from '@/Components/Topbar.vue';
import { PhShieldCheck, PhWarning } from "@phosphor-icons/vue";
import { ref, onMounted } from 'vue';

const props = defineProps({
    title: String
});

const isReady = ref(false);

onMounted(() => {
    setTimeout(() => isReady.value = true, 50);
});
</script>

<template>
    <div class="bg-slate-50 flex h-screen overflow-hidden text-slate-800 antialiased font-sans">
        
        <!-- Sidebar Shell -->
        <Sidebar :is-ready="isReady" />

        <!-- Main Content -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
            <Topbar :title="title" :is-ready="isReady" />

            <main class="flex-1 overflow-y-auto overflow-x-hidden p-4 md:p-6 lg:p-8 pb-24 scrollbar-hide">
                <div class="max-w-7xl mx-auto w-full grid grid-cols-1">
                    <Transition name="page" appear>
                        <div :key="$page.url" 
                             class="w-full col-start-1 row-start-1"
                             :class="isReady ? 'animate-fade-up stagger-6' : 'opacity-0'">
                            <!-- Toast Notification -->
                             <div v-if="$page.props.flash.success || $page.props.flash.error" 
                                  class="fixed top-24 right-4 md:right-8 z-[100] animate-in slide-in-from-right duration-500">
                                 <div :class="[$page.props.flash.success ? 'bg-emerald-500 shadow-emerald-100' : 'bg-rose-500 shadow-rose-100']"
                                      class="flex items-center gap-3 px-6 py-4 rounded-2xl shadow-2xl text-white">
                                     <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center">
                                         <component :is="$page.props.flash.success ? PhShieldCheck : PhWarning" :size="16" weight="bold" />
                                     </div>
                                     <p class="text-sm font-black uppercase tracking-widest">{{ $page.props.flash.success || $page.props.flash.error }}</p>
                                 </div>
                             </div>
                            <slot />
                        </div>
                    </Transition>
                </div>
            </main>

            <!-- FIXED FOOTER -->
            <footer class="shrink-0 bg-white/80 backdrop-blur-md border-t border-slate-100 px-8 py-4 flex flex-col md:flex-row justify-between items-center gap-2 text-slate-400 text-[10px] font-black uppercase tracking-[0.15em] z-20 transition-all duration-700"
                    :class="isReady ? 'opacity-100' : 'opacity-0 translate-y-4'">
                <p>© {{ new Date().getFullYear() }} {{ $page.props.company.name }}</p>
                <div class="flex items-center gap-2">
                    <div class="w-1 h-1 bg-indigo-400 rounded-full"></div>
                    <p>Kota Jakarta Selatan, DKI Jakarta</p>
                </div>
            </footer>
        </div>
    </div>
</template>

