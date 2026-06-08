<?php

namespace App\Services\Labs\Core\Scenarios;

use App\DTOs\Labs\LabActionResult;
use App\DTOs\Labs\LabStateResult;
use App\Enums\Labs\LabMode;
use App\Services\Labs\Contracts\LabScenarioContract;
use App\Services\Labs\Core\LabDatabaseResetService;
use App\Services\Labs\Naive\Concurrency\NaiveBookingDoubleSubmitService;
use App\Services\Labs\Production\Concurrency\ProductionBookingDoubleSubmitService;

final class BookingDoubleSubmitScenario implements LabScenarioContract
{
    public function __construct(
        private readonly NaiveBookingDoubleSubmitService $naive,
        private readonly ProductionBookingDoubleSubmitService $production,
        private readonly LabDatabaseResetService $resetService,
    ) {}

    public function key(): string
    {
        return 'booking-double-submit';
    }

    public function title(): string
    {
        return __('lab_scenarios.'.$this->key().'.title');
    }

    public function subtitle(): string
    {
        return __('lab_scenarios.'.$this->key().'.subtitle');
    }

    public function description(): string
    {
        return __('lab_scenarios.'.$this->key().'.description');
    }

    public function actionHint(): string
    {
        return __('lab_scenarios.'.$this->key().'.action_hint');
    }

    public function howToUse(): array
    {
        return __('lab_scenarios.'.$this->key().'.how_to_use');
    }

    public function learningGoals(): array
    {
        return __('lab_scenarios.'.$this->key().'.learning_goals');
    }

    public function naiveTechniques(): array
    {
        return __('lab_scenarios.'.$this->key().'.naive_techniques');
    }

    public function productionTechniques(): array
    {
        return __('lab_scenarios.'.$this->key().'.production_techniques');
    }

    public function actionPresets(): array
    {
        return [
            'real_requests' => [1, 2, 5],
            'race_simulation' => [5, 20, 100],
        ];
    }

    public function limits(): array
    {
        return [
            'real_requests_max' => 20,
            'race_simulation_max' => 500,
        ];
    }

    public function uiConfig(): array
    {
        return [
            'actions' => [
                'real_requests_label' => 'Real booking requests',
                'simulation_label' => 'Double-submit simulation',
                'custom_real_default' => 10,
                'custom_simulation_default' => 50,
            ],
            'chart' => [
                'title' => 'Reservations vs Valid Slot Limit',
                'description' => 'Compare created reservations against the one-slot limit.',
                'naive_label' => 'Naive Reservations',
                'production_label' => 'Production Reservations',
                'limit_label' => 'Valid Slot Limit',
                'metric_key' => 'result_count',
                'limit_key' => 'valid_limit',
            ],
            'logs' => [
                'naive_title' => 'Naive Booking Log',
                'production_title' => 'Production Booking Log',
            ],
        ];
    }

    public function action(LabMode $mode, array $payload = []): LabActionResult
    {
        return match ($mode) {
            LabMode::Naive => $this->naive->book($payload),
            LabMode::Production => $this->production->book($payload),
        };
    }

    public function state(LabMode $mode): LabStateResult
    {
        return match ($mode) {
            LabMode::Naive => $this->naive->state(),
            LabMode::Production => $this->production->state(),
        };
    }

    public function reset(LabMode $mode): LabActionResult
    {
        return match ($mode) {
            LabMode::Naive => $this->naive->reset(),
            LabMode::Production => $this->production->reset(),
        };
    }

    public function resetAll(): LabActionResult
    {
        $this->resetService->resetBookingDoubleSubmit();

        return LabActionResult::success('Reset both Naive and Production booking databases successfully.');
    }

