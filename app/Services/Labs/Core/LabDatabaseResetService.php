<?php

namespace App\Services\Labs\Core;

use App\Models\Labs\Naive\NaiveBookingReservation;
use App\Models\Labs\Naive\NaiveBookingRoom;
use App\Models\Labs\Naive\NaiveInventoryOrder;
use App\Models\Labs\Naive\NaiveInventoryProduct;
use App\Models\Labs\Naive\NaiveJobNotification;
use App\Models\Labs\Naive\NaivePayment;
use App\Models\Labs\Naive\NaivePaymentOrder;
use App\Models\Labs\Production\ProductionBookingReservation;
use App\Models\Labs\Production\ProductionBookingRoom;
use App\Models\Labs\Production\ProductionIdempotencyKey;
use App\Models\Labs\Production\ProductionInventoryMovement;
use App\Models\Labs\Production\ProductionInventoryOrder;
use App\Models\Labs\Production\ProductionInventoryProduct;
use App\Models\Labs\Production\ProductionJobNotification;
use App\Models\Labs\Production\ProductionPayment;
use App\Models\Labs\Production\ProductionPaymentOrder;
use App\Models\Labs\Production\ProductionProcessedJob;
use Illuminate\Support\Facades\Schema;

final class LabDatabaseResetService
{
    public function resetNaiveInventory(): void
    {
        Schema::disableForeignKeyConstraints();

        NaiveInventoryOrder::truncate();
        NaiveInventoryProduct::truncate();

        Schema::enableForeignKeyConstraints();

        NaiveInventoryProduct::factory()->oneStock()->create();
    }

    public function resetProductionInventory(): void
    {
        Schema::disableForeignKeyConstraints();

        ProductionInventoryMovement::truncate();
        ProductionInventoryOrder::truncate();
        ProductionInventoryProduct::truncate();

        Schema::enableForeignKeyConstraints();

        ProductionInventoryProduct::factory()->oneStock()->create();
    }

    public function resetInventoryOversell(): void
    {
        Schema::disableForeignKeyConstraints();

        NaiveInventoryOrder::truncate();
        NaiveInventoryProduct::truncate();

        ProductionInventoryMovement::truncate();
        ProductionInventoryOrder::truncate();
        ProductionInventoryProduct::truncate();

        Schema::enableForeignKeyConstraints();

        NaiveInventoryProduct::factory()->oneStock()->create();
        ProductionInventoryProduct::factory()->oneStock()->create();
    }

    public function resetBookingDoubleSubmit(): void
    {
        $this->resetNaiveBooking();
        $this->resetProductionBooking();
    }

    public function resetNaiveBooking(): void
    {
        Schema::disableForeignKeyConstraints();

        NaiveBookingReservation::truncate();
        NaiveBookingRoom::truncate();

        Schema::enableForeignKeyConstraints();

        NaiveBookingRoom::factory()->create([
            'name' => 'Naive Meeting Room',
        ]);
    }

    public function resetProductionBooking(): void
    {
        Schema::disableForeignKeyConstraints();

        ProductionBookingReservation::truncate();
        ProductionBookingRoom::truncate();

        Schema::enableForeignKeyConstraints();

        ProductionBookingRoom::factory()->create([
            'name' => 'Production Meeting Room',
        ]);
    }

    public function resetPaymentIdempotency(): void
    {
        $this->resetNaivePayment();
        $this->resetProductionPayment();
    }

    public function resetNaivePayment(): void
    {
        Schema::disableForeignKeyConstraints();

        NaivePayment::truncate();
        NaivePaymentOrder::truncate();

        Schema::enableForeignKeyConstraints();

        NaivePaymentOrder::factory()->create([
            'amount' => 100000,
            'status' => 'pending',
        ]);
    }

    public function resetProductionPayment(): void
    {
        Schema::disableForeignKeyConstraints();

        ProductionIdempotencyKey::truncate();
        ProductionPayment::truncate();
        ProductionPaymentOrder::truncate();

        Schema::enableForeignKeyConstraints();

        ProductionPaymentOrder::factory()->create([
            'amount' => 100000,
            'status' => 'pending',
        ]);
    }

    public function resetQueueRetrySafeJob(): void
    {
        $this->resetNaiveQueue();
        $this->resetProductionQueue();
    }

    public function resetNaiveQueue(): void
    {
        NaiveJobNotification::truncate();

        NaiveJobNotification::factory()->create([
            'recipient' => 'user@example.com',
            'sent_count' => 0,
        ]);
    }

    public function resetProductionQueue(): void
    {
        Schema::disableForeignKeyConstraints();

        ProductionProcessedJob::truncate();
        ProductionJobNotification::truncate();

        Schema::enableForeignKeyConstraints();

        ProductionJobNotification::factory()->create([
            'recipient' => 'user@example.com',
            'sent_count' => 0,
        ]);
    }
}
