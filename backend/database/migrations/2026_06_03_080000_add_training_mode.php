<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update users role enum to include training roles
        // SQLite doesn't enforce enum, so we just need to make sure Laravel validation handles it
        // For MySQL, we'd need to alter the enum. For SQLite, the column is already a string.

        // Add is_training to barangs
        Schema::table('barangs', function (Blueprint $table) {
            $table->boolean('is_training')->default(false)->after('min_stock');
        });

        // Add is_training to suppliers
        Schema::table('suppliers', function (Blueprint $table) {
            $table->boolean('is_training')->default(false)->after('alamat');
        });

        // Add is_training to barang_masuks
        Schema::table('barang_masuks', function (Blueprint $table) {
            $table->boolean('is_training')->default(false)->after('total_harga');
        });

        // Add is_training to barang_keluars
        Schema::table('barang_keluars', function (Blueprint $table) {
            $table->boolean('is_training')->default(false)->after('pic_name');
        });

        // Add is_training to purchase_orders
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->boolean('is_training')->default(false)->after('total_amount');
        });

        // Add is_training to status_op_names
        Schema::table('status_op_names', function (Blueprint $table) {
            $table->boolean('is_training')->default(false)->after('barang_id');
        });
    }

    public function down(): void
    {
        $tables = ['barangs', 'suppliers', 'barang_masuks', 'barang_keluars', 'purchase_orders', 'status_op_names'];
        
        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('is_training');
            });
        }
    }
};
