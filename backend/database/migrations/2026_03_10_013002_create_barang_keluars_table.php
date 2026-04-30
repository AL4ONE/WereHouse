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
        Schema::create('barang_keluars', function (Blueprint $table) {
            $table->id();
            $table->foreignId("barang_id")->constrained()->onDelete("cascade");
            $table->string("destination");
            $table->string("recipient_address")->nullable();
            $table->string("recipient_phone")->nullable();
            $table->integer("stock");
            $table->decimal("harga_satuan", 15, 2)->default(0);
            $table->decimal("total_harga", 15, 2)->default(0);
            $table->string("invoice_number")->unique()->nullable();
            $table->decimal("discount", 15, 2)->default(0);
            $table->decimal("shipping_cost", 15, 2)->default(0);
            $table->decimal("down_payment", 15, 2)->default(0);
            $table->string("po_number")->nullable();
            $table->string("vehicle")->nullable();
            $table->string("vehicle_plate")->nullable();
            $table->string("pic_name")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_keluars');
    }
};
