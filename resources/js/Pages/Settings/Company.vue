<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { 
    PhGear, 
    PhBuildings, 
    PhMapPinLine, 
    PhPhone, 
    PhEnvelope, 
    PhGlobe, 
    PhDeviceMobile, 
    PhCheckCircle,
    PhArrowLeft
} from "@phosphor-icons/vue";
import { ref } from 'vue';

const props = defineProps({
    settings: Object,
});

// Flatten settings for form
const initialData = {};
Object.values(props.settings).forEach(group => {
    group.forEach(s => {
        initialData[s.key] = s.value;
    });
});

const form = useForm({
    settings: initialData
});

const submit = () => {
    form.post(route('settings.company.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            // Toast or notification logic
        }
    });
};

const groups = [
    { id: 'general', label: 'Informasi Umum', icon: PhBuildings },
    { id: 'address', label: 'Alamat Kantor', icon: PhMapPinLine },
    { id: 'contact', label: 'Kontak & Media', icon: PhDeviceMobile },
];
</script>

<template>
    <AuthenticatedLayout title="Pengaturan Perusahaan">
        <Head title="Pengaturan Perusahaan" />
        <div class="max-w-4xl mx-auto font-sans">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-8">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-white rounded-2xl shadow-sm border border-slate-200 shrink-0">
                        <PhGear :size="24" weight="fill" class="text-indigo-600" />
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight">Profil Perusahaan</h1>
                        <p class="text-slate-500 text-[11px] md:text-xs font-bold uppercase tracking-widest mt-0.5">Identitas Resmi Dokumen & Aplikasi</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6 md:space-y-8 pb-24 md:pb-20">
                <div v-for="group in groups" :key="group.id" class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden">
                    <div class="px-6 md:px-8 py-5 md:py-6 border-b border-slate-50 bg-slate-50/30 flex items-center gap-3">
                        <component :is="group.icon" :size="20" weight="bold" class="text-indigo-600" />
                        <h2 class="font-black text-slate-800 text-sm uppercase tracking-widest">{{ group.label }}</h2>
                    </div>

                    <div class="p-6 md:p-8 grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">
                        <div v-for="setting in settings[group.id]" :key="setting.id" :class="setting.type === 'textarea' ? 'md:col-span-2' : ''">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">
                                {{ setting.label }}
                            </label>
                            
                            <input v-if="setting.type === 'text' || setting.type === 'email' || setting.type === 'url'"
                                   v-model="form.settings[setting.key]"
                                   :type="setting.type"
                                   class="input-base font-bold text-slate-700">
                            
                            <textarea v-else-if="setting.type === 'textarea'"
                                      v-model="form.settings[setting.key]"
                                      rows="3"
                                      class="input-base font-bold text-slate-700 py-4 min-h-[100px] resize-none"></textarea>

                            <div v-else-if="setting.type === 'file'" class="flex flex-col gap-4">
                                <div v-if="form.settings[setting.key] && typeof form.settings[setting.key] === 'string'" class="w-32 h-32 rounded-2xl border-2 border-slate-100 overflow-hidden bg-slate-50 relative group">
                                    <img :src="'/storage/' + form.settings[setting.key]" class="w-full h-full object-contain">
                                    <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <p class="text-[8px] font-black text-white uppercase tracking-widest">Logo Saat Ini</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <input type="file" 
                                           @input="form.settings[setting.key] = $event.target.files[0]"
                                           class="file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 text-slate-400 text-xs">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Floating Action Bar -->
                <div class="fixed bottom-6 left-6 right-6 z-50 md:sticky md:left-0 md:right-0">
                    <div class="bg-white/80 backdrop-blur-xl border border-slate-200 shadow-2xl rounded-2xl md:rounded-[2rem] p-3 md:p-4 flex flex-col md:flex-row justify-between items-center gap-3 md:gap-4 px-6 md:px-8">
                        <div class="hidden md:flex items-center gap-3 text-slate-500 text-[10px] font-black uppercase tracking-[0.2em]">
                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse shadow-lg shadow-emerald-500/50"></div>
                            <span>Sinkronisasi Otomatis</span>
                        </div>
                        <div class="flex items-center gap-2 md:gap-3 w-full md:w-auto">
                            <button type="button" @click="form.reset()" class="flex-1 md:flex-none px-6 py-3 rounded-xl md:rounded-2xl text-slate-400 hover:text-slate-600 font-black text-xs uppercase tracking-widest transition active:scale-95">
                                Reset
                            </button>
                            <button type="submit" 
                                    :disabled="form.processing"
                                    class="flex-1 md:flex-none bg-indigo-600 hover:bg-indigo-700 text-white px-8 md:px-10 py-3 md:py-3.5 rounded-xl md:rounded-2xl font-black text-xs md:text-sm uppercase tracking-widest transition shadow-xl shadow-indigo-500/25 flex items-center justify-center gap-2 active:scale-95 disabled:opacity-50">
                                <PhCheckCircle v-if="!form.processing" :size="20" weight="bold" />
                                <span>{{ form.processing ? 'Menyimpan...' : 'Update Profil' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
