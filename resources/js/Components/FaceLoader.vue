<script setup>
import { computed } from 'vue';

const props = defineProps({
    state: {
        type: String,
        default: 'loading', // 'loading' | 'success' | 'error'
    },
    message: {
        type: String,
        default: '',
    }
});

// Color themes based on state
const faceColor = computed(() => {
    switch (props.state) {
        case 'success':
            return 'text-blue-600';
        case 'error':
            return 'text-slate-700';
        default:
            return 'text-blue-600';
    }
});
</script>

<template>
    <div class="flex flex-col items-center justify-center space-y-6">
        
        <!-- Animated Container -->
        <div class="relative w-36 h-36 flex items-center justify-center">
            
            <!-- State 1: Loading (2 Orbiting Circles) -->
            <div v-if="state === 'loading'" class="relative w-28 h-28 flex items-center justify-center animate-spin duration-700">
                <!-- Top Dot -->
                <div class="absolute top-1 left-1/2 -translate-x-1/2 w-6 h-6 bg-blue-600 rounded-full shadow-lg shadow-blue-500/40"></div>
                <!-- Bottom Dot -->
                <div class="absolute bottom-1 left-1/2 -translate-x-1/2 w-6 h-6 bg-blue-600 rounded-full shadow-lg shadow-blue-500/40"></div>
            </div>

            <!-- State 2: Success (Happy Smile :D) -->
            <svg v-else-if="state === 'success'" 
                 viewBox="0 0 120 120" 
                 class="w-32 h-32 text-blue-600 animate-bounce-short transition-all duration-500">
                
                <!-- Eyes (Two Solid Round Dots) -->
                <circle cx="40" cy="40" r="7" fill="currentColor" />
                <circle cx="80" cy="40" r="7" fill="currentColor" />

                <!-- Happy Smile Arc (:D) -->
                <path d="M 30 55 C 30 92, 90 92, 90 55 Z" 
                      fill="currentColor" 
                      stroke="currentColor" 
                      stroke-width="5" 
                      stroke-linejoin="round"
                      stroke-linecap="round"
                      class="animate-smile-pop" />
            </svg>

            <!-- State 3: Error (Sad Frown :() -->
            <svg v-else-if="state === 'error'" 
                 viewBox="0 0 120 120" 
                 class="w-32 h-32 text-slate-700 animate-head-shake transition-all duration-500">
                
                <!-- Eyes (Two Solid Round Dots) -->
                <circle cx="40" cy="45" r="7" fill="currentColor" />
                <circle cx="80" cy="45" r="7" fill="currentColor" />

                <!-- Sad Frown Arc (:( ) -->
                <path d="M 30 90 C 30 62, 90 62, 90 90" 
                      fill="none" 
                      stroke="currentColor" 
                      stroke-width="12" 
                      stroke-linecap="round" 
                      class="animate-frown-pop" />
            </svg>
        </div>

        <!-- Status Label Message -->
        <div v-if="message" class="text-center space-y-2 max-w-xs animate-fade-in">
            <h3 class="text-slate-900 text-xl font-black tracking-tight leading-tight">
                {{ message }}
            </h3>
        </div>

    </div>
</template>

<style scoped>
@keyframes headShake {
    0% { transform: translateX(0); }
    15% { transform: translateX(-10px) rotate(-6deg); }
    30% { transform: translateX(10px) rotate(6deg); }
    45% { transform: translateX(-6px) rotate(-3deg); }
    60% { transform: translateX(6px) rotate(3deg); }
    75% { transform: translateX(-2px) rotate(-1deg); }
    100% { transform: translateX(0); }
}

@keyframes smilePop {
    0% { transform: scale(0.5); opacity: 0; }
    70% { transform: scale(1.1); }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes frownPop {
    0% { transform: scale(0.5); opacity: 0; }
    70% { transform: scale(1.1); }
    100% { transform: scale(1); opacity: 1; }
}

.animate-head-shake {
    animation: headShake 0.6s ease-in-out both;
}

.animate-smile-pop {
    animation: smilePop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

.animate-frown-pop {
    animation: frownPop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}
</style>
