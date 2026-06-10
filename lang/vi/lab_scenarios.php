<?php

return [
    /*
     * |--------------------------------------------------------------------------
     * | Inventory Oversell
     * |--------------------------------------------------------------------------
     */
    'inventory-oversell' => [
        'title' => 'Bán vượt hàng tồn kho',
        'subtitle' => 'Laravel System Design Lab',
        'description' => 'So sánh checkout thiếu concurrency control với checkout dùng atomic update + invariant.',
        'action_hint' => 'Bấm nhiều request liên tục để thấy Naive có thể tạo nhiều order từ 1 stock, còn Production chỉ cho phép request đầu thành công và các request sau báo hết hàng.',
        'learning_goals' => [
            'Hiểu vì sao oversell xảy ra khi nhiều request cùng đọc stock cũ.',
            'Hiểu sự khác nhau giữa request thật và mô phỏng race condition.',
            'Hiểu atomic update giúp bảo vệ invariant inventory như thế nào.',
            'Biết quan sát bug qua metrics, invariants, log và chart.',
        ],
        'how_to_use' => [
            'Bấm nhóm Request thật để gửi Ajax request thực tế từ browser.',
            'Bấm nhóm Mô phỏng race condition để ép nhiều request cùng đọc stock tại cùng một thời điểm.',
            'So sánh Orders Count, Stock, Invariants và Realtime Log giữa Naive và Production.',
            'Bấm Reset All để đưa cả hai database về trạng thái ban đầu.',
        ],
        'naive_techniques' => [
            'Read → Check → Save cơ bản',
            'Không transaction',
            'Không lockForUpdate',
            'Không atomic update',
            'Không invariant protection',
        ],
        'production_techniques' => [
            'DB transaction',
            'Atomic update',
            'Request key/idempotency-like protection',
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
            'code_examples' => [
                [
                    'title' => 'Naive Inventory Service',
                    'language' => 'php',
                    'type' => 'naive',
                    'code' => <<<'PHP'
                        $product = Product::first();

                        if ($product->stock > 0) {

                            usleep(300000);

                            $product->stock--;

                            $product->save();

                            Order::create();
                        }
                        PHP
                ],
                [
                    'title' => 'Production Inventory Service',
                    'language' => 'php',
                    'type' => 'production',
                    'code' => <<<'PHP'
                        $updated = Product::query()
                            ->where('stock', '>', 0)
                            ->decrement('stock');

                        if ($updated === 0) {
                            throw new SoldOutException();
                        }

                        Order::create();
                        PHP
                ],
            ],
            'sequence_diagrams' => [[
                'title' => 'Naive Flow',
                'type' => 'naive',
                'content' => <<<'TEXT'
                    Request A
                        ↓
                    Read stock=1

                    Request B
                        ↓
                    Read stock=1

                    A save stock=0

                    B save stock=0

                    Order A
                    Order B

                    Oversell
                    TEXT
            ],
                [
                    'title' => 'Production Flow',
                    'type' => 'production',
                    'content' => <<<'TEXT'
                        Request A
                            ↓
                        Atomic Update

                        success

                        Request B
                            ↓
                        Atomic Update

                        0 row affected

                        fail
                        TEXT
                ]],
            'database_schemas' => [
                [
                    'title' => 'inventory_products',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        name
                        stock
                        created_at
                        updated_at
                        SQL
                ],
                [
                    'title' => 'inventory_orders',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        product_id
                        quantity
                        status
                        created_at
                        SQL
                ],
            ],
            'trade_offs' => [
                [
                    'technique' => 'Atomic Update',
                    'pros' => [
                        'Nhanh',
                        'Không lock lâu',
                        'Scale tốt',
                    ],
                    'cons' => [
                        'Khó áp dụng business phức tạp',
                    ],
                ],
                [
                    'technique' => 'Lock For Update',
                    'pros' => [
                        'Dễ hiểu',
                        'An toàn',
                    ],
                    'cons' => [
                        'Lock lâu',
                        'Giảm throughput',
                    ],
                ],
            ],
        ],
        'ui' => [
            'actions' => [
                'real_requests_label' => 'Nhóm Request thật',
                'simulation_label' => 'Mô phỏng race condition',
                'custom_real_default' => 10,
                'custom_simulation_default' => 50,
            ],
            'chart' => [
                'title' => 'Số lượng Order vs Giới hạn Tồn kho',
                'description' => 'So sánh số lượng order được tạo với giới hạn tồn kho thực tế.',
                'naive_label' => 'Naive Orders',
                'production_label' => 'Production Orders',
                'limit_label' => 'Giới hạn Tồn kho',
                'metric_key' => 'orders_count',
                'limit_key' => 'valid_stock_limit',
            ],
            'logs' => [
                'naive_title' => 'Naive Checkout Log',
                'production_title' => 'Production Checkout Log',
            ],
        ],
    ],

    /*
     * |--------------------------------------------------------------------------
     * | Booking Double Submit
     * |--------------------------------------------------------------------------
     */
    'booking-double-submit' => [
        'title' => 'Booking Double Submit Lab',
        'subtitle' => 'Concurrency & Race Condition',
        'description' => 'So sánh hệ thống booking không có concurrency control với hệ thống dùng transaction và lock để ngăn chặn double booking.',
        'action_hint' => 'Bấm nhiều request liên tục hoặc chạy Race Simulation để quan sát việc nhiều reservation được tạo cho cùng một time slot.',
        'how_to_use' => [
            'Thực hiện booking nhiều lần liên tục.',
            'Quan sát số lượng reservation được tạo.',
            'So sánh giữa Naive và Production.',
            'Kiểm tra invariant "một slot chỉ được booking một lần".',
        ],
        'learning_goals' => [
            'Hiểu race condition trong booking system.',
            'Hiểu vì sao Read → Check → Insert dễ bị lỗi.',
            'Hiểu DB transaction.',
            'Hiểu lockForUpdate.',
            'Hiểu invariant protection.',
        ],
        'naive_techniques' => [
            'Read → Check → Insert',
            'Không transaction',
            'Không lock',
            'Race window tồn tại',
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
                'real_requests_label' => 'Nhóm Booking thật',
                'simulation_label' => 'Mô phỏng Double-submit',
                'custom_real_default' => 10,
                'custom_simulation_default' => 50,
            ],
            'chart' => [
                'title' => 'Reservations vs Giới hạn Slot',
                'description' => 'So sánh số lượng reservation được tạo với giới hạn 1 slot.',
                'naive_label' => 'Naive Reservations',
                'production_label' => 'Production Reservations',
                'limit_label' => 'Giới hạn Slot',
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

    /*
     * |--------------------------------------------------------------------------
     * | Payment Idempotency
     * |--------------------------------------------------------------------------
     */
    'payment-idempotency' => [
        'title' => 'Payment Idempotency Lab',
        'subtitle' => 'Retry-safe Payment Processing',
        'description' => 'So sánh xử lý payment không có idempotency với hệ thống production-grade sử dụng idempotency key.',
        'action_hint' => 'Thực hiện nhiều lần thanh toán cùng một order để xem payment duplicate được tạo ra như thế nào.',
        'how_to_use' => [
            'Thực hiện pay nhiều lần.',
            'Quan sát payment records.',
            'Quan sát trạng thái order.',
            'So sánh duplicate payment giữa Naive và Production.',
        ],
        'learning_goals' => [
            'Hiểu Idempotency.',
            'Hiểu duplicate payment.',
            'Hiểu retry-safe API.',
            'Hiểu idempotency key.',
            'Hiểu payment consistency.',
        ],
        'naive_techniques' => [
            'Create Payment trực tiếp',
            'Không idempotency key',
            'Không request deduplication',
            'Duplicate payment dễ xảy ra',
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
                'real_requests_label' => 'Nhóm Thanh toán thật',
                'simulation_label' => 'Mô phỏng Retry',
                'custom_real_default' => 10,
                'custom_simulation_default' => 50,
            ],
            'chart' => [
                'title' => 'Số lượng Payment vs Giới hạn',
                'description' => 'So sánh số lượng payment được tạo với giới hạn 1 payment cho mỗi order.',
                'naive_label' => 'Naive Payments',
                'production_label' => 'Production Payments',
                'limit_label' => 'Giới hạn Thanh toán',
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

    /*
     * |--------------------------------------------------------------------------
     * | Queue Retry Safe Job
     * |--------------------------------------------------------------------------
     */
    'queue-retry-safe-job' => [
        'title' => 'Queue Retry-safe Job Lab',
        'subtitle' => 'Idempotent Background Processing',
        'description' => 'So sánh queue job không retry-safe với queue job có idempotent processing.',
        'action_hint' => 'Mô phỏng worker retry nhiều lần để quan sát side effect được thực hiện nhiều lần.',
        'how_to_use' => [
            'Chạy job nhiều lần.',
            'Mô phỏng retry.',
            'Quan sát sent_count.',
            'So sánh side effect duplication.',
        ],
        'learning_goals' => [
            'Hiểu queue retry.',
            'Hiểu side effect duplication.',
            'Hiểu idempotent jobs.',
            'Hiểu processed job tracking.',
            'Hiểu queue production design.',
        ],
        'naive_techniques' => [
            'Retry trực tiếp',
            'Không tracking job',
            'Không deduplication',
            'Side effect lặp lại',
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
                'real_requests_label' => 'Nhóm Thực thi thật',
                'simulation_label' => 'Mô phỏng Retry',
                'custom_real_default' => 10,
                'custom_simulation_default' => 50,
            ],
            'chart' => [
                'title' => 'Số lượng Gửi vs Giới hạn',
                'description' => 'So sánh số lượng side effect được thực hiện với giới hạn 1 lần gửi.',
                'naive_label' => 'Naive Sent Count',
                'production_label' => 'Production Sent Count',
                'limit_label' => 'Giới hạn Gửi',
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
