<x-mail::message>
# Peringatan: Stok Produk Menipis!

Sistem WMS mendeteksi bahwa stok produk berikut telah berada di bawah batas minimum yang ditentukan.

### Informasi Produk:
- **Nama Produk:** {{ $product->nama }}
- **Kode Barang:** {{ $product->kode_barang }}
- **Tipe/Varian:** {{ $product->tipe }}
- **Total Stok Saat Ini:** {{ $newGlobalStock }} {{ $product->satuan }}
- **Batas Stok Minimum:** {{ $product->stok_minimum }} {{ $product->satuan }}

Silakan lakukan pemesanan ulang (Restock) ke supplier untuk menghindari kekosongan stok.

<x-mail::button :url="route('products.show', $product->id)">
Lihat Kartu Stok Produk
</x-mail::button>

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
