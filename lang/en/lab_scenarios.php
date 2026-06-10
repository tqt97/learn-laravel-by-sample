<?php

return [
    'inventory-oversell' => [
        'title' => 'Inventory Oversell',
        'subtitle' => 'Laravel System Design Lab',
        'description' => 'Compare checkout without concurrency control versus checkout using atomic updates and invariants.',
        'action_hint' => 'Click multiple requests continuously to see how Naive can create multiple orders from one stock, while Production only allows the first request to succeed and subsequent ones to report out of stock.',
        'learning_goals' => [
            'Understand why oversell happens when many requests read stale stock.',
            'Understand the difference between real requests and race condition simulation.',
            'Understand how atomic update protects inventory invariants.',
            'Learn how to observe bugs through metrics, invariants, logs, and charts.',
        ],
        'how_to_use' => [
            'Click Real Requests to send actual Ajax requests from the browser.',
            'Click Race Condition Simulation to force many simulated readers to read stock at the same time.',
            'Compare Orders Count, Stock, Invariants, and Realtime Log between Naive and Production.',
            'Click Reset All to restore both databases to the initial state.',
        ],
        'naive_techniques' => [
            'Basic Read → Check → Save',
            'No transaction',
            'No lockForUpdate',
            'No atomic update',
            'No invariant protection',
        ],
        'production_techniques' => [
            'Database transaction',
            'Atomic update',
            'Request key / idempotency-like protection',
            'Stock movement ledger',
            'Inventory invariant validation',
        ],
        'learning_center' => [
            'overview' => [
                'problem' => 'Có 1 sản phẩm tồn kho nhưng nhiều request checkout cùng lúc.',
                'failure' => 'Naive flow dùng read → check → save nên nhiều request có thể cùng đọc stock cũ và cùng tạo order.',
                'solution' => 'Production flow dùng atomic update để check và reserve stock trong cùng một câu SQL.',
                'cost' => 'Production flow cần thêm schema, movement ledger, invariant check và code phức tạp hơn.',
            ],
            'code' => [
                'naive_description' => 'Code đơn giản, dễ đọc nhưng có race window giữa lúc đọc stock và lúc save.',
                'production_description' => 'Code dùng atomic update để database quyết định request nào được reserve stock.',
            ],
        ],
        'tradeoffs' => [
            'read_check_save' => [
                'pros' => [
                    'easy' => 'Dễ hiểu với người mới.',
                    'simple' => 'Ít code, ít bảng.',
                ],
                'cons' => [
                    'race' => 'Có race condition khi nhiều request chạy cùng lúc.',
                    'oversell' => 'Có thể tạo nhiều order từ một stock.',
                ],
            ],
            'atomic_update' => [
                'pros' => [
                    'fast' => 'Nhanh và ít lock lâu.',
                    'safe' => 'Bảo vệ tốt invariant đơn giản như available stock.',
                ],
                'cons' => [
                    'simple_rules' => 'Khó áp dụng nếu business rule quá phức tạp.',
                ],
            ],
            'ledger' => [
                'pros' => [
                    'audit' => 'Dễ audit lịch sử thay đổi stock.',
                    'reconcile' => 'Hỗ trợ reconciliation khi dữ liệu lệch.',
                ],
                'cons' => [
                    'more_tables' => 'Tốn thêm bảng, code và monitoring.',
                ],
            ],
        ],
        'ui' => [
            'actions' => [
                'real_requests_label' => 'Real checkout requests',
                'simulation_label' => 'Race simulation',
                'custom_real_default' => 10,
                'custom_simulation_default' => 50,
            ],
            'chart' => [
                'title' => 'Orders vs Valid Stock Limit',
                'description' => 'Compare created orders against the valid stock limit.',
                'naive_label' => 'Naive Orders',
                'production_label' => 'Production Orders',
                'limit_label' => 'Valid Stock Limit',
                'metric_key' => 'orders_count',
                'limit_key' => 'valid_stock_limit',
            ],
            'logs' => [
                'naive_title' => 'Naive Checkout Log',
                'production_title' => 'Production Checkout Log',
            ],
        ],
    ],
    'booking-double-submit' => [
        'title' => 'Booking Double Submit Lab',
        'subtitle' => 'Concurrency & Race Condition',
        'description' => 'Compare booking without concurrency control against a production-grade implementation using transactions and locks.',
        'action_hint' => 'Submit multiple booking requests and observe duplicate reservations on the same time slot.',
        'how_to_use' => [
            'Send multiple booking requests.',
            'Observe created reservations.',
            'Compare Naive and Production.',
            'Check the booking invariant.',
        ],
        'learning_goals' => [
            'Understand race conditions.',
            'Understand booking conflicts.',
            'Understand transactions.',
            'Understand lockForUpdate.',
            'Understand invariant protection.',
        ],
        'naive_techniques' => [
            'Read → Check → Insert',
            'No transaction',
            'No lock',
            'Race condition',
        ],
        'production_techniques' => [
            'Transaction',
            'lockForUpdate',
            'Request Key',
            'Invariant Validation',
            'Deadlock Retry',
        ],
        'ui' => [
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
        ],
        'learning_center' => [
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
        ],
    ],
    'payment-idempotency' => [
        'title' => 'Payment Idempotency Lab',
        'subtitle' => 'Retry-safe Payment Processing',
        'description' => 'Compare naive payment processing against idempotent payment execution.',
        'action_hint' => 'Execute multiple payment requests against the same order.',
        'how_to_use' => [
            'Run payment requests repeatedly.',
            'Observe payment records.',
            'Observe order state.',
            'Compare duplicate payment behavior.',
        ],
        'learning_goals' => [
            'Understand idempotency.',
            'Understand payment duplication.',
            'Understand retry-safe APIs.',
            'Understand idempotency keys.',
            'Understand payment consistency.',
        ],
        'naive_techniques' => [
            'Direct payment creation',
            'No idempotency key',
            'No deduplication',
            'Duplicate payments possible',
        ],
        'production_techniques' => [
            'Idempotency Key',
            'Unique Constraint',
            'Transaction',
            'lockForUpdate',
            'Response Replay',
        ],
        'ui' => [
            'actions' => [
                'real_requests_label' => 'Real payment requests',
                'simulation_label' => 'Retry simulation',
                'custom_real_default' => 10,
                'custom_simulation_default' => 50,
            ],
            'chart' => [
                'title' => 'Payments vs Valid Payment Limit',
                'description' => 'Compare created payments against the one-payment limit.',
                'naive_label' => 'Naive Payments',
                'production_label' => 'Production Payments',
                'limit_label' => 'Valid Payment Limit',
                'metric_key' => 'result_count',
                'limit_key' => 'valid_limit',
            ],
            'logs' => [
                'naive_title' => 'Naive Payment Log',
                'production_title' => 'Production Payment Log',
            ],
        ],
        'learning_center' => [
            'overview' => [
                'problem' => 'Một order chỉ nên được thanh toán thành công một lần, nhưng retry/double click/webhook có thể gọi payment endpoint nhiều lần.',
                'failure' => 'Naive flow tạo payment mới cho mỗi request nên một order có thể có nhiều payment succeeded.',
                'solution' => 'Production flow dùng idempotency key, unique constraint, transaction và lockForUpdate để cùng một hành động chỉ được xử lý một lần.',
                'cost' => 'Cần thêm bảng idempotency_keys, logic lưu trạng thái processing/completed và xử lý case same key khác payload nếu nâng cấp full pattern.',
            ],
            'code_examples' => [
                [
                    'title' => 'Naive Payment Flow',
                    'type' => 'naive',
                    'language' => 'php',
                    'description' => 'Mỗi lần gọi endpoint đều tạo payment mới, kể cả khi order đã được thanh toán.',
                    'code' => <<<'PHP'
                        $order = NaivePaymentOrder::query()->firstOrFail();

                        $payment = NaivePayment::create([
                            'order_id' => $order->id,
                            'amount' => $order->amount,
                            'status' => 'succeeded',
                        ]);

                        $order->update([
                            'status' => 'paid',
                        ]);

                        return LabActionResult::success(
                            "Naive: Payment #{$payment->id} created."
                        );
                        PHP,
                ],
                [
                    'title' => 'Production Payment Flow',
                    'type' => 'production',
                    'language' => 'php',
                    'description' => 'Idempotency key giúp retry cùng action không tạo duplicate payment.',
                    'code' => <<<'PHP'
                        $payment = DB::transaction(function () use ($requestKey) {
                            $existingKey = ProductionIdempotencyKey::query()
                                ->where('key', $requestKey)
                                ->lockForUpdate()
                                ->first();

                            if ($existingKey && $existingKey->status === 'completed') {
                                return ProductionPayment::query()
                                    ->where('request_key', $requestKey)
                                    ->first();
                            }

                            ProductionIdempotencyKey::query()->firstOrCreate([
                                'key' => $requestKey,
                            ], [
                                'status' => 'processing',
                            ]);

                            $order = ProductionPaymentOrder::query()
                                ->lockForUpdate()
                                ->firstOrFail();

                            if ($order->status === 'paid') {
                                return null;
                            }

                            $payment = ProductionPayment::create([
                                'order_id' => $order->id,
                                'amount' => $order->amount,
                                'status' => 'succeeded',
                                'request_key' => $requestKey,
                            ]);

                            $order->update([
                                'status' => 'paid',
                                'paid_at' => now(),
                            ]);

                            ProductionIdempotencyKey::query()
                                ->where('key', $requestKey)
                                ->update([
                                    'status' => 'completed',
                                    'response_payload' => [
                                        'payment_id' => $payment->id,
                                    ],
                                ]);

                            return $payment;
                        }, attempts: 3);
                        PHP,
                ],
            ],
            'sequence_diagrams' => [
                [
                    'title' => 'Naive Payment Retry',
                    'type' => 'naive',
                    'content' => <<<'TEXT'
                        Request A pays order
                        Payment #1 created

                        Request B retries pay order
                        Payment #2 created

                        Request C retries pay order
                        Payment #3 created

                        Result:
                        One order has multiple successful payments.
                        Invariant is broken.
                        TEXT,
                ],
                [
                    'title' => 'Production Idempotency',
                    'type' => 'production',
                    'content' => <<<'TEXT'
                        Request A sends idempotency_key=abc
                        System creates processing key
                        System locks order
                        Payment #1 created
                        Key marked completed

                        Request B sends same action or order already paid
                        System returns existing result or rejects safely

                        Result:
                        One order has only one successful payment.
                        Invariant is protected.
                        TEXT,
                ],
            ],
            'database_schemas' => [
                [
                    'title' => 'naive_payments',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        order_id
                        amount
                        status
                        created_at
                        updated_at
                        SQL,
                ],
                [
                    'title' => 'production_payments',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        order_id
                        amount
                        status
                        request_key
                        created_at
                        updated_at

                        UNIQUE request_key
                        SQL,
                ],
                [
                    'title' => 'production_idempotency_keys',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        key
                        status
                        response_payload
                        created_at
                        updated_at

                        UNIQUE key
                        SQL,
                ],
            ],
            'trade_offs' => [
                [
                    'technique' => 'No Idempotency',
                    'pros' => [
                        'Code ngắn, dễ viết.',
                        'Không cần thêm table.',
                    ],
                    'cons' => [
                        'Retry có thể tạo duplicate payment.',
                        'Rất nguy hiểm với money operation.',
                    ],
                ],
                [
                    'technique' => 'Idempotency Key',
                    'pros' => [
                        'Retry-safe.',
                        'Giúp external client gọi lại request an toàn.',
                        'Phù hợp payment/order/webhook.',
                    ],
                    'cons' => [
                        'Cần lưu key, trạng thái và response.',
                        'Cần xử lý case cùng key nhưng payload khác.',
                    ],
                ],
                [
                    'technique' => 'Unique Constraint',
                    'pros' => [
                        'Database enforce duplicate protection.',
                        'An toàn hơn check bằng code thuần.',
                    ],
                    'cons' => [
                        'Cần thiết kế key đúng.',
                        'Cần xử lý duplicate key exception.',
                    ],
                ],
                [
                    'technique' => 'lockForUpdate',
                    'pros' => [
                        'Bảo vệ order state khi nhiều request cùng thanh toán.',
                        'Giảm race condition giữa check paid và create payment.',
                    ],
                    'cons' => [
                        'Có thể giảm throughput khi nhiều request tranh cùng order.',
                        'Cần tránh transaction quá dài.',
                    ],
                ],
            ],
        ],
    ],
    'queue-retry-safe-job' => [
        'title' => 'Queue Retry-safe Job Lab',
        'subtitle' => 'Idempotent Background Processing',
        'description' => 'Compare retry-unsafe jobs with idempotent queue processing.',
        'action_hint' => 'Simulate worker retries and observe duplicated side effects.',
        'how_to_use' => [
            'Run jobs repeatedly.',
            'Simulate retries.',
            'Observe sent_count.',
            'Compare side effect duplication.',
        ],
        'learning_goals' => [
            'Understand queue retries.',
            'Understand side effect duplication.',
            'Understand idempotent jobs.',
            'Understand processed job tracking.',
            'Understand production queue design.',
        ],
        'naive_techniques' => [
            'Direct retry',
            'No job tracking',
            'No deduplication',
            'Repeated side effects',
        ],
        'production_techniques' => [
            'Processed Job Table',
            'Job Key',
            'Unique Constraint',
            'Transaction',
            'Retry-safe Design',
        ],
        'ui' => [
            'actions' => [
                'real_requests_label' => 'Real job executions',
                'simulation_label' => 'Retry simulation',
                'custom_real_default' => 10,
                'custom_simulation_default' => 50,
            ],
            'chart' => [
                'title' => 'Sent Count vs Valid Send Limit',
                'description' => 'Compare side effects against the one-send limit.',
                'naive_label' => 'Naive Sent Count',
                'production_label' => 'Production Sent Count',
                'limit_label' => 'Valid Send Limit',
                'metric_key' => 'result_count',
                'limit_key' => 'valid_limit',
            ],
            'logs' => [
                'naive_title' => 'Naive Queue Log',
                'production_title' => 'Production Queue Log',
            ],
        ],
        'learning_center' => [
            'overview' => [
                'problem' => 'Queue job có thể retry nhiều lần do timeout, exception, worker restart hoặc network issue.',
                'failure' => 'Naive job mỗi lần chạy đều thực hiện side effect, dẫn đến gửi email/notification nhiều lần.',
                'solution' => 'Production job dùng processed_jobs table với unique job_key để đảm bảo cùng một logical job chỉ xử lý side effect một lần.',
                'cost' => 'Cần thiết kế job_key ổn định, lưu processed job và dọn dữ liệu cũ nếu bảng lớn.',
            ],
            'code_examples' => [
                [
                    'title' => 'Naive Retry Job',
                    'type' => 'naive',
                    'language' => 'php',
                    'description' => 'Mỗi lần job được retry đều tăng sent_count, mô phỏng gửi notification nhiều lần.',
                    'code' => <<<'PHP'
                        $notification = NaiveJobNotification::query()->firstOrFail();

                        for ($i = 1; $i <= $count; $i++) {
                            $notification->increment('sent_count');
                        }

                        return LabActionResult::success(
                            "Naive Queue: Job executed {$count} times."
                        );
                        PHP,
                ],
                [
                    'title' => 'Production Retry-safe Job',
                    'type' => 'production',
                    'language' => 'php',
                    'description' => 'processed_jobs table đóng vai trò idempotency guard cho queue job.',
                    'code' => <<<'PHP'
                        private function processJob(string $jobKey): bool
                        {
                            return DB::transaction(function () use ($jobKey) {
                                $created = ProductionProcessedJob::query()->firstOrCreate([
                                    'job_key' => $jobKey,
                                ]);

                                if (! $created->wasRecentlyCreated) {
                                    return false;
                                }

                                $notification = ProductionJobNotification::query()
                                    ->lockForUpdate()
                                    ->firstOrFail();

                                $notification->increment('sent_count');

                                return true;
                            }, attempts: 3);
                        }
                        PHP,
                ],
            ],
            'sequence_diagrams' => [
                [
                    'title' => 'Naive Queue Retry',
                    'type' => 'naive',
                    'content' => <<<'TEXT'
                        Job attempt #1 runs
                        Notification sent

                        Job timeout happens
                        Queue retries

                        Job attempt #2 runs
                        Notification sent again

                        Job attempt #3 runs
                        Notification sent again

                        Result:
                        One logical notification is sent multiple times.
                        Invariant is broken.
                        TEXT,
                ],
                [
                    'title' => 'Production Retry-safe Job',
                    'type' => 'production',
                    'content' => <<<'TEXT'
                        Job attempt #1 starts
                        Create processed_jobs row with job_key
                        Notification sent

                        Job retries with same job_key
                        processed_jobs row already exists
                        Side effect is skipped

                        Result:
                        One logical notification is sent once.
                        Invariant is protected.
                        TEXT,
                ],
            ],
            'database_schemas' => [
                [
                    'title' => 'naive_job_notifications',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        recipient
                        sent_count
                        created_at
                        updated_at
                        SQL,
                ],
                [
                    'title' => 'production_job_notifications',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        recipient
                        sent_count
                        created_at
                        updated_at
                        SQL,
                ],
                [
                    'title' => 'production_processed_jobs',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        job_key
                        created_at
                        updated_at

                        UNIQUE job_key
                        SQL,
                ],
            ],
            'trade_offs' => [
                [
                    'technique' => 'Naive Retry',
                    'pros' => [
                        'Không cần thêm table.',
                        'Job code đơn giản.',
                    ],
                    'cons' => [
                        'Retry có thể lặp side effect.',
                        'Dễ gửi email, notification, webhook nhiều lần.',
                    ],
                ],
                [
                    'technique' => 'Processed Job Guard',
                    'pros' => [
                        'Giúp job retry-safe.',
                        'Database unique key enforce xử lý một lần.',
                        'Dễ audit job nào đã xử lý.',
                    ],
                    'cons' => [
                        'Cần thiết kế job_key đúng.',
                        'Cần dọn bảng processed_jobs theo thời gian.',
                    ],
                ],
                [
                    'technique' => 'DB Transaction',
                    'pros' => [
                        'Gom kiểm tra processed job và side effect counter trong cùng unit.',
                        'Giảm race condition khi nhiều worker xử lý cùng job key.',
                    ],
                    'cons' => [
                        'Không nên đặt external API call dài trong transaction.',
                        'Cần xử lý deadlock/retry.',
                    ],
                ],
                [
                    'technique' => 'Idempotent Job Design',
                    'pros' => [
                        'Phù hợp queue production.',
                        'Giảm incident do retry hoặc worker crash.',
                    ],
                    'cons' => [
                        'Không phải side effect nào cũng dễ idempotent.',
                        'Cần phân biệt logical job key và physical queue attempt.',
                    ],
                ],
            ],
        ],
    ],
];
