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
        Schema::create('production_inventory_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->unsignedInteger('stock_on_hand')->default(0);
            $table->unsignedInteger('reserved_stock')->default(0);
            $table->unsignedInteger('sold_stock')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_inventory_products');
    }
};
