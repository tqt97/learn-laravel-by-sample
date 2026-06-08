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
        Schema::create('naive_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('naive_payment_orders')->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->string('status')->default('succeeded');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('naive_payments');
    }
};
