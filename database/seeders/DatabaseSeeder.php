<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\ProductStock;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create Roles if Spatie is used
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'staff_gudang']);
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'viewer']);
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@wms.com'],
            [
                'name' => 'Admin WMS',
                'password' => bcrypt('admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
        if (method_exists($admin, 'assignRole')) $admin->assignRole('admin');

        $staff = User::firstOrCreate(
            ['email' => 'staff@wms.com'],
            [
                'name' => 'Staff Gudang',
                'password' => bcrypt('staff123'),
                'role' => 'staff_gudang',
                'email_verified_at' => now(),
            ]
        );
        if (method_exists($staff, 'assignRole')) $staff->assignRole('staff_gudang');

        $viewer = User::firstOrCreate(
            ['email' => 'viewer@wms.com'],
            [
                'name' => 'Viewer User',
                'password' => bcrypt('viewer123'),
                'role' => 'viewer',
                'email_verified_at' => now(),
            ]
        );
        if (method_exists($viewer, 'assignRole')) $viewer->assignRole('viewer');

        $warehouse = Warehouse::firstOrCreate(
            ['kode_gudang' => 'GDG-01'],
            [
                'nama' => 'Gudang Utama Jakarta',
                'alamat' => 'Jakarta Timur',
            ]
        );

        $items = [
            ['000001', 'SPEDA LISTRIK', 'JOYCO', '1', 'Pcs', 10000000, 10, 'https://images.unsplash.com/photo-1571068316344-75bc76f77890?w=600&auto=format&fit=crop'],
            ['000002', 'KABEL UTP CAT 6', 'BELDEN', 'INDOOR', 'Roll', 1200000, 15, 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=600&auto=format&fit=crop'],
            ['000003', 'MESIN BOR LISTRIK BOSCH GSB 550 PRO', 'BOSCH', 'GSB 550', 'Pcs', 500000, 50, 'https://images.unsplash.com/photo-1504148455328-c376907d081c?w=600&auto=format&fit=crop'],
            ['000004', 'GERINDA TANGAN MAKITA GA4030', 'MAKITA', 'GA4030', 'Pcs', 600000, 40, 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=600&auto=format&fit=crop'],
            ['000005', 'SET KUNCI PAS TEKIRO 8-24MM', 'TEKIRO', '8-24MM', 'Pcs', 250000, 60, 'https://images.unsplash.com/photo-1586864387967-d02ef85d93e8?w=600&auto=format&fit=crop'],
            ['000006', 'TANG KOMBINASI KRISBOW 7 INCH', 'KRISBOW', '7 INCH', 'Pcs', 80000, 80, 'https://images.unsplash.com/photo-1586864387789-628af9feed72?w=600&auto=format&fit=crop'],
            ['000007', 'MULTIMETER DIGITAL SANWA CD800A', 'SANWA', 'CD800A', 'Pcs', 450000, 35, 'https://images.unsplash.com/photo-1581092335397-9583fe92d232?w=600&auto=format&fit=crop'],
            ['000008', 'METERAN LASER BOSCH GLM 40', 'BOSCH', 'GLM 40', 'Pcs', 800000, 25, 'https://images.unsplash.com/photo-1581092580497-e0d23cbdf1dc?w=600&auto=format&fit=crop'],
            ['000009', 'KABEL ETERNA NYM 2X1.5MM 50M', 'ETERNA', 'NYM', 'Roll', 350000, 45, 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=600&auto=format&fit=crop'],
            ['000010', 'STOP KONTAK BROCO 4 LUBANG + KABEL', 'BROCO', '4 LUBANG', 'Pcs', 90000, 90, 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=600&auto=format&fit=crop'],
            ['000011', 'LAMPU LED PHILIPS MYCARE 12W', 'PHILIPS', 'LED', 'Pcs', 50000, 100, 'https://images.unsplash.com/photo-1550985616-10810253b84d?w=600&auto=format&fit=crop'],
            ['000012', 'LAMPU SOROT LED HANOCHS 50W', 'HANOCHS', 'SOROT', 'Pcs', 150000, 20, 'https://images.unsplash.com/photo-1513506003901-1e6a229e2d15?w=600&auto=format&fit=crop'],
            ['000013', 'KOMPRESOR ANGIN LAKONI IMOLA 75', 'LAKONI', 'IMOLA 75', 'Pcs', 1200000, 10, 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=600&auto=format&fit=crop'],
            ['000014', 'MESIN LAS INVERTER RHINO MMA-120', 'RHINO', 'MMA-120', 'Pcs', 850000, 15, 'https://images.unsplash.com/photo-1504148455328-c376907d081c?w=600&auto=format&fit=crop'],
            ['000015', 'MCB SCHNEIDER ELECTRIC DOMAE 16A', 'SCHNEIDER', 'MCB', 'Pcs', 80000, 120, 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=600&auto=format&fit=crop'],
        ];

        foreach ($items as $item) {
            $product = Product::updateOrCreate(
                ['kode_barang' => $item[0]],
                [
                    'nama' => $item[1],
                    'merk' => $item[2],
                    'tipe' => $item[3],
                    'satuan' => $item[4],
                    'harga' => $item[5],
                    'stok_minimum' => 5,
                    'image' => $item[7],
                ]
            );

            ProductStock::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                ],
                [
                    'quantity' => $item[6],
                ]
            );

            // Sync stock to Olshop
            try {
                \App\Jobs\SyncStockToOlshop::dispatch($product->id, now()->format('Y-m-d\TH:i:s\Z'));
            } catch (\Throwable $e) {
                // Ignore sync errors during seeding if Olshop is offline
            }
        }

        Supplier::firstOrCreate(
            ['nama' => 'PT Teknologi Maju'],
            [
                'kontak' => '021-1234567',
                'alamat' => 'Jakarta Industrial Estate Pulogadung',
            ]
        );

        Customer::firstOrCreate(
            ['nama' => 'CV Jaya Abadi'],
            [
                'alamat' => 'Jl. Sudirman No. 10, Jakarta',
                'kontak' => '0812-9876-5432',
            ]
        );

        $this->call(CompanySettingsSeeder::class);
    }
}
