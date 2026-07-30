<?php

namespace App\Http\Controllers;

use App\Models\FailedSyncLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;

class SyncLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = FailedSyncLog::latest()->paginate(15);

        return Inertia::render('Settings/SyncLogs', [
            'logs' => $logs
        ]);
    }

    public function retry(FailedSyncLog $log)
    {
        try {
            $log->increment('attempts');
            $log->save();

            return redirect()->back()->with('success', 'Percobaan ulang sinkronisasi berhasil dikirim!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses ulang: ' . $e->getMessage());
        }
    }

    public function syncAll()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('sync:all-stock');
            return redirect()->back()->with('success', 'Proses sinkronisasi stok massal ke Olshop berhasil dijalankan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memicu sinkronisasi stok: ' . $e->getMessage());
        }
    }

    public function destroy(FailedSyncLog $log)
    {
        $log->delete();
        return redirect()->back()->with('success', 'Log sinkronisasi berhasil dihapus.');
    }
}
