<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index()
    {
        $search = request('search');

        $invoices = Invoice::query()
            ->with(['deliveryOrder.customer', 'deliveryOrder.warehouse'])
            ->when($search, function ($query, $search) {
                $query->where('no_invoice', 'like', "%{$search}%")
                      ->orWhereHas('deliveryOrder.customer', function ($q) use ($search) {
                          $q->where('nama', 'like', "%{$search}%");
                      });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => request()->all('search'),
        ]);
    }

    public function show(Invoice $invoice)
    {
        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice->load(['deliveryOrder.customer', 'deliveryOrder.warehouse', 'deliveryOrder.items.product', 'payments'])
        ]);
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['deliveryOrder.customer', 'deliveryOrder.warehouse', 'deliveryOrder.items.product']);
        
        $pdf = Pdf::loadView('pdf.invoice', compact('invoice'));
        
        return $pdf->download("invoice-{$invoice->no_invoice}.pdf");
    }
}
