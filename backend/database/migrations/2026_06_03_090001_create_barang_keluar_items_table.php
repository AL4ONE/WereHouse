<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('barang_keluar_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_keluar_id')->constrained('barang_keluars')->onDelete('cascade');
            $table->foreignId('barang_id')->constrained('barangs')->onDelete('cascade');
            $table->integer('stock');
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->decimal('total_harga', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('barang_keluars', function (Blueprint $table) {
            $table->foreignId('barang_id')->nullable()->change();
            $table->integer('stock')->nullable()->change();
            $table->decimal('harga_satuan', 15, 2)->nullable()->change();
        });

        if (Schema::hasTable('barang_keluars')) {
            $existing = DB::table('barang_keluars')
                ->whereNotNull('barang_id')
                ->where('barang_id', '>', 0)
                ->get();

            foreach ($existing as $row) {
                $alreadyExists = DB::table('barang_keluar_items')
                    ->where('barang_keluar_id', $row->id)
                    ->where('barang_id', $row->barang_id)
                    ->exists();

                if (!$alreadyExists) {
                    DB::table('barang_keluar_items')->insert([
                        'barang_keluar_id' => $row->id,
                        'barang_id' => $row->barang_id,
                        'stock' => $row->stock ?? 1,
                        'harga_satuan' => $row->harga_satuan ?? 0,
                        'total_harga' => $row->total_harga ?? 0,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_keluar_items');

        Schema::table('barang_keluars', function (Blueprint $table) {
            $table->foreignId('barang_id')->nullable(false)->change();
            $table->integer('stock')->nullable(false)->change();
            $table->decimal('harga_satuan', 15, 2)->nullable(false)->change();
        });
    }
};
