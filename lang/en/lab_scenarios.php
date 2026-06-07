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
    ],
];
