<?php

namespace App\Services\Labs\Core\Scenarios;

use App\DTOs\Labs\LabActionResult;
use App\DTOs\Labs\LabStateResult;
use App\Enums\Labs\LabMode;
use App\Services\Labs\Contracts\LabScenarioContract;
use App\Services\Labs\Core\LabDatabaseResetService;
use App\Services\Labs\Naive\Concurrency\NaivePaymentIdempotencyService;
use App\Services\Labs\Production\Concurrency\ProductionPaymentIdempotencyService;

final class PaymentIdempotencyScenario implements LabScenarioContract
{
    public function __construct(
        private readonly NaivePaymentIdempotencyService $naive,
        private readonly ProductionPaymentIdempotencyService $production,
        private readonly LabDatabaseResetService $resetService,
    ) {}

    public function key(): string
    {
        return 'payment-idempotency';
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
        ];
    }

    public function action(LabMode $mode, array $payload = []): LabActionResult
    {
        return match ($mode) {
            LabMode::Naive => $this->naive->pay($payload),
            LabMode::Production => $this->production->pay($payload),
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
        $this->resetService->resetPaymentIdempotency();

        return LabActionResult::success('Reset both Naive and Production payment databases successfully.');
    }

    public function learningCenter(): array
    {
        return [
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
        ];
    }
}
