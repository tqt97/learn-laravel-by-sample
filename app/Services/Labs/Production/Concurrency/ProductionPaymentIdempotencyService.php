<?php

namespace App\Services\Labs\Production\Concurrency;

use App\DTOs\Labs\LabActionResult;
use App\DTOs\Labs\LabStateResult;
use App\Models\Labs\Production\ProductionIdempotencyKey;
use App\Models\Labs\Production\ProductionPayment;
use App\Models\Labs\Production\ProductionPaymentOrder;
use App\Services\Labs\Core\LabDatabaseResetService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class ProductionPaymentIdempotencyService
{
    public function pay(array $payload = []): LabActionResult
    {
        $runMode = $payload['run_mode'] ?? 'single';

        if ($runMode === 'batch_race') {
            return $this->simulateSafePayments($payload);
        }

        return $this->singlePayment($payload);
    }

    private function singlePayment(array $payload = []): LabActionResult
    {
        $requestKey = $payload['request_key'] ?? (string) Str::uuid();

        try {
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

            if (! $payment) {
                return LabActionResult::failed('Production: Order already paid.');
            }

            return LabActionResult::success("Production: Payment #{$payment->id} created safely.");
        } catch (Throwable $e) {
            Log::error('Production payment failed.', [
                'exception' => $e,
                'service' => self::class,
                'request_key' => $requestKey,
            ]);

            return LabActionResult::failed('Production: Something went wrong.', statusCode: 500);
        }
    }

    private function simulateSafePayments(array $payload): LabActionResult
    {
        $count = min(max((int) ($payload['count'] ?? 5), 1), 500);

        $success = 0;
        $failed = 0;

        for ($i = 1; $i <= $count; $i++) {
            $result = $this->singlePayment([
                'request_key' => (string) Str::uuid(),
            ]);

            $result->success ? $success++ : $failed++;
        }

        return LabActionResult::success(
            "Production Batch: {$success} success, {$failed} ignored/failed.",
            compact('success', 'failed'),
        );
    }

    public function state(): LabStateResult
    {
        $count = ProductionPayment::query()->count();

        return new LabStateResult(
            mode: 'production',
            title: 'Production Payment',
            metrics: [
                'result_count' => $count,
                'valid_limit' => 1,
                'payments_count' => $count,
                'valid_payment_limit' => 1,
            ],
            invariants: [
                [
                    'name' => 'Only one successful payment per order',
                    'ok' => $count <= 1,
                    'message' => $count <= 1 ? 'OK' : "Broken: {$count} payments created.",
                ],
            ],
        );
    }

    public function reset(): LabActionResult
    {
        app(LabDatabaseResetService::class)->resetProductionPayment();

        return LabActionResult::success('Production payment database reset successfully.');
    }
}
