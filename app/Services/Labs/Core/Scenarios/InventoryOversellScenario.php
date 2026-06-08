<?php

namespace App\Services\Labs\Core\Scenarios;

use App\DTOs\Labs\LabActionResult;
use App\DTOs\Labs\LabStateResult;
use App\Enums\Labs\LabMode;
use App\Services\Labs\Contracts\LabScenarioContract;
use App\Services\Labs\Core\LabDatabaseResetService;
use App\Services\Labs\Naive\Inventory\NaiveInventoryOversellService;
use App\Services\Labs\Production\Inventory\ProductionInventoryOversellService;

final class InventoryOversellScenario implements LabScenarioContract
{
    public function __construct(
        private readonly NaiveInventoryOversellService $naive,
        private readonly ProductionInventoryOversellService $production,
        private readonly LabDatabaseResetService $resetService,
    ) {}

    public function key(): string
    {
        return 'inventory-oversell';
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
            'real_requests' => [1, 2, 5, 10],
            'race_simulation' => [5, 10, 20, 50],
        ];
    }

    public function limits(): array
    {
        return [
            'real_requests_max' => 20,
            'race_simulation_max' => 100,
        ];
    }

    public function learningCenter(): array
    {
        return [
            'overview' => [
                'problem' => __('lab_scenarios.'.$this->key().'.learning_center.overview.problem'),
                'failure' => __('lab_scenarios.'.$this->key().'.learning_center.overview.failure'),
                'solution' => __('lab_scenarios.'.$this->key().'.learning_center.overview.solution'),
                'cost' => __('lab_scenarios.'.$this->key().'.learning_center.overview.cost'),
            ],
            'code_examples' => [
                [
                    'title' => 'NaiveInventoryOversellService',
                    'type' => 'naive',
                    'language' => 'php',
                    'description' => __('lab_scenarios.'.$this->key().'.learning_center.code.naive_description'),
                    'code' => <<<'PHP'
                        $product = NaiveInventoryProduct::query()->firstOrFail();

                        if ($product->stock < 1) {
                            return LabActionResult::failed('Naive: Sold out.');
                        }

                        usleep(300_000);

                        $product->stock -= 1;
                        $product->save();

                        $order = NaiveInventoryOrder::create([
                            'product_id' => $product->id,
                            'quantity' => 1,
                            'status' => 'created',
                        ]);
                        PHP,
                ],
                [
                    'title' => 'ProductionInventoryOversellService',
                    'type' => 'production',
                    'language' => 'php',
                    'description' => __('lab_scenarios.'.$this->key().'.learning_center.code.production_description'),
                    'code' => <<<'PHP'
                        $affected = ProductionInventoryProduct::query()
                            ->whereKey($product->id)
                            ->whereRaw('(stock_on_hand - reserved_stock) >= 1')
                            ->update([
                                'reserved_stock' => DB::raw('reserved_stock + 1'),
                            ]);

                        if ($affected === 0) {
                            return LabActionResult::failed('Production: Sold out.');
                        }

                        $order = ProductionInventoryOrder::create([
                            'product_id' => $product->id,
                            'quantity' => 1,
                            'status' => 'created',
                            'request_key' => $requestKey,
                        ]);
                        PHP,
                ],
            ],
            'sequence_diagrams' => [
                [
                    'title' => 'Naive Flow',
                    'type' => 'naive',
                    'content' => <<<'TEXT'
                        Request A reads stock = 1
                        Request B reads stock = 1
                        Request C reads stock = 1

                        All requests think stock is available.

                        A creates order
                        B creates order
                        C creates order

                        Result:
                        1 stock creates multiple orders.
                        Invariant is broken.
                        TEXT,
                ],
                [
                    'title' => 'Production Flow',
                    'type' => 'production',
                    'content' => <<<'TEXT'
                        Request A runs atomic update
                        Affected rows = 1
                        Order is created

                        Request B runs atomic update
                        Affected rows = 0
                        Sold out

                        Request C runs atomic update
                        Affected rows = 0
                        Sold out

                        Result:
                        1 stock creates only 1 order.
                        Invariant is protected.
                        TEXT,
                ],
            ],
            'database_schemas' => [
                [
                    'title' => 'naive_inventory_products',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        name
                        stock
                        created_at
                        updated_at
                        SQL,
                ],
                [
                    'title' => 'production_inventory_products',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        name
                        stock_on_hand
                        reserved_stock
                        sold_stock
                        created_at
                        updated_at
                        SQL,
                ],
                [
                    'title' => 'production_inventory_movements',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        product_id
                        type
                        stock_delta
                        reserved_delta
                        sold_delta
                        stock_on_hand_after
                        reserved_stock_after
                        sold_stock_after
                        reference_type
                        reference_id
                        created_at
                        updated_at
                        SQL,
                ],
            ],
            'trade_offs' => [
                [
                    'technique' => 'Read → Check → Save',
                    'pros' => [
                        __('lab_scenarios.'.$this->key().'.tradeoffs.read_check_save.pros.easy'),
                        __('lab_scenarios.'.$this->key().'.tradeoffs.read_check_save.pros.simple'),
                    ],
                    'cons' => [
                        __('lab_scenarios.'.$this->key().'.tradeoffs.read_check_save.cons.race'),
                        __('lab_scenarios.'.$this->key().'.tradeoffs.read_check_save.cons.oversell'),
                    ],
                ],
                [
                    'technique' => 'Atomic Update',
                    'pros' => [
                        __('lab_scenarios.'.$this->key().'.tradeoffs.atomic_update.pros.fast'),
                        __('lab_scenarios.'.$this->key().'.tradeoffs.atomic_update.pros.safe'),
                    ],
                    'cons' => [
                        __('lab_scenarios.'.$this->key().'.tradeoffs.atomic_update.cons.simple_rules'),
                    ],
                ],
                [
                    'technique' => 'Stock Movement Ledger',
                    'pros' => [
                        __('lab_scenarios.'.$this->key().'.tradeoffs.ledger.pros.audit'),
                        __('lab_scenarios.'.$this->key().'.tradeoffs.ledger.pros.reconcile'),
                    ],
                    'cons' => [
                        __('lab_scenarios.'.$this->key().'.tradeoffs.ledger.cons.more_tables'),
                    ],
                ],
            ],
        ];
    }

    public function uiConfig(): array
    {
        return [
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
                'metric_key' => 'result_count',
                'limit_key' => 'valid_limit',
            ],
            'logs' => [
                'naive_title' => 'Naive Checkout Log',
                'production_title' => 'Production Checkout Log',
            ],
        ];
    }

    public function action(LabMode $mode, array $payload = []): LabActionResult
    {
        return match ($mode) {
            LabMode::Naive => $this->naive->order($payload),
            LabMode::Production => $this->production->order($payload),
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
        $this->resetService->resetInventoryOversell();

        return LabActionResult::success(__('lab.reset_all'));
    }

    // public function overview(): array
    // {
    //     return [
    //         'problem' => '1 sản phẩm nhưng nhiều request cùng checkout.',
    //         'failure' => 'Oversell xảy ra khi nhiều request đọc cùng stock.',
    //         'solution' => 'Atomic update + invariant.',
    //     ];
    // }

    // public function codeExamples(): array
    // {
    //     return [
    //         [
    //             'title' => 'Naive Inventory Service',
    //             'language' => 'php',
    //             'type' => 'naive',
    //             'code' => <<<'PHP'
    //                 $product = Product::first();

    //                 if ($product->stock > 0) {

    //                     usleep(300000);

    //                     $product->stock--;

    //                     $product->save();

    //                     Order::create();
    //                 }
    //                 PHP
    //         ],
    //         [
    //             'title' => 'Production Inventory Service',
    //             'language' => 'php',
    //             'type' => 'production',
    //             'code' => <<<'PHP'
    //                 $updated = Product::query()
    //                     ->where('stock', '>', 0)
    //                     ->decrement('stock');

    //                 if ($updated === 0) {
    //                     throw new SoldOutException();
    //                 }

    //                 Order::create();
    //                 PHP
    //         ],
    //     ];
    // }

    // public function sequenceDiagrams(): array
    // {
    //     return [
    //         [
    //             'title' => 'Naive Flow',
    //             'type' => 'naive',
    //             'content' => <<<'TEXT'
    //                 Request A
    //                     ↓
    //                 Read stock=1

    //                 Request B
    //                     ↓
    //                 Read stock=1

    //                 A save stock=0

    //                 B save stock=0

    //                 Order A
    //                 Order B

    //                 Oversell
    //                 TEXT
    //         ],
    //         [
    //             'title' => 'Production Flow',
    //             'type' => 'production',
    //             'content' => <<<'TEXT'
    //                 Request A
    //                     ↓
    //                 Atomic Update

    //                 success

    //                 Request B
    //                     ↓
    //                 Atomic Update

    //                 0 row affected

    //                 fail
    //                 TEXT
    //         ]
    //     ];
    // }

    // public function databaseSchemas(): array
    // {
    //     return [
    //         [
    //             'title' => 'inventory_products',
    //             'language' => 'sql',
    //             'code' => <<<'SQL'
    //                 id
    //                 name
    //                 stock
    //                 created_at
    //                 updated_at
    //                 SQL
    //         ],
    //         [
    //             'title' => 'inventory_orders',
    //             'language' => 'sql',
    //             'code' => <<<'SQL'
    //                 id
    //                 product_id
    //                 quantity
    //                 status
    //                 created_at
    //                 SQL
    //         ],
    //     ];
    // }

    // public function tradeOffs(): array
    // {
    //     return [
    //         [
    //             'technique' => 'Atomic Update',
    //             'pros' => [
    //                 'Nhanh',
    //                 'Không lock lâu',
    //                 'Scale tốt',
    //             ],
    //             'cons' => [
    //                 'Khó áp dụng business phức tạp',
    //             ],
    //         ],
    //         [
    //             'technique' => 'Lock For Update',
    //             'pros' => [
    //                 'Dễ hiểu',
    //                 'An toàn',
    //             ],
    //             'cons' => [
    //                 'Lock lâu',
    //                 'Giảm throughput',
    //             ],
    //         ],
    //     ];
    // }
}
