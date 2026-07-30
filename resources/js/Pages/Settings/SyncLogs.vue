<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ResponsiveTable from '@/Components/ResponsiveTable.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    PhArrowsClockwise, 
    PhWarning, 
    PhTrash, 
    PhEye,
    PhGear,
    PhArrowLeft
} from "@phosphor-icons/vue";

const props = defineProps({
    logs: Object
});

const selectedPayload = ref(null);

const retryLog = (id) => {
    router.post(route('settings.sync-logs.retry', id), {}, {
        preserveScroll: true
    });
};

const deleteLog = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus log sinkronisasi ini?')) {
        router.delete(route('settings.sync-logs.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <div>
        <Head title="Log Sinkronisasi API" />

        <AuthenticatedLayout title="Log Sinkronisasi API">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 font-sans">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <Link :href="route('settings.company')" class="text-xs font-bold text-slate-400 hover:text-indigo-600 uppercase tracking-widest flex items-center gap-1">
                            <PhArrowLeft :size="14" /> Pengaturan
                        </Link>
                    </div>
                    <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">Monitoring Log Sinkronisasi API</h2>
                    <p class="text-xs text-slate-500 font-medium mt-1">Pantau & proses ulang transaksi yang gagal tersinkronisasi dengan Olshop</p>
                </div>
                <div>
                    <Link :href="route('settings.sync-all-stock')" method="post" as="button" class="bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs uppercase tracking-wider py-3 px-5 rounded-xl shadow-md transition flex items-center gap-2">
                        <PhArrowsClockwise :size="16" weight="bold" /> Sinkronkan Seluruh Stok ke Olshop
                    </Link>
                </div>
            </div>

            <!-- Table Section -->
            <ResponsiveTable :headers="['Waktu', 'Tipe Event', 'Pesan Error', 'Percobaan', 'Payload JSON', 'Aksi']" :items="logs.data">
                <template #row="{ item }">
                    <td class="px-6 py-4 text-xs font-bold text-slate-500 whitespace-nowrap">
                        {{ new Date(item.created_at).toLocaleString('id-ID') }}
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-[10px] font-black px-2.5 py-1 rounded-lg border bg-amber-50 text-amber-700 border-amber-200 uppercase tracking-wider">
                            {{ item.type }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-xs font-bold text-rose-600 max-w-xs truncate">
                        {{ item.error_message }}
                    </td>
                    <td class="px-6 py-4 text-xs font-mono font-bold text-slate-700">
                        {{ item.attempts }}x
                    </td>
                    <td class="px-6 py-4">
                        <button @click="selectedPayload = item.payload" class="text-xs font-bold text-indigo-600 hover:underline flex items-center gap-1">
                            <PhEye :size="14" /> View Payload
                        </button>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center gap-2">
                            <button @click="retryLog(item.id)" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs font-extrabold px-3 py-1.5 rounded-lg border border-indigo-200 transition flex items-center gap-1">
                                <PhArrowsClockwise :size="14" weight="bold" /> Retry Sync
                            </button>
                            <button @click="deleteLog(item.id)" class="text-slate-400 hover:text-rose-600 p-1.5 transition">
                                <PhTrash :size="16" />
                            </button>
                        </div>
                    </td>
                </template>
            </ResponsiveTable>

            <!-- Payload Viewer Modal -->
            <div v-if="selectedPayload" class="fixed inset-0 z-[150] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
                <div class="bg-white w-full max-w-2xl rounded-2xl p-6 shadow-2xl space-y-4">
                    <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                        <h3 class="font-black text-slate-900 text-lg uppercase tracking-wide">Payload Data JSON</h3>
                        <button @click="selectedPayload = null" class="text-slate-400 hover:text-slate-600 font-bold text-sm">✕ Close</button>
                    </div>
                    <pre class="bg-slate-950 text-emerald-400 p-4 rounded-xl text-xs font-mono overflow-x-auto max-h-96">{{ JSON.stringify(selectedPayload, null, 2) }}</pre>
                </div>
            </div>
        </AuthenticatedLayout>
    </div>
</template>
