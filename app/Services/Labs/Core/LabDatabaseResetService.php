<?php

namespace App\Services\Labs\Core;

use App\Services\Labs\Core\Reset\ConcurrencyLabResetService;

final class LabDatabaseResetService
{
    public function __construct(
        private readonly ConcurrencyLabResetService $concurrency,
    ) {}

    public function resetInventoryOversell(): void
    {
        $this->concurrency->resetInventoryOversell();
    }

    public function resetNaiveInventory(): void
    {
        $this->concurrency->resetNaiveInventory();
    }

    public function resetProductionInventory(): void
    {
        $this->concurrency->resetProductionInventory();
    }

    public function resetBookingDoubleSubmit(): void
    {
        $this->concurrency->resetBookingDoubleSubmit();
    }

    public function resetNaiveBooking(): void
    {
        $this->concurrency->resetNaiveBooking();
    }

    public function resetProductionBooking(): void
    {
        $this->concurrency->resetProductionBooking();
    }

    public function resetPaymentIdempotency(): void
    {
        $this->concurrency->resetPaymentIdempotency();
    }

    public function resetNaivePayment(): void
    {
        $this->concurrency->resetNaivePayment();
    }

    public function resetProductionPayment(): void
    {
        $this->concurrency->resetProductionPayment();
    }

    public function resetQueueRetrySafeJob(): void
    {
        $this->concurrency->resetQueueRetrySafeJob();
    }

    public function resetNaiveQueue(): void
    {
        $this->concurrency->resetNaiveQueue();
    }

    public function resetProductionQueue(): void
    {
        $this->concurrency->resetProductionQueue();
    }
}
