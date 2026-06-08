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
        Schema::create('production_booking_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('production_booking_rooms')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status')->default('confirmed');
            $table->string('request_key')->nullable();
            $table->timestamps();

            $table->unique('request_key');
            $table->index(['room_id', 'starts_at', 'ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_booking_reservations');
    }
};
