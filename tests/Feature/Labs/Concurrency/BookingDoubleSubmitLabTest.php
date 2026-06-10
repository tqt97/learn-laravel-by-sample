<?php

use App\Models\Labs\Naive\NaiveBookingReservation;
use App\Models\Labs\Production\ProductionBookingReservation;
use App\Services\Labs\Core\LabDatabaseResetService;

it('resets booking double submit lab to deterministic state', function () {
    app(LabDatabaseResetService::class)->resetBookingDoubleSubmit();

    expect(NaiveBookingReservation::count())->toBe(0)
        ->and(ProductionBookingReservation::count())->toBe(0);
});

it('breaks invariant in naive booking simulation', function () {
    app(LabDatabaseResetService::class)->resetBookingDoubleSubmit();

    $response = $this->postJson(route('labs.action', [
        'scenario' => 'booking-double-submit',
        'mode' => 'naive',
    ]), [
        'run_mode' => 'batch_race',
        'count' => 20,
    ]);

    $response->assertOk();

    expect(NaiveBookingReservation::count())->toBe(20);
});

it('protects invariant in production booking simulation', function () {
    app(LabDatabaseResetService::class)->resetBookingDoubleSubmit();

    $response = $this->postJson(route('labs.action', [
        'scenario' => 'booking-double-submit',
        'mode' => 'production',
    ]), [
        'run_mode' => 'batch_race',
        'count' => 20,
    ]);

    $response->assertOk();

    expect(ProductionBookingReservation::count())->toBe(1);
});
