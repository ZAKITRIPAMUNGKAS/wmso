# Panduan Pengguna (User Manual)
## Sistem Manajemen Gudang (WMS) - CV. Listrindo Jaya Elektrik

Selamat datang di Panduan Pengguna WMS CV. Listrindo Jaya Elektrik. Dokumen ini dirancang untuk membantu Anda memahami fitur-fitur sistem dan cara mengoperasikannya sesuai dengan peran (role) Anda.

---

## 1. Pendahuluan
Sistem WMS ini adalah aplikasi berbasis web yang digunakan untuk mengelola stok barang, transaksi masuk/keluar, penagihan (invoice), dan pelaporan logistik secara real-time.

---

## 2. Sistem Peran (User Roles)
Sistem ini membagi akses menjadi tiga tingkat keamanan:

| Peran | Deskripsi | Hak Akses Utama |
| :--- | :--- | :--- |
| **Admin** | Pengelola Sistem Utama | Akses penuh seluruh fitur, manajemen user, dan pengaturan sistem. |
| **Staff Gudang** | Operator Lapangan | Kelola stok barang, buat surat jalan (SJ), dan penerimaan barang. |
| **Viewer** | Pemantau / Pimpinan | Hanya melihat data dan cetak dokumen (tidak bisa menambah/ubah data). |

---

## 3. Memulai Penggunaan
### 3.1 Login
1. Buka browser dan masukkan alamat URL sistem.
2. Masukkan **Email** dan **Password** Anda.
3. Klik tombol **Masuk Sistem**.

### 3.2 Dashboard
Halaman utama yang menampilkan ringkasan statistik:
- Total stok barang saat ini.
- Grafik pergerakan barang (Masuk vs Keluar) dalam 7 hari terakhir.
- Notifikasi stok rendah (barang yang perlu segera dipesan ulang).

---

## 4. Manajemen Master Data
*(Hanya untuk Admin dan Staff)*

Menu ini digunakan untuk mendata aset dasar perusahaan sebelum melakukan transaksi.
- **Produk**: Tambah/edit data barang, merk, tipe, dan harga jual.
- **Customer**: Daftar pelanggan/tujuan pengiriman barang.
- **Supplier**: Daftar penyedia barang (vendor).
- **Gudang**: Lokasi penyimpanan barang.

---

## 5. Operasional Logistik
### 5.1 Barang Masuk (Penerimaan)
Digunakan saat barang tiba dari supplier ke gudang.
1. Pilih menu **Barang Masuk** > **Penerimaan Baru**.
2. Masukkan nomor Surat Jalan Supplier atau PO.
3. Pilih Gudang tujuan.
4. Input daftar barang dan jumlah yang diterima.
5. Klik **Simpan**. Stok akan bertambah otomatis.

### 5.2 Barang Keluar (Pengiriman)
Digunakan saat mengirim barang ke pelanggan.
1. Pilih menu **Barang Keluar** > **Pengiriman Baru**.
2. Pilih Customer dan Gudang asal barang.
3. Masukkan daftar barang dan jumlahnya.
4. Klik **Simpan**. Stok akan berkurang otomatis dan **Invoice** akan terbuat secara otomatis.

---

## 6. Penagihan & Keuangan
### 6.1 Invoice
- Setiap transaksi **Barang Keluar** akan otomatis menghasilkan Invoice.
- Anda dapat mencetak Invoice dengan berbagai template (termasuk kustomisasi PPN dan informasi bank).
- Status Invoice: `unpaid` (belum lunas) atau `lunas`.

### 6.2 Payment (Hanya Admin)
- Digunakan untuk mencatat pembayaran dari customer.
- Masukkan jumlah bayar dan pilih Invoice yang akan dilunasi.

---

## 7. Laporan (Hanya Admin)
Sistem menyediakan laporan komprehensif yang dapat diakses melalui menu **Laporan**:
- **Ekspor Excel**: Mengunduh data transaksi ke format spreadsheet.
- **Cetak Laporan**: Menghasilkan dokumen PDF untuk arsip fisik.
- Filter laporan dapat disesuaikan berdasarkan rentang tanggal tertentu.

---

## 8. Pengaturan & Keamanan
### 8.1 Manajemen User (Hanya Admin)
- Admin dapat menambah akun baru untuk staff atau viewer.
- Admin dapat mengatur ulang password user yang lupa kredensialnya.

### 8.2 Profil Perusahaan
- Mengubah Nama Perusahaan, Alamat, Nomor Telepon, dan Logo yang akan muncul di Kop Surat/Invoice.

---

## 9. FAQ & Troubleshooting
**Q: Mengapa saya tidak bisa melihat menu Laporan?**
*A: Pastikan peran akun Anda adalah **Admin**. Staff dan Viewer tidak diizinkan mengakses laporan keuangan.*

**Q: Bagaimana jika stok barang di dashboard berwarna merah?**
*A: Itu menandakan stok barang tersebut sudah di bawah ambang batas minimum. Segera lakukan pengadaan (Barang Masuk).*

**Q: Saya salah memasukkan jumlah barang saat transaksi, apa yang harus dilakukan?**
*A: Admin dapat menghapus transaksi tersebut, dan sistem akan mengembalikan saldo stok ke kondisi semula secara otomatis (rollback).*

---
*© 2026 CV. Listrindo Jaya Elektrik - Sistem Manajemen Gudang v2.0*
