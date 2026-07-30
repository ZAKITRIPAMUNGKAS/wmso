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
        $tables = [
            'goods_receipt_items',
            'delivery_order_items',
            'stock_transfer_items',
            'stock_adjustment_items',
            'stock_movements'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreignId('rack_id')->nullable()->constrained('racks')->onDelete('set null');
                $table->string('batch_number')->nullable();
                $table->date('expired_at')->nullable();
                $table->string('serial_number')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'goods_receipt_items',
            'delivery_order_items',
            'stock_transfer_items',
            'stock_adjustment_items',
            'stock_movements'
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                // Drop foreign key first
                $table->dropForeign([$tableName . '_rack_id_foreign']);
                $table->dropColumn(['rack_id', 'batch_number', 'expired_at', 'serial_number']);
            });
        }
    }
};

