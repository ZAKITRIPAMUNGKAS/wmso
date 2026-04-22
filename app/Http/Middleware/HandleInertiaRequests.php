<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Inertia\Inertia;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'company' => [
                'name'        => company('company_name'),
                'short_name'  => company('company_short_name'),
                'tagline'     => company('company_tagline'),
                'address'     => \App\Models\CompanySetting::getAddressFull(),
                'address_short' => company('address_kota') . ', ' . company('address_provinsi'),
                'phone_primary'   => company('phone_primary'),
                'phone_secondary' => company('phone_secondary'),
                'email'       => company('email'),
                'website'     => company('website'),
                'logo'        => company('company_logo'),
            ],
            'notifications' => fn() => 
                $request->user() === null ? [] :
                \Illuminate\Support\Facades\Cache::remember('user_notifications_' . $request->user()->id, 60, function() use ($request) {
                    if (\Illuminate\Support\Facades\Cache::has('notifications_dismissed_at_' . $request->user()->id)) {
                        return [];
                    }
                    return [
                        // 1. Low Stock (Using Scope)
                        ...\App\Models\Product::lowStock(10)
                            ->limit(2)
                            ->get()
                            ->map(fn($p) => [
                                'id' => 'low-stock-'.$p->id,
                                'title' => 'Stok Menipis',
                                'message' => "Produk {$p->nama} sisa " . ($p->total_stock ?? 0) . " unit.",
                                'time' => 'Perlu restok',
                                'type' => 'warning'
                            ]),

                        // 2. Recent Goods Receipts
                        ...\App\Models\GoodsReceipt::latest()->limit(2)->get()->map(fn($gr) => [
                            'id' => 'gr-'.$gr->id,
                            'title' => 'Barang Masuk',
                            'message' => "Penerimaan {$gr->no_receipt} telah diproses.",
                            'time' => $gr->created_at->diffForHumans(),
                            'type' => 'success'
                        ]),

                        // 3. Overdue Invoices
                        ...\App\Models\Invoice::where('status', '!=', 'lunas')
                            ->where('due_date', '<=', now())
                            ->limit(2)->get()->map(fn($inv) => [
                                'id' => 'inv-'.$inv->id,
                                'title' => 'Tagihan Jatuh Tempo',
                                'message' => "Invoice {$inv->no_invoice} telah melewati jatuh tempo.",
                                'time' => \Carbon\Carbon::parse($inv->due_date)->diffForHumans(),
                                'type' => 'error'
                            ]),
                    ];
                }),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
