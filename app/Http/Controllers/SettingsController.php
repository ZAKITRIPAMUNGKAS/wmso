<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Services\FaviconService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function company()
    {
        return Inertia::render('Settings/Company', [
            'settings' => CompanySetting::all()->groupBy('group'),
        ]);
    }

    public function updateCompany(Request $request)
    {
        $settings = $request->input('settings');

        // Handle file upload for logo separately if present
        if ($request->hasFile('settings.company_logo')) {
            $path = $request->file('settings.company_logo')->store('company', 'public');
            CompanySetting::where('key', 'company_logo')->update(['value' => $path]);
            Cache::forget("company_setting_company_logo");

            // Auto-regenerate all favicon sizes from the new logo
            try {
                app(FaviconService::class)->generateFromStoragePath($path);
            } catch (\Throwable $e) {
                // Non-critical: log but don't block the response
                \Log::warning("Favicon regeneration failed: {$e->getMessage()}");
            }
        }

        foreach ($settings as $key => $value) {
            // Skip logo as it's handled above if it's a file
            if ($key === 'company_logo' && $request->hasFile('settings.company_logo')) {
                continue;
            }
            
            CompanySetting::where('key', $key)->update(['value' => $value]);
            Cache::forget("company_setting_{$key}");
        }

        return redirect()->back()->with('success', 'Pengaturan perusahaan berhasil diperbarui.');
    }
}
