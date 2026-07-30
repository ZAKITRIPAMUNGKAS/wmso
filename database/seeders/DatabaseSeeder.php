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

        Product::firstOrCreate(
            ['kode_barang' => 'BRG-001'],
            [
                'nama' => 'Kabel UTP Cat 6',
                'merk' => 'Belden',
                'tipe' => 'Indoor',
                'satuan' => 'Roll',
                'harga' => 1200000,
                'stok_minimum' => 5,
            ]
        );

        $items = [
            ['SKU-9CPHNFID', 'Mesin Bor Listrik Bosch GSB 550 Pro', 'Bosch', 'GSB 550', 500000],
            ['SKU-HL1WOEPZ', 'Gerinda Tangan Makita GA4030', 'Makita', 'GA4030', 600000],
            ['SKU-AAE2DZIH', 'Set Kunci Pas Tekiro 8-24mm', 'Tekiro', '8-24mm', 250000],
            ['SKU-VHURNAT3', 'Tang Kombinasi Krisbow 7 Inch', 'Krisbow', '7 Inch', 80000],
            ['SKU-L8Q3IZXQ', 'Multimeter Digital Sanwa CD800a', 'Sanwa', 'CD800a', 450000],
            ['SKU-AUEP5FFO', 'Meteran Laser Bosch GLM 40', 'Bosch', 'GLM 40', 800000],
            ['SKU-SLDENRLA', 'Kabel Eterna NYM 2x1.5mm 50m', 'Eterna', 'NYM', 350000],
            ['SKU-GYGUHSLZ', 'Stop Kontak Broco 4 Lubang + Kabel', 'Broco', '4 Lubang', 90000],
            ['SKU-6AXKM6TW', 'Lampu LED Philips MyCare 12W', 'Philips', 'LED', 50000],
            ['SKU-SHKHAYQA', 'Lampu Sorot LED Hanochs 50W', 'Hanochs', 'Sorot', 150000],
            ['SKU-R5UCSBQY', 'Kompresor Angin Lakoni Imola 75', 'Lakoni', 'Imola 75', 1200000],
            ['SKU-LUIY3SJS', 'Mesin Las Inverter Rhino MMA-120', 'Rhino', 'MMA-120', 850000],
            ['SKU-VNV5LIY3', 'Smart Switch Wifi Tuya 1 Channel', 'Tuya', 'Smart Switch', 100000],
            ['SKU-YD4SQUN1', 'Lampu LED Philips Essential 18W Putih', 'Philips', 'Essential', 65000],
            ['SKU-RHAOTUPD', 'Kabel Eterna NYA 1x1.5mm 100 Meter', 'Eterna', 'NYA', 250000],
            ['SKU-NVQBFM2M', 'Tang Potong Tekiro 6 Inch', 'Tekiro', '6 Inch', 65000],
            ['SKU-J0KHEGDC', 'MCB Schneider Electric Domae 1 Phase 16A', 'Schneider', 'MCB', 80000],
            ['SKU-FRZ001SG', 'Nugget Ayam So Good 500g',                 'So Good',   'Frozen', 32000],
            ['SKU-FRZ002FS', 'Sosis Sapi Fiesta 360g',                   'Fiesta',    'Frozen', 28500],
            ['SKU-FRZ003CD', 'Fillet Ikan Dori Cedea 500g',              'Cedea',     'Frozen', 45000],
            ['SKU-FRZ004CH', 'Dimsum Siomay Udang Champ 300g',           'Champ',     'Frozen', 38000],
            ['SKU-FRZ005BF', 'Beef Burger Patty Belfoods 400g',          'Belfoods',  'Frozen', 55000],
        ];

        $stockData = [
            'SKU-9CPHNFID' => 50,
            'SKU-HL1WOEPZ' => 40,
            'SKU-AAE2DZIH' => 60,
            'SKU-VHURNAT3' => 80,
            'SKU-L8Q3IZXQ' => 35,
            'SKU-AUEP5FFO' => 25,
            'SKU-SLDENRLA' => 45,
            'SKU-GYGUHSLZ' => 90,
            'SKU-6AXKM6TW' => 100,
            'SKU-SHKHAYQA' => 15,
            'SKU-R5UCSBQY' => 10,
            'SKU-LUIY3SJS' => 8,
            'SKU-VNV5LIY3' => 30,
            'SKU-YD4SQUN1' => 75,
            'SKU-RHAOTUPD' => 50,
            'SKU-NVQBFM2M' => 40,
            'SKU-J0KHEGDC' => 120,
            'SKU-FRZ001SG' => 200,
            'SKU-FRZ002FS' => 150,
            'SKU-FRZ003CD' => 100,
            'SKU-FRZ004CH' => 120,
            'SKU-FRZ005BF' => 80,
        ];

        $warehouse = Warehouse::firstOrCreate(
            ['kode_gudang' => 'GDG-01'],
            [
                'nama' => 'Gudang Utama Jakarta',
                'alamat' => 'Jakarta Timur',
            ]
        );

        foreach ($items as $item) {
            $product = Product::firstOrCreate(
                ['kode_barang' => $item[0]],
                [
                    'nama' => $item[1],
                    'merk' => $item[2],
                    'tipe' => $item[3],
                    'harga' => $item[4],
                    'satuan' => 'Pcs',
                    'stok_minimum' => 5
                ]
            );

            // Seed stock
            $qty = $stockData[$item[0]] ?? 0;
            \App\Models\ProductStock::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                ],
                [
                    'quantity' => $qty,
                ]
            );
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
