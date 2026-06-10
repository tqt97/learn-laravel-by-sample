<?php

namespace App\Services\Labs\Core\Reset;

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

final class ConcurrencyLabResetService
{
    public function resetInventoryOversell(): void
    {
        $this->withoutForeignKeyChecks(function () {
            NaiveInventoryOrder::truncate();
            NaiveInventoryProduct::truncate();

            ProductionInventoryMovement::truncate();
            ProductionInventoryOrder::truncate();
            ProductionInventoryProduct::truncate();
        });

        NaiveInventoryProduct::factory()
            ->oneStock()
            ->create();

        ProductionInventoryProduct::factory()
            ->oneStock()
            ->create();
    }

    public function resetNaiveInventory(): void
    {
        $this->withoutForeignKeyChecks(function () {
            NaiveInventoryOrder::truncate();
            NaiveInventoryProduct::truncate();
        });

        NaiveInventoryProduct::factory()
            ->oneStock()
            ->create();
    }

    public function resetProductionInventory(): void
    {
        $this->withoutForeignKeyChecks(function () {
            ProductionInventoryMovement::truncate();
            ProductionInventoryOrder::truncate();
            ProductionInventoryProduct::truncate();
        });

        ProductionInventoryProduct::factory()
            ->oneStock()
            ->create();
    }

    public function resetBookingDoubleSubmit(): void
    {
        $this->resetNaiveBooking();
        $this->resetProductionBooking();
    }

    public function resetNaiveBooking(): void
    {
        $this->withoutForeignKeyChecks(function () {
            NaiveBookingReservation::truncate();
            NaiveBookingRoom::truncate();
        });

        NaiveBookingRoom::factory()
            ->create([
                'name' => 'Naive Meeting Room',
            ]);
    }

    public function resetProductionBooking(): void
    {
        $this->withoutForeignKeyChecks(function () {
            ProductionBookingReservation::truncate();
            ProductionBookingRoom::truncate();
        });

        ProductionBookingRoom::factory()
            ->create([
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
        $this->withoutForeignKeyChecks(function () {
            NaivePayment::truncate();
            NaivePaymentOrder::truncate();
        });

        NaivePaymentOrder::factory()
            ->create([
                'amount' => 100000,
                'status' => 'pending',
            ]);
    }

    public function resetProductionPayment(): void
    {
        $this->withoutForeignKeyChecks(function () {
            ProductionIdempotencyKey::truncate();
            ProductionPayment::truncate();
            ProductionPaymentOrder::truncate();
        });

        ProductionPaymentOrder::factory()
            ->create([
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

        NaiveJobNotification::factory()
            ->create([
                'recipient' => 'user@example.com',
                'sent_count' => 0,
            ]);
    }

    public function resetProductionQueue(): void
    {
        $this->withoutForeignKeyChecks(function () {
            ProductionProcessedJob::truncate();
            ProductionJobNotification::truncate();
        });

        ProductionJobNotification::factory()
            ->create([
                'recipient' => 'user@example.com',
                'sent_count' => 0,
            ]);
    }

    private function withoutForeignKeyChecks(callable $callback): void
    {
        Schema::disableForeignKeyConstraints();

        try {
            $callback();
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
}
