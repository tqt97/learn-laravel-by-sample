<?php

namespace App\Services\Labs\Naive\Concurrency;

use App\DTOs\Labs\LabActionResult;
use App\DTOs\Labs\LabStateResult;
use App\Models\Labs\Naive\NaiveBookingReservation;
use App\Models\Labs\Naive\NaiveBookingRoom;
use App\Services\Labs\Core\LabDatabaseResetService;
use Illuminate\Support\Facades\Log;
use Throwable;

final class NaiveBookingDoubleSubmitService
{
    public function book(array $payload = []): LabActionResult
    {
        $runMode = $payload['run_mode'] ?? 'single';

        if ($runMode === 'batch_race') {
            return $this->simulateRaceBatch($payload);
        }

        return $this->singleBooking($payload);
    }

    private function singleBooking(array $payload = []): LabActionResult
    {
        try {
            $room = NaiveBookingRoom::query()->firstOrFail();

            $startsAt = now()->addDay()->setTime(10, 0);
            $endsAt = now()->addDay()->setTime(11, 0);

            $exists = NaiveBookingReservation::query()
                ->where('room_id', $room->id)
                ->where('starts_at', $startsAt)
                ->where('ends_at', $endsAt)
                ->exists();

            if ($exists) {
                return LabActionResult::failed('Naive: Slot already booked.');
            }

            usleep((int) ($payload['delay_microseconds'] ?? 300_000));

            $reservation = NaiveBookingReservation::create([
                'room_id' => $room->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'confirmed',
            ]);

            return LabActionResult::success("Naive: Reservation #{$reservation->id} created.");
        } catch (Throwable $e) {
            Log::error('Naive booking failed.', [
                'exception' => $e,
                'service' => self::class,
            ]);

            return LabActionResult::failed('Naive: Something went wrong.', statusCode: 500);
        }
    }

    private function simulateRaceBatch(array $payload = []): LabActionResult
    {
        $count = min(max((int) ($payload['count'] ?? 5), 1), 500);

        $room = NaiveBookingRoom::query()->firstOrFail();

        $startsAt = now()->addDay()->setTime(10, 0);
        $endsAt = now()->addDay()->setTime(11, 0);

        $slotWasFree = ! NaiveBookingReservation::query()
            ->where('room_id', $room->id)
            ->where('starts_at', $startsAt)
            ->where('ends_at', $endsAt)
            ->exists();

        if (! $slotWasFree) {
            return LabActionResult::failed('Naive Batch: Slot already booked.');
        }

        $createdIds = [];

        for ($i = 1; $i <= $count; $i++) {
            $reservation = NaiveBookingReservation::create([
                'room_id' => $room->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => 'confirmed',
            ]);

            $createdIds[] = $reservation->id;
        }

        return LabActionResult::success(
            "Naive Batch: {$count} readers saw free slot and created ".count($createdIds).' reservations.',
            [
                'created_ids' => $createdIds,
                'reservations_created' => count($createdIds),
            ],
        );
    }

    public function state(): LabStateResult
    {
        $count = NaiveBookingReservation::query()->count();

        return new LabStateResult(
            mode: 'naive',
            title: 'Naive Booking',
            // metrics: [
            //     'reservations_count' => $count,
            //     'valid_slot_limit' => 1,
            // ],
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
                    'message' => $count <= 1 ? 'OK' : "Broken: {$count} reservations created for one slot.",
                ],
            ],
        );
    }

    public function reset(): LabActionResult
    {
        app(LabDatabaseResetService::class)->resetNaiveBooking();

        return LabActionResult::success('Naive booking database reset successfully.');
    }
}
