<?php

namespace App\Services\Labs\Naive\Concurrency;

use App\DTOs\Labs\LabActionResult;
use App\DTOs\Labs\LabStateResult;
use App\Models\Labs\Naive\NaiveJobNotification;
use App\Models\Labs\Production\ProductionProcessedJob;
use App\Services\Labs\Core\LabDatabaseResetService;

final class NaiveQueueRetryService
{
    public function run(array $payload = []): LabActionResult
    {
        $count = min(max((int) ($payload['count'] ?? 1), 1), 500);

        $notification = NaiveJobNotification::query()->firstOrFail();

        for ($i = 1; $i <= $count; $i++) {
            $notification->increment('sent_count');
        }

        return LabActionResult::success("Naive Queue: Job executed {$count} times.");
    }

    public function state(): LabStateResult
    {
        $notification = NaiveJobNotification::query()->first();

        $sentCount = $notification?->sent_count ?? 0;

        return new LabStateResult(
            mode: 'naive',
            title: 'Naive Queue Retry',
            // metrics: [
            //     'sent_count' => $sentCount,
            //     'valid_send_limit' => 1,
            // ],
            metrics: [
                'result_count' => $sentCount,
                'valid_limit' => 1,
                'sent_count' => $sentCount,
                'valid_send_limit' => 1,
                'processed_jobs_count' => ProductionProcessedJob::query()->count(),
            ],
            invariants: [
                [
                    'name' => 'Notification should be sent once',
                    'ok' => $sentCount <= 1,
                    'message' => $sentCount <= 1 ? 'OK' : "Broken: sent {$sentCount} times.",
                ],
            ],
        );
    }

    public function reset(): LabActionResult
    {
        app(LabDatabaseResetService::class)->resetNaiveQueue();

        return LabActionResult::success('Naive queue database reset successfully.');
    }
}
