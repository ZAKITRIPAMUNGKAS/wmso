<script setup>
import { onMounted, ref } from 'vue';
import FaceLoader from '@/Components/FaceLoader.vue';
import { PhPackage } from "@phosphor-icons/vue";

const props = defineProps({
    processing: Boolean,
    authStatus: {
        type: String,
        default: 'loading'
    },
    authMessage: {
        type: String,
        default: ''
    }
});

const isLoaded = ref(false);
const showInitialLoading = ref(true);

onMounted(() => {
    // Initial page load sequence
    setTimeout(() => {
        showInitialLoading.value = false;
        setTimeout(() => {
            isLoaded.value = true;
        }, 150);
    }, 900);
});
</script>

<template>
    <div class="font-sans min-h-screen flex items-center justify-center p-4 md:p-8 relative overflow-hidden bg-slate-900">
        
        <!-- Fullscreen Background Image with Soft Blur Overlay matching Reference Image -->
        <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1920&q=80')] bg-cover bg-center filter blur-md scale-105 opacity-40"></div>
        <div class="absolute inset-0 bg-slate-900/30 backdrop-blur-sm"></div>

        <!-- Initial Page Loading Screen Overlay -->
        <Transition
            enter-active-class="transition duration-500 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-700 ease-in-out"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showInitialLoading" class="fixed inset-0 z-[100] bg-white flex flex-col items-center justify-center">
                <FaceLoader state="loading" message="Mempersiapkan Sistem WMS..." />
            </div>
        </Transition>

        <!-- Authenticating / Login Process Overlay (Loading -> Smile / Frown) -->
        <Transition
            enter-active-class="transition duration-400 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-500 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="processing || authStatus === 'error' || authStatus === 'success'" 
                 class="fixed inset-0 z-[110] bg-white/95 backdrop-blur-md flex flex-col items-center justify-center text-slate-900 overflow-hidden p-6">
                
                <FaceLoader :state="authStatus || 'loading'" :message="authMessage || 'Memvalidasi Akses...'" />
                
            </div>
        </Transition>
        
        <!-- Master Card Container (Exact 1:1 Layout & Appearance matching Reference) -->
        <div 
            id="auth-card"
            class="w-full max-w-5xl bg-white p-5 md:p-6 rounded-[2.5rem] shadow-[0_35px_80px_rgba(0,0,0,0.25)] border border-white/20 relative z-10 flex flex-col lg:flex-row gap-8 transition-all duration-700 overflow-hidden"
            :class="isLoaded ? 'animate-fade-up' : 'opacity-0'"
        >
            <!-- Left Hero Visual Panel (Exact match with Green Valley Residence Left Card) -->
            <div class="w-full lg:w-[46%] hidden lg:flex relative rounded-[2.2rem] overflow-hidden p-9 flex-col justify-between min-h-[540px] text-white bg-slate-950 shadow-inner shrink-0 group">
                <!-- Hero Background Photo with Dark Gradient -->
                <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1000&q=80')] bg-cover bg-center transition-transform duration-1000 group-hover:scale-105"></div>
                <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-black/40 to-black/90"></div>

                <!-- Top Header Content -->
                <div class="relative z-10 flex justify-between items-start">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-200">WELCOME TO</p>
                        <h3 class="text-2xl font-black tracking-tight mt-1 text-white leading-tight">WMS Gateway</h3>
                    </div>

                    <!-- Logo Badge Top Right (Official Listrindo Logo) -->
                    <div class="w-14 h-14 bg-white/10 backdrop-blur-md rounded-2xl border-2 border-white/30 flex items-center justify-center p-2 shadow-xl">
                        <img :src="$page.props.company?.logo ? '/storage/' + $page.props.company.logo : '/favicon.jpg'" alt="Logo Listrindo" class="w-full h-full object-contain rounded-lg">
                    </div>
                </div>

                <!-- Bottom Text Banner -->
                <div class="relative z-10 space-y-2">
                    <h2 class="text-3xl xl:text-4xl font-black text-white leading-tight tracking-tight">
                        {{ $page.props.company?.name || 'CV. Listrindo Jaya' }}
                    </h2>
                    <p class="text-xs text-slate-300 font-normal leading-relaxed opacity-90">
                        {{ $page.props.company?.tagline || 'Solusi Manajemen Stok, Gudang, & Operasional Logistik Terintegrasi.' }}
                    </p>
                </div>
            </div>

            <!-- Right Form Panel -->
            <div class="flex-1 flex flex-col justify-center px-4 py-6 md:px-10 md:py-8">
                <slot />
            </div>
        </div>
    </div>
</template>
