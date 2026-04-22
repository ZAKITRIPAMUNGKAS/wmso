<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index()
    {
        $search = request('search');

        $invoices = Invoice::query()
            ->with(['deliveryOrder.customer'])
            ->when($search, function ($query, $search) {
                $query->where('no_invoice', 'like', "%{$search}%")
                      ->orWhereHas('deliveryOrder.customer', function ($q) use ($search) {
                          $q->where('nama', 'like', "%{$search}%");
                      });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Payments/Index', [
            'invoices' => $invoices,
            'filters' => request()->all('search'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id'  => 'required|exists:invoices,id',
            'nominal'     => 'required|numeric|min:1',
            'tanggal'     => 'required|date',
            'metode'      => 'required|string',
            'keterangan'  => 'nullable|string',
            'bukti_bayar' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        // Handle file upload
        if ($request->hasFile('bukti_bayar')) {
            $validated['bukti_bayar'] = $request->file('bukti_bayar')
                ->store('bukti-bayar', 'public');
        }

        $this->paymentService->recordPayment($validated);

        return redirect()->back()->with('success', 'Pembayaran berhasil dikonfirmasi!');
    }
}
