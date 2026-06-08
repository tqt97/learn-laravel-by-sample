<?php

return [
    'inventory-oversell' => [
        'title' => 'Bài toán: Bán vượt hàng tồn kho',
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
    ],
];
