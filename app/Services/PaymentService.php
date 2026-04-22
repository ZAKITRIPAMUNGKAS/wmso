<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PaymentService
{
    public function recordPayment(array $data)
    {
        return DB::transaction(function () use ($data) {
            $payment = Payment::create([
                'invoice_id'  => $data['invoice_id'],
                'tanggal'     => $data['tanggal'],
                'nominal'     => $data['nominal'],
                'metode'      => $data['metode'],
                'keterangan'  => $data['keterangan'] ?? null,
                'bukti_bayar' => $data['bukti_bayar'] ?? null,
                'user_id'     => Auth::id()
            ]);

            $invoice = Invoice::lockForUpdate()->find($data['invoice_id']);
            $paidAmount = Payment::where('invoice_id', $invoice->id)->sum('nominal');
            
            $invoice->paid_amount = $paidAmount;
            
            if ($paidAmount >= $invoice->total) {
                $invoice->status = 'lunas';
            } elseif ($paidAmount > 0) {
                $invoice->status = 'partial';
            } else {
                $invoice->status = 'belum_lunas';
            }

            $invoice->save();

            return $payment;
        });
    }
}
