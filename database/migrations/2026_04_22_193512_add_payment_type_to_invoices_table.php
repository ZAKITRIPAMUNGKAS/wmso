<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // 'cash' atau 'tempo'
            $table->enum('jenis_pembayaran', ['cash', 'tempo'])->default('tempo')->after('status');
            // Tempo dalam hari (hanya berlaku jika jenis_pembayaran = tempo)
            $table->unsignedTinyInteger('tempo_hari')->default(30)->after('jenis_pembayaran');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['jenis_pembayaran', 'tempo_hari']);
        });
    }
};
