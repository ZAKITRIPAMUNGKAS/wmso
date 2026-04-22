<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \App\Models\CompanySetting::updateOrCreate(
            ['key' => 'company_logo'],
            [
                'label' => 'Logo Perusahaan',
                'value' => null,
                'type' => 'file',
                'group' => 'general',
            ]
        );
    }

    public function down(): void
    {
        \App\Models\CompanySetting::where('key', 'company_logo')->delete();
    }
};