    public function learningCenter(): array
    {
        return [
            'overview' => [
                'problem' => 'Một phòng họp chỉ được đặt một lần trong cùng một khung giờ, nhưng nhiều request có thể submit cùng lúc.',
                'failure' => 'Naive flow dùng read → check → insert nên nhiều request cùng thấy slot còn trống và cùng tạo reservation.',
                'solution' => 'Production flow dùng DB transaction, lockForUpdate trên room và kiểm tra slot bên trong transaction.',
                'cost' => 'An toàn hơn nhưng có thêm lock, transaction và throughput giảm khi nhiều request tranh cùng một room.',
            ],
            'code_examples' => [
                [
                    'title' => 'Naive Booking Flow',
                    'type' => 'naive',
                    'language' => 'php',
                    'description' => 'Dễ hiểu nhưng có race window giữa lúc check slot và lúc tạo reservation.',
                    'code' => <<<'PHP'
                        $exists = NaiveBookingReservation::query()
                            ->where('room_id', $room->id)
                            ->where('starts_at', $startsAt)
                            ->where('ends_at', $endsAt)
                            ->exists();

                        if ($exists) {
                            return LabActionResult::failed('Naive: Slot already booked.');
                        }

                        usleep(300_000);

                        $reservation = NaiveBookingReservation::create([
                            'room_id' => $room->id,
                            'starts_at' => $startsAt,
                            'ends_at' => $endsAt,
                            'status' => 'confirmed',
                        ]);
                        PHP,
                ],
                [
                    'title' => 'Production Booking Flow',
                    'type' => 'production',
                    'language' => 'php',
                    'description' => 'Lock room trong transaction để các request cùng tranh một resource phải xử lý tuần tự.',
                    'code' => <<<'PHP'
                        $reservation = DB::transaction(function () use ($requestKey) {
                            $room = ProductionBookingRoom::query()
                                ->lockForUpdate()
                                ->firstOrFail();

                            $exists = ProductionBookingReservation::query()
                                ->where('room_id', $room->id)
                                ->where('starts_at', $startsAt)
                                ->where('ends_at', $endsAt)
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
                        PHP,
                ],
            ],
            'sequence_diagrams' => [
                [
                    'title' => 'Naive Double Submit',
                    'type' => 'naive',
                    'content' => <<<'TEXT'
                        Request A checks slot
                        Slot is free

                        Request B checks slot
                        Slot is free

                        Request A creates reservation
                        Request B creates reservation

                        Result:
                        Two reservations exist for one room and one time slot.
                        Invariant is broken.
                        TEXT,
                ],
                [
                    'title' => 'Production Booking',
                    'type' => 'production',
                    'content' => <<<'TEXT'
                        Request A starts transaction
                        Request A locks room row
                        Request A checks slot
                        Request A creates reservation
                        Request A commits

                        Request B waits for lock
                        Request B checks slot
                        Slot already booked
                        Request B fails safely

                        Result:
                        Only one reservation exists.
                        Invariant is protected.
                        TEXT,
                ],
            ],
            'database_schemas' => [
                [
                    'title' => 'naive_booking_rooms',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        name
                        created_at
                        updated_at
                        SQL,
                ],
                [
                    'title' => 'naive_booking_reservations',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        room_id
                        starts_at
                        ends_at
                        status
                        created_at
                        updated_at
                        SQL,
                ],
                [
                    'title' => 'production_booking_reservations',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        room_id
                        starts_at
                        ends_at
                        status
                        request_key
                        created_at
                        updated_at

                        UNIQUE request_key
                        INDEX room_id, starts_at, ends_at
                        SQL,
                ],
            ],
            'trade_offs' => [
                [
                    'technique' => 'Read → Check → Insert',
                    'pros' => [
                        'Dễ viết, dễ hiểu.',
                        'Ít lock, code ngắn.',
                    ],
                    'cons' => [
                        'Có race condition khi nhiều request cùng check slot.',
                        'Có thể tạo nhiều booking cho cùng một room/time slot.',
                    ],
                ],
                [
                    'technique' => 'DB Transaction',
                    'pros' => [
                        'Gom check slot và insert reservation thành một unit.',
                        'Rollback được nếu có lỗi.',
                    ],
                    'cons' => [
                        'Nếu transaction dài sẽ giữ lock lâu.',
                        'Cần hiểu deadlock/retry.',
                    ],
                ],
                [
                    'technique' => 'lockForUpdate',
                    'pros' => [
                        'Ép request tranh cùng room xử lý tuần tự.',
                        'Dễ áp dụng cho resource booking.',
                    ],
                    'cons' => [
                        'Giảm throughput khi nhiều request tranh cùng resource.',
                        'Không nên lock quá rộng hoặc quá lâu.',
                    ],
                ],
                [
                    'technique' => 'Request Key',
                    'pros' => [
                        'Giảm duplicate booking do user double submit cùng request.',
                        'Dễ trace request.',
                    ],
                    'cons' => [
                        'Chưa thay thế được full idempotency design.',
                        'Cần unique constraint để thật sự an toàn.',
                    ],
                ],
            ],
        ];
    }
}
