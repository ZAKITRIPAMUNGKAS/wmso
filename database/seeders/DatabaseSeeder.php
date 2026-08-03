<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Create Spatie Roles
        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'staff_gudang']);
            \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'viewer']);
        }

        // 2. Admin User Account
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

        // 3. Staff Gudang User Account
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

        // 4. Viewer User Account
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

        // 5. Default Primary Warehouse Structure (Required for stock operations)
        Warehouse::firstOrCreate(
            ['kode_gudang' => 'GDG-01'],
            [
                'nama' => 'Gudang Utama Jakarta',
                'alamat' => 'Jakarta Timur',
            ]
        );

        // 6. Company Settings
        $this->call(CompanySettingsSeeder::class);
    }
}
