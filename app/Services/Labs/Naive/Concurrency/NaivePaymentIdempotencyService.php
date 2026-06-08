<?php

namespace App\Services\Labs\Naive\Concurrency;

use App\DTOs\Labs\LabActionResult;
use App\DTOs\Labs\LabStateResult;
use App\Models\Labs\Naive\NaivePayment;
use App\Models\Labs\Naive\NaivePaymentOrder;
use App\Services\Labs\Core\LabDatabaseResetService;

final class NaivePaymentIdempotencyService
{
    public function pay(array $payload = []): LabActionResult
    {
        $runMode = $payload['run_mode'] ?? 'single';

        if ($runMode === 'batch_race') {
            return $this->simulateDuplicatePayments($payload);
        }

        return $this->singlePayment();
    }

    private function singlePayment(): LabActionResult
    {
        $order = NaivePaymentOrder::query()->firstOrFail();

        $payment = NaivePayment::create([
            'order_id' => $order->id,
            'amount' => $order->amount,
            'status' => 'succeeded',
        ]);

        $order->update(['status' => 'paid']);

        return LabActionResult::success("Naive: Payment #{$payment->id} created.");
    }

    private function simulateDuplicatePayments(array $payload): LabActionResult
    {
        $count = min(max((int) ($payload['count'] ?? 5), 1), 500);

        for ($i = 1; $i <= $count; $i++) {
            $this->singlePayment();
        }

        return LabActionResult::success("Naive Batch: {$count} payments created for one order.");
    }

    public function state(): LabStateResult
    {
        $count = NaivePayment::query()->count();

        return new LabStateResult(
            mode: 'naive',
            title: 'Naive Payment',
            // metrics: [
            //     'payments_count' => $count,
            //     'valid_payment_limit' => 1,
            // ],
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
        app(LabDatabaseResetService::class)->resetNaivePayment();

        return LabActionResult::success('Naive payment database reset successfully.');
    }
}
