<?php

namespace App\Services\Labs\Naive\Inventory;

use App\DTOs\Labs\LabActionResult;
use App\DTOs\Labs\LabStateResult;
use App\Models\Labs\Naive\NaiveInventoryOrder;
use App\Models\Labs\Naive\NaiveInventoryProduct;
use App\Services\Labs\Core\LabDatabaseResetService;
use Throwable;

final class NaiveInventoryOversellService
{
    public function __construct(
        private readonly LabDatabaseResetService $resetService,
    ) {}

    private function singleOrder(array $payload = []): LabActionResult
    {
        try {
            $product = NaiveInventoryProduct::query()->firstOrFail();

            if ($product->stock < 1) {
                return LabActionResult::failed('Naive: Sold out.');
            }

            usleep((int) ($payload['delay_microseconds'] ?? 300_000));

            $product->stock -= 1;
            $product->save();

            $order = NaiveInventoryOrder::create([
                'product_id' => $product->id,
                'quantity' => 1,
                'status' => 'created',
            ]);

            return LabActionResult::success(
                message: "Naive: Order #{$order->id} created.",
                data: [
                    'order_id' => $order->id,
                    'stock_after' => $product->fresh()->stock,
                ],
            );
        } catch (Throwable $e) {
            return LabActionResult::failed(
                message: 'Naive: '.$e->getMessage(),
                statusCode: 500,
            );
        }
    }

    private function simulateRaceBatch(array $payload = []): LabActionResult
    {
        try {
            $count = min(max((int) ($payload['count'] ?? 5), 1), 100);

            $product = NaiveInventoryProduct::query()->firstOrFail();

            /**
             * Mô phỏng 20 request cùng đọc stock tại cùng một thời điểm.
             * Tất cả đều nhìn thấy snapshot ban đầu.
             */
            $snapshotStock = $product->stock;

            if ($snapshotStock < 1) {
                return LabActionResult::failed('Naive Batch: Sold out.');
            }

            $createdOrders = [];

            for ($i = 1; $i <= $count; $i++) {
                /**
                 * Vì đây là Naive simulation, mỗi request giả lập đều tin rằng
                 * stock ban đầu vẫn còn đủ.
                 */
                if ($snapshotStock >= 1) {
                    $order = NaiveInventoryOrder::create([
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'status' => 'created',
                    ]);

                    $createdOrders[] = $order->id;
                }
            }

            /**
             * Để UI dễ hiểu, set stock về 0.
             * Bug chính cần visualize là:
             * - initial stock = 1
             * - orders created = 20
             */
            $product->update([
                'stock' => 0,
            ]);

            return LabActionResult::success(
                message: "Naive Batch Race: {$count} requests saw stock=1 and created ".count($createdOrders).' orders.',
                data: [
                    'created_order_ids' => $createdOrders,
                    'orders_created' => count($createdOrders),
                    'initial_stock' => $snapshotStock,
                    'stock_after' => 0,
                ],
            );
        } catch (Throwable $e) {
            return LabActionResult::failed(
                message: 'Naive Batch Race: '.$e->getMessage(),
                statusCode: 500,
            );
        }
    }

    public function order(array $payload = []): LabActionResult
    {
        $runMode = $payload['run_mode'] ?? 'single';

        if ($runMode === 'batch_race') {
            return $this->simulateRaceBatch($payload);
        }

        return $this->singleOrder($payload);
    }

    public function state(): LabStateResult
    {
        $product = NaiveInventoryProduct::query()->first();

        $ordersCount = NaiveInventoryOrder::query()->count();
        $stock = $product?->stock ?? 0;

        return new LabStateResult(
            mode: 'naive',
            title: 'Naive Checkout',
            metrics: [
                'stock' => $stock,
                'orders_count' => $ordersCount,
                'valid_stock_limit' => 1,
            ],
            records: NaiveInventoryOrder::query()
                ->latest()
                ->limit(10)
                ->get(['id', 'quantity', 'status', 'created_at'])
                ->toArray(),
            invariants: [
                [
                    'name' => 'Stock must not be negative',
                    'ok' => $stock >= 0,
                    'message' => $stock >= 0
                        ? 'OK'
                        : 'Broken: stock is negative.',
                ],
                [
                    'name' => 'Orders must not exceed initial stock',
                    'ok' => $ordersCount <= 1,
                    'message' => $ordersCount <= 1
                        ? 'OK'
                        : "Broken: {$ordersCount} orders created from 1 stock.",
                ],
            ],
        );
    }

    public function reset(): LabActionResult
    {
        $this->resetService->resetNaiveInventory();

        return LabActionResult::success('Naive database reset successfully.');
    }
}
