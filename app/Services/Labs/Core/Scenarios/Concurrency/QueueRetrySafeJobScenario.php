<?php

namespace App\Services\Labs\Core\Scenarios\Concurrency;

use App\DTOs\Labs\LabActionResult;
use App\DTOs\Labs\LabStateResult;
use App\Enums\Labs\LabMode;
use App\Services\Labs\Concerns\HasDefaultLabControls;
use App\Services\Labs\Core\LabDatabaseResetService;
use App\Services\Labs\Core\Scenarios\BaseLabScenario;
use App\Services\Labs\Naive\Concurrency\NaiveQueueRetryService;
use App\Services\Labs\Production\Concurrency\ProductionQueueRetryService;

final class QueueRetrySafeJobScenario extends BaseLabScenario
{
    use HasDefaultLabControls;

    public function __construct(
        private readonly NaiveQueueRetryService $naive,
        private readonly ProductionQueueRetryService $production,
        private readonly LabDatabaseResetService $resetService,
    ) {}

    public function key(): string
    {
        return 'queue-retry-safe-job';
    }

    public function group(): string
    {
        return 'Concurrency';
    }

    public function part(): string
    {
        return 'Part 01';
    }

    public function order(): int
    {
        return 4;
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

    protected function resetMethod(): string
    {
        return 'resetQueueRetrySafeJob';
    }
}
