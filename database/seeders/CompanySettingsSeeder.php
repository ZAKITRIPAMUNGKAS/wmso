<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use Illuminate\Database\Seeder;

class CompanySettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // GENERAL
            ['key' => 'company_name',       'value' => 'CV. Listrindo Jaya Elektrik',    
             'group' => 'general', 'label' => 'Nama Perusahaan',    'type' => 'text'],
            ['key' => 'company_short_name', 'value' => 'Listrindo Jaya',                 
             'group' => 'general', 'label' => 'Nama Singkat',        'type' => 'text'],
            ['key' => 'company_tagline',    'value' => '', 
             'group' => 'general', 'label' => 'Tagline',             'type' => 'text'],
            
            // ADDRESS
            ['key' => 'address_street',     'value' => 'Jl. Tebet Raya No.11G 1',       
             'group' => 'address', 'label' => 'Jalan',               'type' => 'text'],
            ['key' => 'address_rt_rw',      'value' => 'RT.20/RW.2',                    
             'group' => 'address', 'label' => 'RT/RW',               'type' => 'text'],
            ['key' => 'address_kelurahan',  'value' => 'Tebet Barat',                   
             'group' => 'address', 'label' => 'Kelurahan',           'type' => 'text'],
            ['key' => 'address_kecamatan',  'value' => 'Kec. Tebet',                    
             'group' => 'address', 'label' => 'Kecamatan',           'type' => 'text'],
            ['key' => 'address_kota',       'value' => 'Kota Jakarta Selatan',          
             'group' => 'address', 'label' => 'Kota',                'type' => 'text'],
            ['key' => 'address_provinsi',   'value' => 'DKI Jakarta',                   
             'group' => 'address', 'label' => 'Provinsi',            'type' => 'text'],
            ['key' => 'address_kode_pos',   'value' => '12810',                         
             'group' => 'address', 'label' => 'Kode Pos',            'type' => 'text'],
            
            // CONTACT
            ['key' => 'phone_primary',      'value' => '0812-2507-9988',                
             'group' => 'contact', 'label' => 'Telepon Utama',       'type' => 'text'],
            ['key' => 'phone_secondary',    'value' => '0856-7224-975',                 
             'group' => 'contact', 'label' => 'Telepon Kedua',       'type' => 'text'],
            ['key' => 'email',              'value' => 'info@listrindojayaelektrik.com',                              
             'group' => 'contact', 'label' => 'Email',               'type' => 'email'],
            ['key' => 'website',            'value' => 'https://listrindojayaelektrik.com',                              
             'group' => 'contact', 'label' => 'Website',             'type' => 'url'],
        ];

        foreach ($settings as $s) {
            CompanySetting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
