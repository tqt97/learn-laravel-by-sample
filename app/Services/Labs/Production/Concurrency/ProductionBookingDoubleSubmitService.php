<?php

namespace App\Services\Labs\Production\Concurrency;

use App\DTOs\Labs\LabActionResult;
use App\DTOs\Labs\LabStateResult;
use App\Models\Labs\Production\ProductionBookingReservation;
use App\Models\Labs\Production\ProductionBookingRoom;
use App\Services\Labs\Core\LabDatabaseResetService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class ProductionBookingDoubleSubmitService
{
    public function book(array $payload = []): LabActionResult
    {
        $runMode = $payload['run_mode'] ?? 'single';

        if ($runMode === 'batch_race') {
            return $this->simulateSafeBatch($payload);
        }

        return $this->singleBooking($payload);
    }

    private function singleBooking(array $payload = []): LabActionResult
    {
        $requestKey = $payload['request_key'] ?? (string) Str::uuid();

        try {
            $reservation = DB::transaction(function () use ($requestKey) {
                $existing = ProductionBookingReservation::query()
                    ->where('request_key', $requestKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $room = ProductionBookingRoom::query()
                    ->lockForUpdate()
                    ->firstOrFail();

                $startsAt = now()->addDay()->setTime(10, 0);
                $endsAt = now()->addDay()->setTime(11, 0);

                $exists = ProductionBookingReservation::query()
                    ->where('room_id', $room->id)
                    ->where('starts_at', $startsAt)
                    ->where('ends_at', $endsAt)
                    ->lockForUpdate()
                    ->exists();

                if ($exists) {
                    return null;
                }

                return ProductionBookingReservation::create([
                    'room_id' => $room->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => 'confirmed',
                    'request_key' => $requestKey,
                ]);
            }, attempts: 3);

            if (! $reservation) {
                return LabActionResult::failed('Production: Slot already booked.');
            }

            return LabActionResult::success("Production: Reservation #{$reservation->id} created safely.");
        } catch (Throwable $e) {
            Log::error('Production booking failed.', [
                'exception' => $e,
                'service' => self::class,
                'request_key' => $requestKey,
            ]);

            return LabActionResult::failed('Production: Something went wrong.', statusCode: 500);
        }
    }

    private function simulateSafeBatch(array $payload = []): LabActionResult
    {
        $count = min(max((int) ($payload['count'] ?? 5), 1), 500);

        $success = 0;
        $failed = 0;

        for ($i = 1; $i <= $count; $i++) {
            $result = $this->singleBooking([
                'request_key' => (string) Str::uuid(),
            ]);

            $result->success ? $success++ : $failed++;
        }

        return LabActionResult::success(
            "Production Batch: {$success} success, {$failed} already booked.",
            compact('success', 'failed'),
        );
    }

    public function state(): LabStateResult
    {
        $count = ProductionBookingReservation::query()->count();

        return new LabStateResult(
            mode: 'production',
            title: 'Production Booking',
            metrics: [
                'result_count' => $count,
                'valid_limit' => 1,
                'reservations_count' => $count,
                'valid_slot_limit' => 1,
            ],
            invariants: [
                [
                    'name' => 'Only one reservation per slot',
                    'ok' => $count <= 1,
                    'message' => $count <= 1 ? 'OK' : "Broken: {$count} reservations created.",
                ],
            ],
        );
    }

    public function reset(): LabActionResult
    {
        app(LabDatabaseResetService::class)->resetProductionBooking();

        return LabActionResult::success('Production booking database reset successfully.');
    }
}
