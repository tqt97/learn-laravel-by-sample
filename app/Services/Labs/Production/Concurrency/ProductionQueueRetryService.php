<?php

namespace App\Services\Labs\Production\Concurrency;

use App\DTOs\Labs\LabActionResult;
use App\DTOs\Labs\LabStateResult;
use App\Models\Labs\Production\ProductionJobNotification;
use App\Models\Labs\Production\ProductionProcessedJob;
use App\Services\Labs\Core\BaseLabService;
use App\Services\Labs\Core\LabDatabaseResetService;
use Illuminate\Support\Facades\DB;

final class ProductionQueueRetryService extends BaseLabService
{
    public function run(array $payload = []): LabActionResult
    {
        $count = $this->normalizedCount($payload);

        $success = 0;
        $ignored = 0;

        for ($i = 1; $i <= $count; $i++) {
            $result = $this->processJob('send-welcome-email:user-1');

            $result ? $success++ : $ignored++;
        }

        return LabActionResult::success(
            "Production Queue: {$success} processed, {$ignored} ignored.",
            compact('success', 'ignored'),
        );
    }

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

    public function state(): LabStateResult
    {
        $notification = ProductionJobNotification::query()->first();

        $sentCount = $notification?->sent_count ?? 0;

        return new LabStateResult(
            mode: 'production',
            title: 'Production Queue Retry',
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
        app(LabDatabaseResetService::class)->resetProductionQueue();

        return LabActionResult::success('Production queue database reset successfully.');
    }
}
