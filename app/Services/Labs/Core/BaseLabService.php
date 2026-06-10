<?php

namespace App\Services\Labs\Core;

use App\DTOs\Labs\LabActionResult;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseLabService
{
    public function __construct(
        protected readonly LabStateFactory $stateFactory,
    ) {}

    protected function normalizedCount(
        array $payload,
        int $default = 5,
        int $min = 1,
        int $max = 500,
    ): int {
        $count = (int) ($payload['count'] ?? $default);

        return min(max($count, $min), $max);
    }

    protected function failWithLoggedException(
        Throwable $e,
        string $logMessage,
        string $clientMessage,
        array $context = [],
    ): LabActionResult {
        Log::error($logMessage, [
            'exception' => $e,
            'service' => static::class,
            ...$context,
        ]);

        return LabActionResult::failed(
            message: $clientMessage,
            statusCode: 500,
        );
    }
}
