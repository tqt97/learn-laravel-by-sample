<?php

namespace App\Services\Labs\Production\Inventory;

use App\DTOs\Labs\LabActionResult;
use App\DTOs\Labs\LabStateResult;
use App\Models\Labs\Production\ProductionInventoryMovement;
use App\Models\Labs\Production\ProductionInventoryOrder;
use App\Models\Labs\Production\ProductionInventoryProduct;
use App\Services\Labs\Core\LabDatabaseResetService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class ProductionInventoryOversellService
{
    public function __construct(
        private readonly LabDatabaseResetService $resetService,
    ) {}

    private function simulateSafeBatch(array $payload = []): LabActionResult
    {
        $count = min(max((int) ($payload['count'] ?? 5), 1), 100);

        $success = 0;
        $failed = 0;
        $orderIds = [];

        for ($i = 1; $i <= $count; $i++) {
            $result = $this->singleOrder([
                'request_key' => (string) Str::uuid(),
            ]);

            if ($result->success) {
                $success++;
                $orderIds[] = $result->data['order_id'] ?? null;
            } else {
                $failed++;
            }
        }

        return LabActionResult::success(
            message: "Production Batch: {$success} success, {$failed} sold out. Invariant protected.",
            data: [
                'success' => $success,
                'failed' => $failed,
                'order_ids' => array_filter($orderIds),
            ],
        );
    }

    private function singleOrder(array $payload = []): LabActionResult
    {
        $requestKey = $payload['request_key'] ?? (string) Str::uuid();

        try {
            $order = DB::transaction(function () use ($requestKey) {
                $existing = ProductionInventoryOrder::query()
                    ->where('request_key', $requestKey)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $product = ProductionInventoryProduct::query()->firstOrFail();

                $affected = ProductionInventoryProduct::query()
                    ->whereKey($product->id)
                    ->whereRaw('(stock_on_hand - reserved_stock) >= 1')
                    ->update([
                        'reserved_stock' => DB::raw('reserved_stock + 1'),
                    ]);

                if ($affected === 0) {
                    return null;
                }

                $product->refresh();

                $order = ProductionInventoryOrder::create([
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'status' => 'created',
                    'request_key' => $requestKey,
                ]);

                ProductionInventoryMovement::create([
                    'product_id' => $product->id,
                    'type' => 'reserve',
                    'stock_delta' => 0,
                    'reserved_delta' => 1,
                    'sold_delta' => 0,
                    'stock_on_hand_after' => $product->stock_on_hand,
                    'reserved_stock_after' => $product->reserved_stock,
                    'sold_stock_after' => $product->sold_stock,
                    'reference_type' => ProductionInventoryOrder::class,
                    'reference_id' => $order->id,
                ]);

                return $order;
            }, attempts: 3);

            if (! $order) {
                return LabActionResult::failed('Production: Sold out. No order created.');
            }

            return LabActionResult::success(
                message: "Production: Order #{$order->id} created safely.",
                data: [
                    'order_id' => $order->id,
                    'request_key' => $requestKey,
                ],
            );
        } catch (Throwable $e) {
            return LabActionResult::failed(
                message: 'Production: '.$e->getMessage(),
                statusCode: 500,
            );
        }
    }

    public function order(array $payload = []): LabActionResult
    {
        $runMode = $payload['run_mode'] ?? 'single';

        if ($runMode === 'batch_race') {
            return $this->simulateSafeBatch($payload);
        }

        return $this->singleOrder($payload);
    }

    public function state(): LabStateResult
    {
        $product = ProductionInventoryProduct::query()->first();

        $ordersCount = ProductionInventoryOrder::query()->count();

        $stockOnHand = $product?->stock_on_hand ?? 0;
        $reservedStock = $product?->reserved_stock ?? 0;
        $availableStock = max(0, $stockOnHand - $reservedStock);

        return new LabStateResult(
            mode: 'production',
            title: 'Production Checkout',
            metrics: [
                'stock_on_hand' => $stockOnHand,
                'reserved_stock' => $reservedStock,
                'available_stock' => $availableStock,
                'orders_count' => $ordersCount,
                'valid_stock_limit' => 1,
            ],
            records: ProductionInventoryOrder::query()
                ->latest()
                ->limit(10)
                ->get(['id', 'quantity', 'status', 'request_key', 'created_at'])
                ->toArray(),
            invariants: [
                [
                    'name' => 'Reserved stock must not exceed stock on hand',
                    'ok' => $reservedStock <= $stockOnHand,
                    'message' => $reservedStock <= $stockOnHand
                        ? 'OK'
                        : 'Broken: reserved stock exceeds stock on hand.',
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
        $this->resetService->resetProductionInventory();

        return LabActionResult::success('Production database reset successfully.');
    }
}
