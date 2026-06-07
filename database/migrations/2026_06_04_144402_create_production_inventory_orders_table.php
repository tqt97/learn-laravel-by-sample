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
        Schema::create('production_inventory_orders', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('product_id')
                ->constrained('production_inventory_products')
                ->cascadeOnDelete();

            $table->unsignedInteger('quantity')->default(1);
            $table->string('status')->default('created');

            $table->string('request_key')->nullable();
            $table->timestamps();

            $table->unique('request_key');
            $table->index(['product_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_inventory_orders');
    }
};
