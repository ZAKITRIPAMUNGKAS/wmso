<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $product;
    public $newGlobalStock;

    /**
     * Create a new message instance.
     */
    public function __construct(Product $product, int $newGlobalStock)
    {
        $this->product = $product;
        $this->newGlobalStock = $newGlobalStock;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Peringatan: Stok Produk ' . $this->product->nama . ' Menipis!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.low_stock_alert',
        );
    }
}
