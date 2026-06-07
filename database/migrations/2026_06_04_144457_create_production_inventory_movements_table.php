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
        Schema::create('production_inventory_movements', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('product_id')
                ->constrained('production_inventory_products')
                ->cascadeOnDelete();

            $table->string('type');
            $table->integer('stock_delta')->default(0);
            $table->integer('reserved_delta')->default(0);
            $table->integer('sold_delta')->default(0);

            $table->unsignedInteger('stock_on_hand_after');
            $table->unsignedInteger('reserved_stock_after');
            $table->unsignedInteger('sold_stock_after');

            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_inventory_movements');
    }
};
