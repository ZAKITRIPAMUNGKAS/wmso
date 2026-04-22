<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin WMS',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Staff Gudang',
            'email' => 'staff@example.com',
            'password' => bcrypt('password'),
            'role' => 'staff_gudang',
        ]);

        User::factory()->create([
            'name' => 'Viewer User',
            'email' => 'viewer@example.com',
            'password' => bcrypt('password'),
            'role' => 'viewer',
        ]);

        Product::create([
            'kode_barang' => 'BRG-001',
            'nama' => 'Kabel UTP Cat 6',
            'merk' => 'Belden',
            'tipe' => 'Indoor',
            'satuan' => 'Roll',
            'harga' => 1200000,
            'stok_minimum' => 5,
        ]);

        Supplier::create([
            'nama' => 'PT Teknologi Maju',
            'kontak' => '021-1234567',
            'alamat' => 'Jakarta Industrial Estate Pulogadung',
        ]);

        Customer::create([
            'nama' => 'CV Jaya Abadi',
            'alamat' => 'Jl. Sudirman No. 10, Jakarta',
            'kontak' => '0812-9876-5432',
        ]);

        Warehouse::create([
            'kode_gudang' => 'GDG-01',
            'nama' => 'Gudang Utama Jakarta',
            'alamat' => 'Jakarta Timur',
        ]);

        $this->call(CompanySettingsSeeder::class);
    }
}
