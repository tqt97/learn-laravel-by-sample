<?php

namespace App\Services\Labs\Core\Scenarios;

use App\DTOs\Labs\LabActionResult;
use App\DTOs\Labs\LabStateResult;
use App\Enums\Labs\LabMode;
use App\Services\Labs\Contracts\LabScenarioContract;
use App\Services\Labs\Core\LabDatabaseResetService;
use App\Services\Labs\Naive\Concurrency\NaiveQueueRetryService;
use App\Services\Labs\Production\Concurrency\ProductionQueueRetryService;

final class QueueRetrySafeJobScenario implements LabScenarioContract
{
    public function __construct(
        private readonly NaiveQueueRetryService $naive,
        private readonly ProductionQueueRetryService $production,
        private readonly LabDatabaseResetService $resetService,
    ) {}

    public function key(): string
    {
        return 'queue-retry-safe-job';
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
                'real_requests_label' => 'Real job executions',
                'simulation_label' => 'Retry simulation',
                'custom_real_default' => 10,
                'custom_simulation_default' => 50,
            ],
            'chart' => [
                'title' => 'Sent Count vs Valid Send Limit',
                'description' => 'Compare side effects against the one-send limit.',
                'naive_label' => 'Naive Sent Count',
                'production_label' => 'Production Sent Count',
                'limit_label' => 'Valid Send Limit',
                'metric_key' => 'result_count',
                'limit_key' => 'valid_limit',
            ],
            'logs' => [
                'naive_title' => 'Naive Queue Log',
                'production_title' => 'Production Queue Log',
            ],
        ];
    }

    public function action(LabMode $mode, array $payload = []): LabActionResult
    {
        return match ($mode) {
            LabMode::Naive => $this->naive->run($payload),
            LabMode::Production => $this->production->run($payload),
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
        $this->resetService->resetQueueRetrySafeJob();

        return LabActionResult::success('Reset both Naive and Production queue databases successfully.');
    }

    public function learningCenter(): array
    {
        return [
            'overview' => [
                'problem' => 'Queue job có thể retry nhiều lần do timeout, exception, worker restart hoặc network issue.',
                'failure' => 'Naive job mỗi lần chạy đều thực hiện side effect, dẫn đến gửi email/notification nhiều lần.',
                'solution' => 'Production job dùng processed_jobs table với unique job_key để đảm bảo cùng một logical job chỉ xử lý side effect một lần.',
                'cost' => 'Cần thiết kế job_key ổn định, lưu processed job và dọn dữ liệu cũ nếu bảng lớn.',
            ],
            'code_examples' => [
                [
                    'title' => 'Naive Retry Job',
                    'type' => 'naive',
                    'language' => 'php',
                    'description' => 'Mỗi lần job được retry đều tăng sent_count, mô phỏng gửi notification nhiều lần.',
                    'code' => <<<'PHP'
                        $notification = NaiveJobNotification::query()->firstOrFail();

                        for ($i = 1; $i <= $count; $i++) {
                            $notification->increment('sent_count');
                        }

                        return LabActionResult::success(
                            "Naive Queue: Job executed {$count} times."
                        );
                        PHP,
                ],
                [
                    'title' => 'Production Retry-safe Job',
                    'type' => 'production',
                    'language' => 'php',
                    'description' => 'processed_jobs table đóng vai trò idempotency guard cho queue job.',
                    'code' => <<<'PHP'
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
                        PHP,
                ],
            ],
            'sequence_diagrams' => [
                [
                    'title' => 'Naive Queue Retry',
                    'type' => 'naive',
                    'content' => <<<'TEXT'
                        Job attempt #1 runs
                        Notification sent

                        Job timeout happens
                        Queue retries

                        Job attempt #2 runs
                        Notification sent again

                        Job attempt #3 runs
                        Notification sent again

                        Result:
                        One logical notification is sent multiple times.
                        Invariant is broken.
                        TEXT,
                ],
                [
                    'title' => 'Production Retry-safe Job',
                    'type' => 'production',
                    'content' => <<<'TEXT'
                        Job attempt #1 starts
                        Create processed_jobs row with job_key
                        Notification sent

                        Job retries with same job_key
                        processed_jobs row already exists
                        Side effect is skipped

                        Result:
                        One logical notification is sent once.
                        Invariant is protected.
                        TEXT,
                ],
            ],
            'database_schemas' => [
                [
                    'title' => 'naive_job_notifications',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        recipient
                        sent_count
                        created_at
                        updated_at
                        SQL,
                ],
                [
                    'title' => 'production_job_notifications',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        recipient
                        sent_count
                        created_at
                        updated_at
                        SQL,
                ],
                [
                    'title' => 'production_processed_jobs',
                    'language' => 'sql',
                    'code' => <<<'SQL'
                        id
                        job_key
                        created_at
                        updated_at

                        UNIQUE job_key
                        SQL,
                ],
            ],
            'trade_offs' => [
                [
                    'technique' => 'Naive Retry',
                    'pros' => [
                        'Không cần thêm table.',
                        'Job code đơn giản.',
                    ],
                    'cons' => [
                        'Retry có thể lặp side effect.',
                        'Dễ gửi email, notification, webhook nhiều lần.',
                    ],
                ],
                [
                    'technique' => 'Processed Job Guard',
                    'pros' => [
                        'Giúp job retry-safe.',
                        'Database unique key enforce xử lý một lần.',
                        'Dễ audit job nào đã xử lý.',
                    ],
                    'cons' => [
                        'Cần thiết kế job_key đúng.',
                        'Cần dọn bảng processed_jobs theo thời gian.',
                    ],
                ],
                [
                    'technique' => 'DB Transaction',
                    'pros' => [
                        'Gom kiểm tra processed job và side effect counter trong cùng unit.',
                        'Giảm race condition khi nhiều worker xử lý cùng job key.',
                    ],
                    'cons' => [
                        'Không nên đặt external API call dài trong transaction.',
                        'Cần xử lý deadlock/retry.',
                    ],
                ],
                [
                    'technique' => 'Idempotent Job Design',
                    'pros' => [
                        'Phù hợp queue production.',
                        'Giảm incident do retry hoặc worker crash.',
                    ],
                    'cons' => [
                        'Không phải side effect nào cũng dễ idempotent.',
                        'Cần phân biệt logical job key và physical queue attempt.',
                    ],
                ],
            ],
        ];
    }
}
