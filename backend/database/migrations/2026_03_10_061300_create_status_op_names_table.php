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
        Schema::create('status_op_names', function (Blueprint $table) {
            $table->id();
            $table->integer("stock");
            $table->string("keterangan");
            $table->enum("tipe", ["penambahan", "pengurangan"]);
            $table->foreignId("barang_id")->constrained()->onDelete("cascade");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_op_names');
    }
};
